<?php

// Fuller pages for the services that still had short text (6 Nov 2026).

$c = [];

$c['yearly-horoscope-report'] = ['body' => "A personal horoscope for the whole calendar year, prepared from your own birth chart rather than a general sun-sign column printed for millions of people. The report starts from your Moon sign and Lagna and follows the year's important transits — Saturn, Jupiter, Rahu and Ketu, and the faster movements of Mars and Venus — through the houses of your chart.\n\n"
    ."These transits are read together with the Vimshottari dasha you are running, because the same transit gives different results in different dashas. You get a month-by-month guide to career and work, money and investments, family and relationships, health and travel, with the months that support new beginnings and the months that ask for patience.\n\n"
    .'Each difficult period comes with a simple remedy — a mantra, a day of fasting, a donation or a colour to favour. It is an affordable planner to keep on your phone and look at each month.',
    'benefits' => ['Personal, from your own chart', 'Month-by-month planner for the year', 'Good and careful months marked', 'Simple remedy for each difficult period']];

$c['love-report'] = ['body' => "Love and relationships are read in Jyotish from several parts of the chart together: the 5th house of romance and affection, the 7th house of partnership and marriage, Venus (the karaka of love), Mars (attraction and passion), the Moon (emotional needs) and the Navamsa (D-9) chart, which shows the deeper nature of marriage.\n\n"
    ."This report explains how you love and what you need from a partner, the qualities of the partner who suits you, any yogas for love marriage or delay in marriage, Mangal dosh and how strong it really is, and the periods in your dasha and transits when relationships are most likely to begin or deepen.\n\n"
    .'If you are already in a relationship, it shows the areas that need care and gives practical, simple remedies. For a full match with a partner, choose the Couple Kundli Compatibility report.',
    'benefits' => ['Understand your love nature and needs', 'The kind of partner who suits you', 'Timing of love and marriage', 'Honest view of Mangal dosh']];

$c['fortune-report'] = ['body' => "The Fortune report is a short, clear outlook for the next three years. It is made for people who want to know what is coming without reading a long kundli.\n\n"
    ."Acharya Ji's team studies the Mahadasha and Antardasha you are running now and the one that begins next, the transits of Saturn, Jupiter and Rahu–Ketu over your chart, and the strength of your 9th house (fortune) and 11th house (gains). From this the report describes the main theme of each year for career, money, relationships and health.\n\n"
    .'It marks the best periods for big decisions — a job change, marriage, a property purchase or a new venture — and the periods to go slowly, with one simple remedy for each.',
    'benefits' => ['Three-year outlook in a few pages', 'Best periods for big decisions', 'Remedy for each slow period', 'Affordable starting point']];

$c['fortune-plus-report'] = ['body' => "Fortune Plus is the detailed version of the Fortune report and covers five years, year by year.\n\n"
    ."For each year it combines three things: the dasha and antardasha running, the Varshphal (Tajik annual chart) theme for that year, and the major transits of Saturn, Jupiter and Rahu–Ketu. It marks Sade Sati and Dhaiya periods if they fall in these years, explains what they mean for you rather than in general, and lists the months that are strongest for career, money and family matters.\n\n"
    .'Every difficult period comes with a detailed remedy — mantra and count, daan, fasting and, where the chart supports it, a gemstone. It is a good choice before planning a business, higher studies abroad or a marriage.',
    'benefits' => ['Five years, year by year', 'Varshphal theme for each year', 'Sade Sati and Dhaiya marked', 'Detailed remedies for difficult periods']];

$c['jupiter-transit-report'] = ['body' => "Jupiter (Guru) is the great benefic of Jyotish. It changes sign about once a year, and wherever it moves it brings growth, wisdom, children, wealth and good advice. Its aspects on the 5th, 7th and 9th houses from its position spread that blessing further.\n\n"
    ."This report shows the house Jupiter is transiting from your Lagna and from your Moon sign, its aspects on your natal planets, the classical verdict (favourable houses 2, 5, 7, 9 and 11 from the Moon), and any retrograde period during the year.\n\n"
    .'You learn which areas of life will grow this year and the best months to act on them. If Jupiter is weak or troubled in your birth chart, simple remedies — Thursday puja, chana dal and turmeric daan, the Guru mantra — are given.',
    'benefits' => ['Know where growth comes this year', 'Best months to act', 'Retrograde period explained', 'Remedies for a weak Jupiter']];

$c['saturn-in-birth-chart-report'] = ['body' => "Saturn (Shani) is the planet of karma, discipline, patience and time. It rewards honest hard work and slows down short cuts. Its place in your birth chart shapes your career, responsibilities, health in later life and the main lessons of your life.\n\n"
    ."The report explains your natal Saturn — its sign and house, whether it is exalted, own-sign, debilitated or retrograde, its aspects on the 3rd, 7th and 10th houses from it, and yogas such as Shasha (Pancha Mahapurusha) — and what that means for you in practice.\n\n"
    .'It lists your Saturn Mahadasha and Antardasha periods, current Sade Sati or Dhaiya, and gives practical remedies: Shani mantra and Hanuman Chalisa, Saturday daan of black til and mustard oil, service to the elderly and workers, and a Blue Sapphire only if your chart truly supports it.',
    'benefits' => ['Understand Saturn\'s lessons for you', 'Saturn dasha periods explained', 'Sade Sati and Dhaiya checked', 'Practical, safe remedies']];

$bundle = fn (string $intro, string $who) => $intro."\n\nEach report in the bundle is prepared separately by Acharya Ji's team from your chart and delivered as its own PDF in your account, so you can read them one at a time. Buying them together costs less than buying each one.\n\n$who";
$c['career-destiny-bundle'] = ['body' => $bundle('The Career Destiny bundle brings together three reports for people planning their working life: the Career report (job or business, suitable fields, timing of growth), the Dhanyog / Finance report (earning style, wealth yogas, strong and weak periods for money) and the Fortune report (the three-year outlook).', 'It is well suited to students choosing a stream, professionals thinking of a change, and anyone starting a business.'),
    'benefits' => ['Career, money and timing together', 'Three reports for less', 'Clear plan for the next years']];
$c['kundli-suraksha-pack'] = ['body' => $bundle('Kundli Suraksha is a protective set that checks the difficult parts of a chart and gives remedies for each: the Kaal Sarp and Manglik Dosh report, the Sade Sati report and the Saturn in Birth Chart report.', 'It is useful for anyone who has been told they have a dosha and wants an honest second view, and before marriage or a big decision.'),
    'benefits' => ['All major doshas checked honestly', 'Saturn periods mapped', 'Remedies only where needed']];
$c['love-compatibility-kit'] = ['body' => $bundle('The Love Compatibility kit combines your own Love report with the Couple Kundli Compatibility report for you and your partner (Ashtakoota, Mangal, Nadi and Bhakoot dosh, 7th house and Navamsa).', 'It is made for couples planning a love marriage and for families who want a full picture before deciding.'),
    'benefits' => ['Your love nature plus the full match', 'Both birth charts studied', 'Wedding timing included']];
$c['seasonal-guidance-package'] = ['body' => $bundle('Seasonal guidance gives you the two transit reports that matter most for the coming months: the Jupiter Transit report (where growth comes) and the Rahu–Ketu Transit report (sudden changes, foreign matters and confusion to watch for).', 'It is a good yearly check-up for people who already have their kundli read.'),
    'benefits' => ['The two key transits together', 'Opportunities and cautions', 'Remedies for the season']];
$c['navratri-guidance-package'] = ['body' => $bundle('The Navratri package is prepared for the nine nights of the Divine Mother. It contains your Yearly Horoscope report and a personal Navratri sadhana guide: the form of Maa Durga to focus on for your chart, a mantra with its count for each day, the colour of each day and simple offerings.', 'It is sent before Navratri begins so you can follow it from Ghatasthapana to Navami.'),
    'benefits' => ['Personal mantra for the nine nights', 'Day-by-day colours and offerings', 'Yearly horoscope included']];

$puja = fn (string $about, string $how) => $about."\n\n".$how."\n\nAcharya Dr. Rajnath Jha and the priests of Jyotirveda Vigyan Sansthan perform the puja with the complete vidhi in your name and gotra. You can watch the live link where possible; photos are added to your account, and prasad is sent with the Family package.";
$c['durga-ashtami-puja'] = ['body' => $puja('Durga Ashtami, the eighth day of Sharad and Chaitra Navratri, is dedicated to Maa Mahagauri, the eighth form of Durga — pure, calm and granting. It is one of the most powerful days of the year to worship the Divine Mother for protection, the removal of obstacles and peace in the family.', 'The day\'s worship includes Durga puja with red chunri and shringar, selected chapters of the Durga Saptashati, the Sandhi puja at the meeting moment of Ashtami and Navami with lamps or lotuses, kanya pujan of young girls as forms of the Devi, and a havan.'),
    'benefits' => ['Blessings of Maa Durga on her holiest day', 'Protection and strength', 'Removes obstacles', 'Peace in the family']];
$c['dev-diwali-sarva-devta-puja'] = ['body' => $puja('Dev Diwali falls on Kartik Purnima, fifteen days after Diwali. Tradition says the gods themselves come down to the Ganga to celebrate Lord Shiva\'s victory over Tripurasura, and the ghats are lit with lakhs of lamps. Worship and deep-daan (offering of lamps) on this night are said to give great merit.', 'The puja honours all deities together — Ganesh, Vishnu, Shiva, Lakshmi, Durga and the nine planets — with deep-daan of lamps in your name, recitation of the Kartik Mahatmya and a havan.'),
    'benefits' => ['Blessings of all deities together', 'Merit of deep-daan on Kartik Purnima', 'Peace and prosperity']];

$chadhava = fn (string $what, string $why, string $day) => "A chadhava is an offering made at the temple in your name and gotra, for people who cannot visit themselves. $what\n\n$why\n\nOur team makes the offering on $day with a short prayer for your wish, and a photo of the offering is added to your account. You are told by notification when it is done.";
$c['shani-grah-chadhava'] = ['benefits' => ['Calms Saturn during Sade Sati and Dhaiya', 'Patience and relief from delays', 'Offered on Saturday in your name']];
$c['hanuman-garhi-bajrangbali-chadhava'] = ['benefits' => ['Courage and protection', 'Relief from Mangal and Shani troubles', 'Offered on Tuesday or Saturday']];
$c['mahamrityunjaya-chadhava'] = ['body' => $chadhava('Milk, Ganga jal, bilva leaves and dhatura are offered to Lord Shiva with the Mahamrityunjaya mantra.', 'This chadhava is a prayer for health, recovery from illness and protection from danger — a simple remedy when a full Mahamrityunjay puja is not possible.', 'a Monday'),
    'benefits' => ['Prayer for health and recovery', 'Protection from danger', 'Simpler than a full puja']];
$c['rahu-grah-chadhava'] = ['body' => $chadhava('Coconut, blue cloth, black til and durva grass are offered for Rahu.', 'It is offered during Rahu Mahadasha or Antardasha, in Kaal Sarp dosh, and when there is confusion, fear or trouble in foreign matters.', 'a Saturday'),
    'benefits' => ['Calms Rahu', 'Clarity and freedom from fear', 'Helpful in Kaal Sarp']];
$c['maa-baglamukhi-chadhava'] = ['body' => $chadhava('A yellow chunri, haldi, chana dal and besan laddoo are offered to Maa Baglamukhi, the goddess who silences opponents.', 'It is offered for victory in court cases, disputes and competitions, and protection from gossip and enemies.', 'a Thursday'),
    'benefits' => ['Victory over opponents', 'Support in court and competitions', 'Protection from gossip']];
$c['diwali-lakshmi-ganesh-chadhava'] = ['body' => $chadhava('Lotus, kamal gatta, kheer and coins are offered to Maa Lakshmi and Lord Ganesha.', 'Offered on Diwali for prosperity in the home and business in the year ahead, and for the removal of obstacles to income.', 'Diwali'),
    'benefits' => ['Prosperity for the year ahead', 'Blessings of Lakshmi and Ganesh', 'Offered on Diwali night']];
$c['karwa-chauth-sindoor-shringar-chadhava'] = ['body' => $chadhava('Sindoor, bangles and a full shringar set are offered to Maa Gauri.', 'Offered on Karwa Chauth for the long life of the husband and a happy, lasting marriage.', 'Karwa Chauth'),
    'benefits' => ['Prayer for the husband\'s long life', 'Blessing for a happy marriage', 'Offered on Karwa Chauth']];

return $c;
