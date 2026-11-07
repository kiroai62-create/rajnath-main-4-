<?php

namespace App\Domain\Content;

use App\Domain\Astro\Gochar;
use App\Domain\Astro\Vedic;
use App\Http\Controllers\Web\ContentController;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Rashifal written without any AI key, straight from our Swiss Ephemeris engine and the classical
 * rules already used on the Rashi pages: the gochar (transit) table counted from the Moon sign
 * (Phaladeepika ch. 26), Chandra gochar for the day, Sade Sati / Dhaiya for Saturn, Tara Bala for
 * nakshatras, planetary friendship for numerology, the Chinese harmony/clash table and a
 * deterministic Major Arcana draw for tarot.
 *
 * Rows are stored with source "engine". The AI writer replaces them as soon as a key is added;
 * staff-written readings are never touched. Every function is pure computation (no network), so
 * the website and app always have a reading for the current period.
 */
class GocharRashifal
{
    public const SOURCE = 'engine';

    /** Same plan as the AI writer: kind => [period => topics]. */
    public const PLAN = RashifalWriter::PLAN;

    private const ORD_HI = ['पहले', 'दूसरे', 'तीसरे', 'चौथे', 'पाँचवें', 'छठे', 'सातवें', 'आठवें', 'नौवें', 'दसवें', 'ग्यारहवें', 'बारहवें'];

    private const ORD = ['1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'];

    private const HOUSE = [
        'en' => ['self, health and confidence', 'family, savings and speech', 'courage, siblings and short trips', 'home, mother, property and peace of mind',
            'children, love, study and creativity', 'work routine, health and competition', 'marriage, partnerships and business deals', 'sudden changes, research and hidden matters',
            'fortune, father, teachers and long journeys', 'career, status and reputation', 'income, gains and friends', 'expenses, sleep, travel abroad and spiritual life'],
        'hi' => ['स्वयं, स्वास्थ्य और आत्मविश्वास', 'परिवार, धन-संचय और वाणी', 'साहस, भाई-बहन और छोटी यात्राएँ', 'घर, माता, संपत्ति और मानसिक शांति',
            'संतान, प्रेम, पढ़ाई और रचनात्मकता', 'दिनचर्या, स्वास्थ्य और प्रतियोगिता', 'विवाह, साझेदारी और व्यापारिक सौदे', 'अचानक बदलाव, शोध और गुप्त विषय',
            'भाग्य, पिता, गुरु और लंबी यात्राएँ', 'करियर, पद और प्रतिष्ठा', 'आय, लाभ और मित्र', 'खर्च, नींद, विदेश यात्रा और अध्यात्म'],
    ];

    private const NAMES = [
        'en' => ['sun' => 'Surya (Sun)', 'moon' => 'Chandra (Moon)', 'mars' => 'Mangal (Mars)', 'mercury' => 'Budh (Mercury)', 'jupiter' => 'Guru (Jupiter)',
            'venus' => 'Shukra (Venus)', 'saturn' => 'Shani (Saturn)', 'rahu' => 'Rahu', 'ketu' => 'Ketu'],
        'hi' => Gochar::HI,
    ];

    /** [favourable, needs care] effect of a planet's transit. */
    private const EFFECT = [
        'en' => [
            'sun' => ['brings recognition, energy and success over rivals', 'can bring fatigue, ego clashes or extra expenses, so keep your words soft'],
            'moon' => ['keeps the mind calm and brings comfort and good news', 'can make the mind restless, so avoid hasty decisions'],
            'mars' => ['gives courage, drive and wins in competition', 'can raise temper and haste, so drive carefully and avoid disputes'],
            'mercury' => ['helps communication, trade, study and smart decisions', 'can cause misunderstandings, so double-check papers and messages'],
            'jupiter' => ['brings growth, blessings, good advice and good fortune', 'slows growth a little, so stay patient and do not over-commit'],
            'venus' => ['brings harmony in relationships, comfort and enjoyment', 'can bring indulgence or sensitivity in relationships, so spend wisely'],
            'saturn' => ['rewards steady hard work with lasting gains', 'brings delays and extra responsibility, where patience and discipline help'],
            'rahu' => ['brings unexpected openings and gains through new contacts', 'can bring confusion or temptation, so avoid shortcuts'],
            'ketu' => ['supports spiritual insight and quiet wins over obstacles', 'can bring detachment or unclear thinking, so keep plans simple'],
        ],
        'hi' => [
            'sun' => ['मान-सम्मान, ऊर्जा और विरोधियों पर विजय देता है', 'थकान, अहं के टकराव या अतिरिक्त खर्च दे सकता है, इसलिए वाणी नरम रखें'],
            'moon' => ['मन को शांत रखता है और सुख व शुभ समाचार देता है', 'मन को चंचल कर सकता है, जल्दबाज़ी के निर्णय न लें'],
            'mars' => ['साहस, उत्साह और प्रतियोगिता में जीत देता है', 'क्रोध और जल्दबाज़ी बढ़ा सकता है, वाहन सावधानी से चलाएँ और विवाद से बचें'],
            'mercury' => ['बातचीत, व्यापार, पढ़ाई और सही निर्णय में मदद करता है', 'गलतफ़हमी दे सकता है, कागज़ात और संदेश दोबारा जाँचें'],
            'jupiter' => ['उन्नति, आशीर्वाद, अच्छी सलाह और सौभाग्य देता है', 'प्रगति थोड़ी धीमी करता है, धैर्य रखें और ज़्यादा वादे न करें'],
            'venus' => ['रिश्तों में मधुरता, सुख-सुविधा और आनंद देता है', 'भोग-विलास या रिश्तों में संवेदनशीलता दे सकता है, खर्च सोच-समझकर करें'],
            'saturn' => ['लगातार मेहनत का स्थायी फल देता है', 'देरी और ज़िम्मेदारी बढ़ाता है, धैर्य और अनुशासन से लाभ होगा'],
            'rahu' => ['अचानक अवसर और नए संपर्कों से लाभ देता है', 'भ्रम या प्रलोभन दे सकता है, शॉर्टकट से बचें'],
            'ketu' => ['आध्यात्मिक समझ और बाधाओं पर शांत विजय देता है', 'विरक्ति या उलझन दे सकता है, योजनाएँ सरल रखें'],
        ],
    ];

    /** Chandra gochar: the day's mood by the Moon's house from your Moon sign (1..12). */
    private const MOON_DAY = [
        'en' => ['The Moon is in your own sign: a comfortable, confident day with good food and good company.', 'The Moon moves through your 2nd house: watch spending and speak gently with family.',
            'The Moon in your 3rd house brings energy and success in efforts; a good day to take the first step.', 'The Moon in your 4th house can make you uneasy at home; rest well and avoid arguments.',
            'The Moon in your 5th house asks for patience with plans and children; avoid speculation.', 'The Moon in your 6th house helps you finish pending work and get past rivals; health improves.',
            'The Moon in your 7th house favours your partner, meetings and pleasant travel.', 'The Moon in your 8th house can bring small worries; avoid risks and keep the day simple.',
            'The Moon in your 9th house may slow luck a little; pray, help your elders and stay steady.', 'The Moon in your 10th house supports work and recognition; complete important tasks.',
            'The Moon in your 11th house brings gains, help from friends and good news.', 'The Moon in your 12th house may raise expenses or tiredness; sleep on time and meditate.'],
        'hi' => ['चंद्रमा आपकी ही राशि में है: सुखद, आत्मविश्वास भरा दिन, अच्छा भोजन और अच्छी संगत।', 'चंद्रमा दूसरे भाव में है: खर्च पर नज़र रखें और परिवार से मधुर बोलें।',
            'तीसरे भाव का चंद्रमा उत्साह और प्रयासों में सफलता देता है; नई शुरुआत के लिए अच्छा दिन।', 'चौथे भाव का चंद्रमा घर में बेचैनी दे सकता है; आराम करें और बहस से बचें।',
            'पाँचवें भाव का चंद्रमा योजनाओं और संतान के विषय में धैर्य माँगता है; सट्टे से बचें।', 'छठे भाव का चंद्रमा अधूरे काम पूरे कराता है और विरोधियों पर जीत देता है; स्वास्थ्य सुधरेगा।',
            'सातवें भाव का चंद्रमा जीवनसाथी, मुलाक़ात और सुखद यात्रा के लिए अच्छा है।', 'आठवें भाव का चंद्रमा छोटी चिंताएँ दे सकता है; जोखिम न लें और दिन सरल रखें।',
            'नौवें भाव का चंद्रमा भाग्य को थोड़ा धीमा कर सकता है; प्रार्थना करें और बड़ों की सेवा करें।', 'दसवें भाव का चंद्रमा काम और सम्मान में सहायक है; ज़रूरी काम पूरे करें।',
            'ग्यारहवें भाव का चंद्रमा लाभ, मित्रों की मदद और शुभ समाचार देता है।', 'बारहवें भाव का चंद्रमा खर्च या थकान बढ़ा सकता है; समय पर सोएँ और ध्यान करें।'],
    ];

    private const REMEDY = [
        'en' => ['sun' => 'offer water to the rising Sun and chant "Om Suryaya Namah"', 'moon' => 'offer milk or water on a Shivling and chant "Om Somaya Namah"',
            'mars' => 'recite the Hanuman Chalisa and donate red lentils (masoor)', 'mercury' => 'feed green grass to a cow and chant "Om Budhaya Namah"',
            'jupiter' => 'apply a turmeric tilak and donate chana dal on Thursday', 'venus' => 'donate white sweets or curd and chant "Om Shukraya Namah"',
            'saturn' => 'light a mustard-oil lamp under a peepal tree on Saturday and chant "Om Shanaishcharaya Namah"', 'rahu' => 'chant "Om Rahave Namah" and donate a blanket',
            'ketu' => 'worship Ganesha and feed a dog'],
        'hi' => ['sun' => 'उगते सूर्य को जल चढ़ाएँ और "ॐ सूर्याय नमः" का जप करें', 'moon' => 'शिवलिंग पर दूध या जल चढ़ाएँ और "ॐ सोमाय नमः" का जप करें',
            'mars' => 'हनुमान चालीसा पढ़ें और मसूर दाल का दान करें', 'mercury' => 'गाय को हरी घास खिलाएँ और "ॐ बुधाय नमः" का जप करें',
            'jupiter' => 'हल्दी का तिलक लगाएँ और गुरुवार को चने की दाल दान करें', 'venus' => 'सफ़ेद मिठाई या दही दान करें और "ॐ शुक्राय नमः" का जप करें',
            'saturn' => 'शनिवार को पीपल के नीचे सरसों के तेल का दीपक जलाएँ और "ॐ शनैश्चराय नमः" का जप करें', 'rahu' => '"ॐ राहवे नमः" का जप करें और कंबल दान करें',
            'ketu' => 'गणेश जी की पूजा करें और कुत्ते को भोजन दें'],
    ];

    private const COLOUR = [
        'en' => ['sun' => 'orange', 'moon' => 'white', 'mars' => 'red', 'mercury' => 'green', 'jupiter' => 'yellow', 'venus' => 'pink', 'saturn' => 'dark blue', 'rahu' => 'grey', 'ketu' => 'brown'],
        'hi' => ['sun' => 'नारंगी', 'moon' => 'सफ़ेद', 'mars' => 'लाल', 'mercury' => 'हरा', 'jupiter' => 'पीला', 'venus' => 'गुलाबी', 'saturn' => 'गहरा नीला', 'rahu' => 'स्लेटी', 'ketu' => 'भूरा'],
    ];

    /** Numerology number of each planet (1 Sun … 9 Mars). */
    private const NUMBER = ['sun' => 1, 'moon' => 2, 'jupiter' => 3, 'rahu' => 4, 'mercury' => 5, 'venus' => 6, 'ketu' => 7, 'saturn' => 8, 'mars' => 9];

    /** Planets and houses that matter most for each topic. */
    private const TOPIC = [
        'love' => [['venus', 'moon', 'mars', 'jupiter'], [5, 7]],
        'career' => [['sun', 'saturn', 'mercury', 'mars', 'jupiter'], [10, 6]],
        'finance' => [['jupiter', 'venus', 'mercury', 'saturn'], [2, 11]],
        'health' => [['sun', 'mars', 'saturn', 'moon'], [1, 6, 8, 12]],
        'education' => [['mercury', 'jupiter', 'sun'], [4, 5, 9]],
    ];

    private const TOPIC_TIP = [
        'en' => [
            'love' => ['Plan time together and listen more than you speak.', 'Small gestures of care will mean more than big promises.', 'Singles may meet someone through friends or family.'],
            'career' => ['Finish what is pending before starting something new.', 'Put important proposals in writing.', 'Seniors notice steady, quiet effort now.'],
            'finance' => ['Keep a simple budget and avoid lending large sums.', 'Prefer safe savings over quick-profit schemes.', 'Clear small dues to keep the mind light.'],
            'health' => ['Sleep on time and drink enough water.', 'A short morning walk or pranayama will help.', 'Do not ignore small aches; see a doctor if needed.'],
            'education' => ['Revise daily in short sessions.', 'Ask teachers for help without hesitation.', 'A fixed study time will bring results.'],
        ],
        'hi' => [
            'love' => ['साथ समय बिताएँ और बोलने से ज़्यादा सुनें।', 'छोटी-छोटी परवाह बड़े वादों से ज़्यादा असर करेगी।', 'अविवाहितों को मित्रों या परिवार के माध्यम से कोई पसंद आ सकता है।'],
            'career' => ['नया शुरू करने से पहले अधूरे काम पूरे करें।', 'ज़रूरी प्रस्ताव लिखित में दें।', 'वरिष्ठ लोग आपकी लगातार मेहनत पर ध्यान देंगे।'],
            'finance' => ['सरल बजट बनाएँ और बड़ी रकम उधार न दें।', 'जल्दी मुनाफ़े वाली योजनाओं से बेहतर सुरक्षित बचत है।', 'छोटे बकाया चुका दें, मन हल्का रहेगा।'],
            'health' => ['समय पर सोएँ और पर्याप्त पानी पिएँ।', 'सुबह की सैर या प्राणायाम लाभ देगा।', 'छोटी तकलीफ़ को अनदेखा न करें; ज़रूरत हो तो डॉक्टर से मिलें।'],
            'education' => ['रोज़ थोड़े-थोड़े समय में दोहराएँ।', 'शिक्षकों से बेझिझक मदद लें।', 'पढ़ाई का निश्चित समय परिणाम देगा।'],
        ],
    ];

    private const DAY_TIP = [
        'en' => ['Start the morning with a few minutes of prayer or silence.', 'Speak politely at work; a kind word opens doors today.', 'Keep receipts and papers in order.',
            'Call an elder in the family and take their blessings.', 'Avoid lending or borrowing money today.', 'A short walk in the evening will clear the mind.',
            'Give something to someone in need, even if small.', 'Finish one pending task before lunch.', 'Eat light and on time.'],
        'hi' => ['सुबह कुछ मिनट प्रार्थना या मौन से शुरू करें।', 'कार्यस्थल पर विनम्र रहें; आज एक मीठा शब्द रास्ते खोलेगा।', 'रसीदें और कागज़ात व्यवस्थित रखें।',
            'परिवार के किसी बड़े को फ़ोन करें और आशीर्वाद लें।', 'आज पैसे उधार देने या लेने से बचें।', 'शाम की छोटी सैर मन को साफ़ करेगी।',
            'किसी ज़रूरतमंद को कुछ दें, चाहे थोड़ा ही हो।', 'दोपहर से पहले एक अधूरा काम पूरा करें।', 'हल्का और समय पर भोजन करें।'],
    ];

    private const TARA = [
        ['Janma', 'a day of your own birth star: mixed results, so look after health and avoid big risks'],
        ['Sampat', 'a star of wealth: good for money matters, purchases and new work'],
        ['Vipat', 'a star of obstacles: move slowly, avoid travel and quarrels'],
        ['Kshema', 'a star of well-being: comfort, safety and steady progress'],
        ['Pratyari', 'a star of opposition: expect resistance and keep plans flexible'],
        ['Sadhaka', 'a star of achievement: efforts succeed and work gets done'],
        ['Vadha', 'a difficult star: postpone important starts and stay careful'],
        ['Mitra', 'a friendly star: help from friends and pleasant meetings'],
        ['Param Mitra', 'a very friendly star: one of the best days for any good work'],
    ];

    /** Natural friendships (Parashari) used for numerology; Rahu as Saturn, Ketu as Mars. */
    private const FRIENDS = ['sun' => ['moon', 'mars', 'jupiter'], 'moon' => ['sun', 'mercury'], 'mars' => ['sun', 'moon', 'jupiter'], 'mercury' => ['sun', 'venus'],
        'jupiter' => ['sun', 'moon', 'mars'], 'venus' => ['mercury', 'saturn'], 'saturn' => ['mercury', 'venus']];

    private const ENEMIES = ['sun' => ['venus', 'saturn'], 'moon' => [], 'mars' => ['mercury'], 'mercury' => ['moon'], 'jupiter' => ['mercury', 'venus'],
        'venus' => ['sun', 'moon'], 'saturn' => ['sun', 'moon', 'mars']];

    private const UNIVERSAL = [1 => 'new beginnings and leadership', 2 => 'cooperation, patience and partnership', 3 => 'expression, learning and joy',
        4 => 'hard work, order and building foundations', 5 => 'change, travel and fresh ideas', 6 => 'family, responsibility and care',
        7 => 'reflection, study and inner growth', 8 => 'money, power and practical results', 9 => 'completion, generosity and letting go'];

    private const MULANK = [1 => 'a natural leader who likes to start things', 2 => 'gentle, intuitive and caring', 3 => 'wise, disciplined and fond of learning',
        4 => 'hard-working, original and practical', 5 => 'quick, clever and fond of change', 6 => 'loving, artistic and family-minded',
        7 => 'thoughtful, spiritual and private', 8 => 'patient, serious and ambitious', 9 => 'brave, energetic and generous'];

    private const MAJOR_ARCANA = [
        ['The Fool', 'a fresh start; be open to new experiences but look before you leap'], ['The Magician', 'skill and willpower; you have the tools to make plans real'],
        ['The High Priestess', 'intuition; listen to your inner voice and keep some things private'], ['The Empress', 'growth and comfort; nurture your home, health and creativity'],
        ['The Emperor', 'structure and authority; organise and take responsibility'], ['The Hierophant', 'tradition and guidance; learn from a teacher or elder'],
        ['The Lovers', 'choices of the heart; act on your values in relationships'], ['The Chariot', 'determination; steady focus brings progress'],
        ['Strength', 'quiet courage; patience wins over force'], ['The Hermit', 'reflection; step back, study and find your answer within'],
        ['Wheel of Fortune', 'change and turning luck; adapt and take the opening'], ['Justice', 'fairness; honest dealing and clear papers bring balance'],
        ['The Hanged Man', 'pause; seeing things differently helps more than pushing'], ['Death', 'an ending that makes room for something new'],
        ['Temperance', 'balance and moderation; blend patience with effort'], ['The Devil', 'attachments; break a habit that holds you back'],
        ['The Tower', 'sudden change; let go of what is not stable'], ['The Star', 'hope and healing; keep faith in your plans'],
        ['The Moon', 'uncertainty; check facts before you decide'], ['The Sun', 'success and joy; a bright and confident period'],
        ['Judgement', 'awakening; review the past and answer a calling'], ['The World', 'completion; a cycle closes with achievement'],
    ];

    private const CHINESE_TRAITS = ['clever and resourceful', 'steady and dependable', 'brave and confident', 'gentle and diplomatic', 'ambitious and energetic', 'wise and graceful',
        'free-spirited and active', 'kind and artistic', 'witty and inventive', 'hard-working and honest', 'loyal and fair', 'generous and warm'];

    private const ELEMENT_TONE = ['fire' => 'Act with energy but keep your temper cool.', 'earth' => 'Build slowly and practically.', 'air' => 'Talk things through and stay curious.', 'water' => 'Trust your feelings but protect your peace.'];

    public function __construct(private Gochar $gochar) {}

    /** Fills every missing reading for the current (and next) period. Returns the number written. */
    public function ensureCurrent(?CarbonImmutable $today = null): int
    {
        $today ??= CarbonImmutable::now('Asia/Kolkata')->startOfDay();
        $n = 0;
        foreach (self::PLAN as $kind => $periods) {
            foreach (array_keys($periods) as $period) {
                $start = RashifalWriter::periodStart($period, $today);
                foreach ($period === 'daily' ? [$start, $start->addDay()] : [$start] as $s) {
                    $n += $this->ensure($kind, $period, $s);
                }
            }
        }

        return $n;
    }

    /**
     * Called by the pages and the API before reading: makes sure the period containing today has
     * a reading (checked at most every 10 minutes per kind and period). Never throws.
     */
    public static function fillToday(string $kind, ?string $period = null): void
    {
        $today = CarbonImmutable::now('Asia/Kolkata')->startOfDay();
        foreach ($period ? [$period] : array_keys(self::PLAN[$kind] ?? []) as $p) {
            if (! isset(self::PLAN[$kind][$p]) || ! Cache::add("gochar-rashifal:filled:$kind:$p:".$today->toDateString(), 1, 600)) {
                continue;
            }
            try {
                app(self::class)->ensure($kind, $p, RashifalWriter::periodStart($p, $today));
            } catch (Throwable $e) {
                report($e);
            }
        }
    }

    /** Writes the readings of one kind and period (every topic and language) that are still missing. */
    public function ensure(string $kind, string $period, CarbonImmutable $start): int
    {
        $topics = self::PLAN[$kind][$period] ?? null;
        if ($topics === null) {
            return 0;
        }
        $n = 0;
        foreach ($topics as $topic) {
            foreach ($this->locales($kind) as $locale) {
                $have = DB::table('horoscopes')->where(compact('kind', 'topic', 'period', 'locale'))->where('starts_on', $start->toDateString())->pluck('sign')->all();
                $missing = array_diff(array_keys(HoroscopeTypes::subjects($kind, 'en')), $have);
                if ($missing === []) {
                    continue;
                }
                try {
                    $n += $this->write($kind, $topic, $period, $start, $locale, $missing);
                } catch (Throwable $e) {
                    report($e); // engine not built: the page shows its normal "not published" state

                    return $n;
                }
            }
        }

        return $n;
    }

    /** Languages written for a kind: Hindi only for Moon-sign Rashifal (the other kinds are English). */
    private function locales(string $kind): array
    {
        $enabled = config('locales.enabled', ['en']) ?: ['en'];
        $out = array_values(array_intersect(['en', 'hi'], $enabled)) ?: ['en'];

        return $kind === 'rashi' ? $out : array_values(array_intersect($out, ['en']));
    }

    private function write(string $kind, string $topic, string $period, CarbonImmutable $start, string $locale, array $signs): int
    {
        $end = HoroscopeTypes::endOf($period, $start);
        $n = 0;
        foreach ($signs as $s) {
            $body = match ($kind) {
                'rashi' => $this->rashi($s, $topic, $period, $start, $end, $locale),
                'nakshatra' => $this->nakshatra($s, $start),
                'numerology' => $this->numerology($s + 1, $start),
                'tarot' => $this->tarot($s, $period, $start),
                'chinese' => $this->chinese($s, $start),
            };
            $n += DB::table('horoscopes')->insertOrIgnore([
                'kind' => $kind, 'topic' => $topic, 'period' => $period, 'sign' => $s, 'starts_on' => $start->toDateString(), 'ends_on' => $end->toDateString(),
                'locale' => $locale, 'body' => $body, 'status' => 'published', 'source' => self::SOURCE, 'author_id' => null,
                'published_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $n;
    }

    /** Positions at 6 AM IST on a day, cached. */
    private function sky(CarbonImmutable $day): array
    {
        return Cache::remember('gochar-rashifal:sky:'.$day->toDateString(), now()->addDays(2), fn () => $this->gochar->now(new DateTimeImmutable($day->format('Y-m-d').' 06:00', new DateTimeZone('Asia/Kolkata'))));
    }

    private static function house(int $planetSign, int $moonSign): int
    {
        return ($planetSign - $moonSign + 12) % 12 + 1;
    }

    private function line(string $p, int $house, string $l): string
    {
        $good = in_array($house, Gochar::GOOD_HOUSES[$p], true);
        $name = self::NAMES[$l][$p];
        $theme = self::HOUSE[$l][$house - 1];

        return $l === 'hi'
            ? "{$name} आपकी राशि से ".self::ORD_HI[$house - 1]." भाव ({$theme}) में है और ".self::EFFECT[$l][$p][$good ? 0 : 1].'।'
            : "{$name} transits your ".self::ORD[$house - 1]." house ({$theme}) and ".self::EFFECT[$l][$p][$good ? 0 : 1].'.';
    }

    private function pick(array $list, string $seed): string
    {
        return $list[crc32($seed) % count($list)];
    }

    private function rashi(int $s, string $topic, string $period, CarbonImmutable $start, CarbonImmutable $end, string $l): string
    {
        $sky = $this->sky($start);
        $h = array_map(fn ($x) => self::house($x['sign'], $s), $sky);
        $good = fn ($p) => in_array($h[$p], Gochar::GOOD_HOUSES[$p], true);
        $parts = [];

        if ($topic === 'general') {
            if ($period === 'daily') {
                $parts[] = self::MOON_DAY[$l][$h['moon'] - 1];
                $fast = ['sun', 'mercury', 'venus', 'mars'];
            } elseif (in_array($period, ['weekly', 'monthly'], true)) {
                $fast = ['sun', 'mercury', 'venus', 'mars', 'jupiter'];
            } else {
                $fast = ['jupiter', 'rahu', 'ketu', 'mars']; // Saturn gets its own note below
            }
            $helpers = array_values(array_filter($fast, $good));
            $care = array_values(array_filter($fast, fn ($p) => ! $good($p)));
            foreach (array_slice($helpers, 0, 2) as $p) {
                $parts[] = $this->line($p, $h[$p], $l);
            }
            foreach (array_slice($care, 0, $period === 'daily' ? 1 : 2) as $p) {
                $parts[] = $this->line($p, $h[$p], $l);
            }
            if (! in_array($period, ['daily', 'weekly'], true) || in_array($h['saturn'], [12, 1, 2, 4, 8], true)) {
                $parts[] = $this->saturnNote($h['saturn'], $l);
            }
            if ($period !== 'daily') {
                $parts = array_merge($parts, $this->events($s, $start, $end, $l, $period));
            }
            if ($period === 'daily') {
                $parts[] = $this->pick(self::DAY_TIP[$l], "$s|".$start->toDateString());
            }
            $score = count($helpers) - count($care);
            $parts[] = $l === 'hi'
                ? ($score > 0 ? 'कुल मिलाकर यह समय आपके पक्ष में है।' : ($score < 0 ? 'कुल मिलाकर धैर्य और सावधानी से चलने का समय है।' : 'कुल मिलाकर मिला-जुला समय है; संतुलन बनाए रखें।'))
                : ($score > 0 ? 'Overall the period works in your favour.' : ($score < 0 ? 'Overall it is a time to move with patience and care.' : 'Overall a mixed period; keep your balance.'));
        } else {
            [$planets, $houses] = self::TOPIC[$topic];
            foreach ($planets as $p) {
                if (in_array($h[$p], $houses, true) || in_array($p, array_slice($planets, 0, 2), true)) {
                    $parts[] = $this->line($p, $h[$p], $l);
                }
            }
            $parts = array_slice(array_values(array_unique($parts)), 0, 3);
            $parts[] = $this->pick(self::TOPIC_TIP[$l][$topic], "$s|$topic|$period|".$start->toDateString());
        }

        $weak = collect(['saturn', 'jupiter', 'mars', 'sun', 'rahu', 'mercury', 'venus', 'moon', 'ketu'])->first(fn ($p) => ! $good($p)) ?? Vedic::SIGN_LORDS[$s];
        $strong = collect(['jupiter', 'venus', 'moon', 'mercury', 'sun', 'mars', 'saturn'])->first($good) ?? Vedic::SIGN_LORDS[$s];
        $lord = Vedic::SIGN_LORDS[$s];
        if ($period === 'daily') {
            // Daily: the planet of the weekday guides the remedy; the Moon's nakshatra lord the number.
            $weak = ['sun', 'moon', 'mars', 'mercury', 'jupiter', 'venus', 'saturn'][(int) $start->dayOfWeek];
            $lord = Vedic::DASHA_ORDER[(int) array_search($sky['moon']['nakshatra'], Vedic::NAKSHATRAS, true) % 9];
        }
        $parts[] = $l === 'hi'
            ? 'उपाय: '.self::REMEDY[$l][$weak].'। शुभ रंग: '.self::COLOUR[$l][$strong].'; शुभ अंक: '.self::NUMBER[$lord].'।'
            : 'Remedy: '.self::REMEDY[$l][$weak].'. Lucky colour: '.self::COLOUR[$l][$strong].'; lucky number: '.self::NUMBER[$lord].'.';

        return implode(' ', $parts);
    }

    private function saturnNote(int $house, string $l): string
    {
        $hi = $l === 'hi';

        return match (true) {
            $house === 12 => $hi ? 'शनि बारहवें भाव में है: साढ़ेसाती का पहला चरण चल रहा है, खर्च और नींद का ध्यान रखें।' : 'Saturn is in your 12th house: the first phase of Sade Sati. Watch expenses and sleep.',
            $house === 1 => $hi ? 'शनि आपकी राशि में है: साढ़ेसाती का मध्य चरण, मेहनत और धैर्य से स्थिरता आएगी।' : 'Saturn is in your own sign: the middle phase of Sade Sati. Hard work and patience bring stability.',
            $house === 2 => $hi ? 'शनि दूसरे भाव में है: साढ़ेसाती का अंतिम चरण, धन और वाणी में संयम रखें।' : 'Saturn is in your 2nd house: the last phase of Sade Sati. Be careful with money and words.',
            in_array($house, [4, 8], true) => $hi ? 'शनि की ढैय्या चल रही है: काम में देरी हो सकती है, शांत और अनुशासित रहें।' : 'Saturn\'s Dhaiya (Kantaka Shani) is running: expect some delays and stay calm and disciplined.',
            in_array($house, [3, 6, 11], true) => $hi ? 'शनि शुभ भाव में है: लगातार प्रयास का अच्छा फल मिलेगा।' : 'Saturn sits in a favourable house for you: steady effort is well rewarded.',
            default => $hi ? 'शनि सामान्य फल देगा: समय पर काम और नियमित दिनचर्या रखें।' : 'Saturn gives average results now: keep to deadlines and a regular routine.',
        };
    }

    /** Sign changes and stations of the main planets during the period, counted from the Moon sign. */
    private function events(int $s, CarbonImmutable $start, CarbonImmutable $end, string $l, string $period): array
    {
        $tz = new DateTimeZone('Asia/Kolkata');
        $planets = in_array($period, ['weekly', 'monthly'], true) ? ['sun', 'mars', 'mercury', 'venus', 'jupiter', 'saturn'] : ['jupiter', 'saturn', 'rahu', 'mars'];
        $out = [];
        foreach ($planets as $p) {
            $key = 'gochar-rashifal:ev:'.$p.':'.$start->toDateString().':'.$end->toDateString();
            $events = Cache::remember($key, now()->addDays(3), fn () => $this->gochar->events($p, new DateTimeImmutable($start->format('Y-m-d').' 00:00', $tz), new DateTimeImmutable($end->format('Y-m-d').' 23:59', $tz), 'Asia/Kolkata'));
            foreach ($events as $e) {
                if ($e['type'] !== 'ingress' || $p === 'ketu') {
                    continue;
                }
                $house = self::house($e['sign'], $s);
                $sign = $l === 'hi' ? ContentController::SIGNS_HI[$e['sign']] : strtok(Vedic::SIGNS[$e['sign']], ' ');
                $out[] = $l === 'hi'
                    ? $e['at']->format('d/m').' को '.self::NAMES[$l][$p]." {$sign} में प्रवेश करेगा, यानी आपके ".self::ORD_HI[$house - 1].' भाव में।'
                    : 'On '.$e['at']->format('d M').' '.self::NAMES[$l][$p]." enters {$sign}, your ".self::ORD[$house - 1].' house ('.self::HOUSE[$l][$house - 1].').';
            }
        }

        return array_slice($out, 0, 3);
    }

    private function nakshatra(int $birth, CarbonImmutable $day): string
    {
        $sky = $this->sky($day);
        $moonNak = (int) array_search($sky['moon']['nakshatra'], Vedic::NAKSHATRAS, true);
        $tara = (($moonNak - $birth + 27) % 27) % 9;
        [$name, $meaning] = self::TARA[$tara];
        $lord = Vedic::DASHA_ORDER[$birth % 9];
        $good = in_array($tara, [1, 3, 5, 7, 8], true);

        return "Today the Moon is in {$sky['moon']['nakshatra']} nakshatra, which is {$name} Tara for you: {$meaning}. "
            .'Your birth star is ruled by '.self::NAMES['en'][$lord].', '.($good ? 'so use the day for things that matter.' : 'so keep the day light and avoid fresh commitments.')
            .' Remedy: '.self::REMEDY['en'][$lord].'. Lucky colour: '.self::COLOUR['en'][$lord].'.';
    }

    private static function reduce(int $n): int
    {
        while ($n > 9) {
            $n = array_sum(str_split((string) $n));
        }

        return max(1, $n);
    }

    private function numerology(int $mulank, CarbonImmutable $month): string
    {
        $universal = self::reduce(array_sum(str_split($month->format('Y'))) + $month->month);
        $planet = array_search($mulank, self::NUMBER, true);
        $monthPlanet = array_search($universal, self::NUMBER, true);
        $base = ['rahu' => 'saturn', 'ketu' => 'mars'];
        $a = $base[$planet] ?? $planet;
        $b = $base[$monthPlanet] ?? $monthPlanet;
        $rel = $a === $b || in_array($b, self::FRIENDS[$a], true) ? 'friendly' : (in_array($b, self::ENEMIES[$a], true) ? 'testing' : 'neutral');
        $tone = ['friendly' => 'This vibration supports you, so it is a good month to push your plans forward.', 'neutral' => 'The month is steady: results come in proportion to effort.',
            'testing' => 'The two energies pull in different directions, so go slowly with big decisions and keep your promises.'][$rel];

        return 'As a Mulank '.$mulank.' person ruled by '.self::NAMES['en'][$planet].', you are '.self::MULANK[$mulank].'. '
            .$month->format('F Y').' is a universal month '.$universal.', a time of '.self::UNIVERSAL[$universal].'. '.$tone
            .' Favourable dates: '.implode(', ', array_filter([$mulank, $mulank + 9, $mulank + 18, $mulank + 27], fn ($d) => $d <= $month->daysInMonth)).'.'
            .' Remedy: '.self::REMEDY['en'][$planet].'. Lucky colour: '.self::COLOUR['en'][$planet].'.';
    }

    private function tarot(int $s, string $period, CarbonImmutable $start): string
    {
        [$card, $meaning] = self::MAJOR_ARCANA[crc32("tarot|$s|$period|".$start->toDateString()) % 22];
        $element = ['fire', 'earth', 'air', 'water'][$s % 4];
        $span = $period === 'weekly' ? 'this week' : 'this year';

        return 'Card drawn for '.strtok(Vedic::SIGNS[$s], ' ')." {$span}: {$card}. It speaks of {$meaning}. "
            .self::ELEMENT_TONE[$element].' Keep the card in mind when a choice comes up, and act on what feels honest and practical.';
    }

    private function chinese(int $a, CarbonImmutable $year): string
    {
        $y = HoroscopeTypes::chineseAnimal($year->year);
        $names = HoroscopeTypes::CHINESE;
        $trine = $a % 4 === $y % 4;
        $harmony = ($a + $y) % 12 === 1;
        $clash = ($a + 6) % 12 === $y;
        $verdict = match (true) {
            $a === $y => 'This is your own year (Ben Ming Nian). Tradition says to move carefully, avoid big risks and wear a little red for luck.',
            $harmony => "The {$names[$y]} is your secret friend in the Chinese zodiac, so this is one of your best years for support, partnerships and growth.",
            $trine => "You and the {$names[$y]} belong to the same harmony group, so the year brings cooperation and good chances.",
            $clash => "Your sign sits opposite the {$names[$y]}, the classic clash. Expect changes, stay flexible and avoid hasty moves.",
            default => "The {$names[$y]} year is neutral for you: steady work and good habits decide the results.",
        };

        return "{$year->year} is the year of the {$names[$y]}. People born in the year of the {$names[$a]} are ".self::CHINESE_TRAITS[$a].'. '.$verdict;
    }
}
