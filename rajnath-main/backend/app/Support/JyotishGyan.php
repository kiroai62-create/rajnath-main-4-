<?php

namespace App\Support;

/**
 * Reference content for the "Learn Jyotish" pages and the Rashi guides: the 12 bhavas, the 27
 * nakshatras, the five limbs of the Panchang, major yogas and doshas, remedies, Vastu directions
 * and the nature of each Moon sign. Classical (Parashari) facts written in plain language.
 */
final class JyotishGyan
{
    /** Topic pages of /learn: slug => [title, Hindi title, icon, one-line summary]. */
    public const TOPICS = [
        'kundli-basics' => ['Kundli basics', 'कुंडली की मूल बातें', 'chart', 'What a birth chart is, Lagna, Rashi and how the 12 houses are read.'],
        'twelve-houses' => ['The 12 houses (bhavas)', '12 भाव', 'houses', 'Each house rules one part of life, from self and wealth to career and moksha.'],
        'nakshatras' => ['The 27 nakshatras', '27 नक्षत्र', 'star', 'The lunar mansions: ruling planet, deity, symbol and nature of each star.'],
        'panchang' => ['The five limbs of Panchang', 'पंचांग के पाँच अंग', 'sun', 'Tithi, vara, nakshatra, yoga and karana: how a day is judged.'],
        'dasha' => ['Vimshottari dasha', 'विंशोत्तरी दशा', 'cycle', 'The 120-year cycle of planetary periods that times events in life.'],
        'yogas' => ['Important yogas', 'प्रमुख योग', 'yoga', 'Gajakesari, Budhaditya, Pancha Mahapurusha, Raja and Dhana yogas.'],
        'doshas' => ['Doshas explained calmly', 'दोष', 'shield', 'Manglik, Kaal Sarp, Pitra dosh, Sade Sati and Nadi dosh, without fear.'],
        'remedies' => ['Remedies (upay)', 'उपाय', 'lamp', 'Mantra, daan, vrat, puja, gemstones and rudraksha: when and how.'],
        'vastu' => ['Vastu directions', 'वास्तु दिशाएँ', 'compass', 'The eight directions, their deities and planets, and what each suits.'],
    ];

    /** [name, Sanskrit name, significations, karaka (natural significator)] */
    public const HOUSES = [
        ['1st house', 'Tanu bhava (Lagna)', 'Body, health, personality, appearance and the general direction of life.', 'Sun'],
        ['2nd house', 'Dhana bhava', 'Wealth, savings, family, speech, food and early education.', 'Jupiter'],
        ['3rd house', 'Sahaja bhava', 'Courage, younger siblings, effort, communication, skills and short journeys.', 'Mars'],
        ['4th house', 'Sukha bhava', 'Mother, home, property, vehicles, inner peace and basic education.', 'Moon'],
        ['5th house', 'Putra bhava', 'Children, intelligence, higher learning, romance, mantra and past-life merit (purva punya).', 'Jupiter'],
        ['6th house', 'Ripu bhava', 'Disease, debts, enemies, competition, service and daily work.', 'Mars, Saturn'],
        ['7th house', 'Kalatra bhava', 'Marriage, spouse, partnerships, business and public dealings.', 'Venus'],
        ['8th house', 'Ayu bhava', 'Longevity, sudden events, inheritance, research and the occult.', 'Saturn'],
        ['9th house', 'Dharma bhava', 'Fortune (bhagya), father, guru, religion, long journeys and higher wisdom.', 'Jupiter, Sun'],
        ['10th house', 'Karma bhava', 'Career, status, authority, reputation and one\'s actions in the world.', 'Sun, Mercury, Jupiter, Saturn'],
        ['11th house', 'Labha bhava', 'Income, gains, fulfilment of wishes, elder siblings and friends.', 'Jupiter'],
        ['12th house', 'Vyaya bhava', 'Expenses, losses, sleep, foreign lands, charity and moksha.', 'Saturn'],
    ];

    /** [name, ruling planet, deity, symbol, gana] — in order from Ashwini. */
    public const NAKSHATRAS = [
        ['Ashwini', 'Ketu', 'Ashwini Kumaras', 'Horse\'s head', 'Deva'], ['Bharani', 'Venus', 'Yama', 'Yoni', 'Manushya'],
        ['Krittika', 'Sun', 'Agni', 'Razor / flame', 'Rakshasa'], ['Rohini', 'Moon', 'Brahma (Prajapati)', 'Ox cart', 'Manushya'],
        ['Mrigashira', 'Mars', 'Soma', 'Deer\'s head', 'Deva'], ['Ardra', 'Rahu', 'Rudra', 'Teardrop', 'Manushya'],
        ['Punarvasu', 'Jupiter', 'Aditi', 'Bow and quiver', 'Deva'], ['Pushya', 'Saturn', 'Brihaspati', 'Cow\'s udder / flower', 'Deva'],
        ['Ashlesha', 'Mercury', 'Nagas', 'Coiled serpent', 'Rakshasa'], ['Magha', 'Ketu', 'Pitris (ancestors)', 'Royal throne', 'Rakshasa'],
        ['Purva Phalguni', 'Venus', 'Bhaga', 'Front legs of a bed', 'Manushya'], ['Uttara Phalguni', 'Sun', 'Aryaman', 'Back legs of a bed', 'Manushya'],
        ['Hasta', 'Moon', 'Savitar', 'Hand', 'Deva'], ['Chitra', 'Mars', 'Vishwakarma', 'Bright jewel', 'Rakshasa'],
        ['Swati', 'Rahu', 'Vayu', 'Young sprout in the wind', 'Deva'], ['Vishakha', 'Jupiter', 'Indra and Agni', 'Triumphal arch', 'Rakshasa'],
        ['Anuradha', 'Saturn', 'Mitra', 'Lotus', 'Deva'], ['Jyeshtha', 'Mercury', 'Indra', 'Earring / umbrella', 'Rakshasa'],
        ['Mula', 'Ketu', 'Nirriti', 'Bunch of roots', 'Rakshasa'], ['Purva Ashadha', 'Venus', 'Apas (waters)', 'Winnowing fan', 'Manushya'],
        ['Uttara Ashadha', 'Sun', 'Vishvedevas', 'Elephant tusk', 'Manushya'], ['Shravana', 'Moon', 'Vishnu', 'Ear / three footprints', 'Deva'],
        ['Dhanishta', 'Mars', 'The eight Vasus', 'Drum (mridanga)', 'Rakshasa'], ['Shatabhisha', 'Rahu', 'Varuna', 'Empty circle', 'Rakshasa'],
        ['Purva Bhadrapada', 'Jupiter', 'Aja Ekapada', 'Front of a funeral cot', 'Manushya'], ['Uttara Bhadrapada', 'Saturn', 'Ahirbudhnya', 'Back of a funeral cot', 'Manushya'],
        ['Revati', 'Mercury', 'Pushan', 'Fish / drum', 'Deva'],
    ];

    /** [limb, what it is, how it is used] */
    public const PANCHANG = [
        ['Tithi', 'The lunar day: each 12° the Moon moves ahead of the Sun. 30 tithis make a lunar month, 15 in Shukla paksha (waxing) and 15 in Krishna paksha (waning).', 'Vrat, festivals and many muhurats are fixed by tithi. Rikta tithis (4th, 9th, 14th) are avoided for new beginnings.'],
        ['Vara', 'The weekday, counted from sunrise to sunrise and ruled by a planet: Sunday Sun, Monday Moon, Tuesday Mars, Wednesday Mercury, Thursday Jupiter, Friday Venus, Saturday Saturn.', 'Each day suits work of its planet, and planetary worship is done on its day.'],
        ['Nakshatra', 'The Moon\'s star of the day: one of 27 divisions of 13°20\' each.', 'The most important limb for muhurat; fixed stars (dhruva) suit foundations, movable stars (chara) suit travel.'],
        ['Yoga', 'One of 27 combinations of the Sun\'s and Moon\'s longitudes added together.', 'Auspicious yogas such as Siddhi and Shubha help; Vyatipata and Vaidhriti are avoided.'],
        ['Karana', 'Half a tithi; 11 karanas repeat through the month.', 'Vishti (Bhadra) karana is avoided for auspicious work.'],
    ];

    /** [name, how it forms, what it gives] */
    public const YOGAS = [
        ['Gajakesari yoga', 'Jupiter in a kendra (1st, 4th, 7th or 10th) from the Moon.', 'Wisdom, respect, a good name and help from influential people.'],
        ['Budhaditya yoga', 'Sun and Mercury together in one sign.', 'Sharp intellect, skill in speech, study and administration.'],
        ['Pancha Mahapurusha yogas', 'Mars (Ruchaka), Mercury (Bhadra), Jupiter (Hamsa), Venus (Malavya) or Saturn (Shasha) in its own or exalted sign in a kendra from the Lagna.', 'A strong personality in the field of that planet: courage, intellect, wisdom, beauty and comfort, or discipline.'],
        ['Raja yoga', 'Lords of a kendra and a trikona (1st, 5th, 9th) joined, in mutual aspect or exchange.', 'Rise in status and authority, especially in the dashas of those planets.'],
        ['Dhana yoga', 'Lords of the 2nd, 5th, 9th and 11th houses connected with each other.', 'Earning and saving capacity; wealth through one\'s own effort.'],
        ['Neecha Bhanga Raja yoga', 'A debilitated planet whose debility is cancelled, for example when the lord of its sign is in a kendra.', 'Rise after early struggle; weakness turning into strength.'],
        ['Vipareeta Raja yoga', 'Lords of the 6th, 8th or 12th placed in another of these houses.', 'Success that comes out of difficult situations.'],
        ['Chandra–Mangal yoga', 'Moon and Mars together or in mutual aspect.', 'Enterprise and earning power, sometimes with a quick temper.'],
        ['Kemadruma yoga', 'No planet (other than the Sun, Rahu and Ketu) in the 2nd or 12th from the Moon.', 'Periods of loneliness or financial strain; often cancelled by other factors in the chart.'],
    ];

    /** [name, how it is judged, the calm view, common remedies] */
    public const DOSHAS = [
        ['Manglik (Kuja) dosh', 'Mars in the 1st, 4th, 7th, 8th or 12th house (some include the 2nd) from Lagna, Moon or Venus.', 'Very common and often cancelled: by Mars in own or exalted sign, by the partner also being Manglik, and by other rules. It is checked together with the full matching, never alone.', 'Hanuman worship on Tuesdays, Mangal shanti puja, and in some traditions Kumbh vivah before marriage.'],
        ['Kaal Sarp dosh', 'All seven planets lying on one side of the Rahu–Ketu axis.', 'Not mentioned in the oldest classics; its effect depends on the rest of the chart and the dashas. It is not a curse.', 'Kaal Sarp shanti puja (for example at Trimbakeshwar or by a learned pandit), Shiva abhishek and Rahu–Ketu mantra jaap.'],
        ['Pitra dosh', 'Afflictions to the Sun, the 9th house or its lord, often by Rahu or Saturn.', 'Read as a reminder of duties to the ancestors rather than a punishment.', 'Tarpan and shraddha in Pitru Paksha, Narayan Bali or Tripindi shraddha where advised, and feeding the needy.'],
        ['Sade Sati', 'Saturn transiting the 12th, 1st and 2nd houses from the Moon sign, about 7½ years in total.', 'A period of responsibility and maturing, often with real achievement; its results depend on Saturn\'s strength in the birth chart.', 'Shani mantra, Hanuman Chalisa, mustard-oil lamp on Saturdays, service to elders and workers.'],
        ['Nadi dosh (in matching)', 'Bride and groom have the same Nadi (Adi, Madhya or Antya) in Ashtakoota matching, losing 8 of 36 points.', 'Several exceptions remove it, for example the same nakshatra in different padas or the same Rashi lord.', 'Mahamrityunjaya jaap and a full chart comparison before deciding.'],
        ['Grahan dosh', 'Sun or Moon joined with Rahu or Ketu in the birth chart.', 'Shows a sensitive area of life that benefits from care; not a fixed bad result.', 'Surya or Chandra mantra, daan on eclipse days, and Rahu–Ketu shanti.'],
    ];

    /** [remedy, what it is, when it suits] */
    public const REMEDIES = [
        ['Mantra jaap', 'Repeating the beej or Vedic mantra of a planet or deity a fixed number of times.', 'Safe for everyone and the first remedy in most cases.'],
        ['Daan (charity)', 'Giving the grain, cloth or item linked to a planet on its day.', 'For planets that are troublesome in the chart or in transit.'],
        ['Vrat (fasting)', 'Fasting or simple food on the planet\'s weekday or on Ekadashi, Pradosh and similar days.', 'Builds discipline and devotion; follow your health needs.'],
        ['Puja and anushthan', 'Shanti puja, havan or a set number of mantra recitations by pandits with sankalp in your name.', 'For a specific dosha, dasha or important goal.'],
        ['Gemstones (ratna)', 'Wearing the stone of a planet to strengthen it.', 'Only after the chart is checked: a stone strengthens the planet whether it helps or harms you.'],
        ['Rudraksha and yantra', 'Mukhi rudraksha linked to planets, and energised yantras for worship.', 'A gentle support alongside mantra.'],
        ['Seva and conduct', 'Respect to parents and teachers, help to the needy, honest work.', 'Classical texts call good conduct the strongest remedy of all.'],
    ];

    /** [direction, Sanskrit, deity, planet, suits] */
    public const VASTU = [
        ['North-east', 'Ishan', 'Shiva (Ishana)', 'Jupiter', 'Puja room, water source, open space; keep light and clean.'],
        ['East', 'Purva', 'Indra', 'Sun', 'Main entrance, windows, living room; morning light.'],
        ['South-east', 'Agneya', 'Agni', 'Venus', 'Kitchen and electrical equipment.'],
        ['South', 'Dakshin', 'Yama', 'Mars', 'Heavy walls and storage; fewer openings.'],
        ['South-west', 'Nairitya', 'Nirriti', 'Rahu', 'Master bedroom, heavy furniture, the head of the family.'],
        ['West', 'Paschim', 'Varuna', 'Saturn', 'Dining room, children\'s study, overhead water tank.'],
        ['North-west', 'Vayavya', 'Vayu', 'Moon', 'Guest room, store for finished goods, garage.'],
        ['North', 'Uttar', 'Kubera', 'Mercury', 'Cash locker, office, open space for growth.'],
        ['Centre', 'Brahmasthan', 'Brahma', '—', 'Keep open and uncluttered; no heavy pillars or toilets.'],
    ];

    /** Moon sign guides: [nature, strengths, take care, work that suits, compatible signs]. */
    public const SIGNS = [
        ['Energetic, direct and a natural starter; quick to act and quick to cool down.', 'Courage, leadership, honesty, drive', 'Haste, impatience, temper', 'Defence, police, sports, surgery, engineering, starting businesses', 'Simha, Dhanu, Mithuna, Kumbha'],
        ['Steady, patient and practical; loves comfort, beauty and good food.', 'Reliability, loyalty, artistic taste, financial sense', 'Stubbornness, resistance to change', 'Banking, finance, agriculture, music, fashion, hospitality', 'Kanya, Makara, Karka, Meena'],
        ['Curious, witty and talkative; learns quickly and enjoys variety.', 'Communication, intellect, adaptability, humour', 'Restlessness, scattered focus', 'Media, writing, teaching, sales, trade, IT', 'Tula, Kumbha, Mesha, Simha'],
        ['Caring, emotional and protective; home and family come first.', 'Empathy, intuition, memory, nurturing', 'Moodiness, over-sensitivity', 'Medicine, nursing, food, real estate, social work', 'Vrishchika, Meena, Vrishabha, Kanya'],
        ['Confident, generous and dignified; likes to lead and be respected.', 'Leadership, warmth, creativity, loyalty', 'Pride, need for praise', 'Government, management, politics, performing arts', 'Mesha, Dhanu, Mithuna, Tula'],
        ['Analytical, modest and hard-working; notices every detail.', 'Precision, service, practical intelligence', 'Worry, over-criticism', 'Accounts, audit, health care, research, editing', 'Vrishabha, Makara, Karka, Vrishchika'],
        ['Balanced, polite and fair; seeks harmony and partnership.', 'Diplomacy, charm, sense of justice, taste', 'Indecision, avoiding conflict', 'Law, design, fashion, counselling, business partnerships', 'Mithuna, Kumbha, Simha, Dhanu'],
        ['Intense, private and determined; feelings run deep.', 'Willpower, research, loyalty, resilience', 'Secrecy, jealousy, holding grudges', 'Research, investigation, medicine, occult sciences, mining', 'Karka, Meena, Kanya, Makara'],
        ['Optimistic, principled and free-spirited; loves learning and travel.', 'Wisdom, honesty, faith, generosity', 'Bluntness, over-confidence', 'Teaching, law, religion, publishing, travel', 'Mesha, Simha, Tula, Kumbha'],
        ['Disciplined, ambitious and responsible; builds slowly and surely.', 'Patience, organisation, practicality, endurance', 'Pessimism, rigidity', 'Administration, engineering, construction, government service', 'Vrishabha, Kanya, Vrishchika, Meena'],
        ['Independent, humane and inventive; thinks of the larger group.', 'Original ideas, friendship, social conscience', 'Detachment, stubborn views', 'Science, technology, social work, research, NGOs', 'Mithuna, Tula, Dhanu, Mesha'],
        ['Gentle, imaginative and devoted; deeply spiritual and kind.', 'Compassion, intuition, creativity, faith', 'Escapism, indecision', 'Spiritual work, arts, healing, charity, marine work', 'Karka, Vrishchika, Vrishabha, Makara'],
    ];
}
