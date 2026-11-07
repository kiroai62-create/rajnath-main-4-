<?php

// Detailed text for written reports, bundles and written answers (Nov 2026).

$steps = fn (string $study, string $days = '5–7 working days') => [
    'Pay online and choose a saved birth profile (date, time and place of birth) and the language — Hindi or English.',
    'Your chart is calculated with the Swiss Ephemeris (Lahiri ayanamsha).',
    $study,
    "The report is written by a person in Acharya Ji's team and checked before release — usually within $days.",
    'You get a notification and email; read it in your account or download the PDF.',
];
$pdf = ['Is the report written by a computer?', 'No. The chart is calculated by software, but every section is written by a person in Acharya Ji\'s team after studying your chart.'];
$time = ['What if I do not know my exact birth time?', 'Tell us the closest time you know. For an unknown time, choose a consultation (Prashna Kundali) or the Palmistry report instead.'];

$c = [];

$c['premium-personalized-kundli'] = [
    'body' => "The Premium Personalized Kundli is a complete written record of your birth chart — something to keep and refer to for years. It is prepared in the Parashari tradition and covers your personality, health, education, career, money, marriage, children, property and spiritual life.\n\n"
        ."The report explains your Lagna, Moon sign and Nakshatra with their meaning for you; each of the twelve houses and their lords; the strength of every planet; important yogas (Raj, Dhana, Gajakesari, Pancha Mahapurusha and others) and doshas (Manglik, Kaal Sarp, Pitru indicators, Sade Sati) — with their cancellations explained honestly.\n\n"
        ."It sets out your Vimshottari Mahadasha and Antardasha timeline for the coming years with what each period is likely to bring, and the divisional charts Navamsa (D-9) and Dashamsa (D-10). Remedies are simple and chosen for your chart — mantra, daan, fasting, and a gemstone only where it is safe.\n\n"
        .'The PDF is printed in clear Devanagari or English, with your charts drawn in the North Indian style.',
    'process' => $steps('Acharya Ji\'s team studies the Lagna, houses, planetary strengths, yogas, doshas, divisional charts and dasha timeline.'),
    'benefits' => ['A full written record of your kundali', 'Dasha timeline for planning the years ahead', 'Honest view of doshas with cancellations', 'Simple remedies suited to your chart', 'Charts and text in Hindi or English'],
    'faqs' => [$pdf, $time, ['How long is the report?', 'Usually 30–40 pages, depending on your chart.']],
];

$c['couple-kundli-compatibility-report'] = [
    'body' => "A written compatibility report for two people, prepared for families and couples who want to study the match carefully before deciding.\n\n"
        ."It includes the full Ashtakoota milan (36 gunas) with an explanation of each koota; Mangal dosh from Lagna, Moon and Venus for both partners with cancellations; Nadi and Bhakoot dosh and their exceptions; the 7th house, its lord, Venus and Jupiter in both charts; a Navamsa (D-9) comparison; and the dasha each partner will run in the first years of marriage.\n\n"
        .'The report ends with a clear summary — strengths of the match, points that need care, remedies where needed — and suitable months for the wedding.',
    'process' => $steps('Both charts are compared: Ashtakoota, Mangal, Nadi and Bhakoot dosh, 7th house, Venus, Jupiter, Navamsa and dashas.'),
    'benefits' => ['Goes far beyond the guna score', 'Written record for both families', 'Clear summary with remedies', 'Suitable months for the wedding'],
    'faqs' => [$pdf, ['Do you need both birth times?', 'Yes, for both partners, as exact as possible.']],
];

$c['varshphal-yearly-report'] = [
    'body' => "Varshphal (the Tajik annual chart) is cast for the moment the Sun returns to its exact birth position each year — your solar return. It shows the themes of the coming year from one birthday to the next.\n\n"
        ."The report studies the Varsha Lagna, the Muntha and its lord, the Varsheshwar (lord of the year), Tajik yogas such as Ithasala and Ishrafa, the Mudda dasha for the year, and the transits of Saturn, Jupiter and Rahu–Ketu over your birth chart.\n\n"
        .'It gives a month-by-month view of career, money, health, family and relationships, the best months for important steps, months that need care, and simple remedies for the year.',
    'process' => $steps('The Varshphal chart for your coming year is cast and studied with Muntha, Varsheshwar, Tajik yogas, Mudda dasha and transits.'),
    'benefits' => ['Month-by-month guide for your year', 'Best months for important steps', 'Remedies for the year ahead', 'A good birthday gift'],
    'faqs' => [$pdf, ['From which date does the report run?', 'From your coming birthday to the next. Tell us if you want the current year instead.']],
];

$c['yearly-horoscope-report'] = [
    'body' => 'A personal horoscope for the calendar year, based on your Moon sign and birth chart rather than a general sun-sign column. It looks at the transits of Saturn, Jupiter, Rahu–Ketu and Mars over your chart, together with your running dasha, and gives a month-by-month view of career, money, health, family and love.',
    'process' => $steps('Transits of the year are studied against your chart and dasha.', '3–5 working days'),
    'benefits' => ['Personal, not a general sign column', 'Month-by-month guidance', 'Affordable yearly planner'],
    'faqs' => [$pdf],
];

$c['soul-purpose-report'] = [
    'body' => "Why am I here, and what kind of life will feel meaningful? The Soul Purpose report looks at the parts of the chart that speak of dharma and inner growth: the 1st, 5th and 9th houses (dharma trikona), the Atmakaraka planet in the Jaimini system and its Navamsa position (Karakamsha), the nodes Rahu and Ketu showing what the soul is moving towards and away from, and Jupiter as the guru.\n\n"
        .'It describes your natural gifts, the lessons that repeat in your life, the kind of work and service that will feel fulfilling, and simple spiritual practices that suit your chart.',
    'process' => $steps('The dharma houses, Atmakaraka, Karakamsha, Rahu–Ketu axis and Jupiter are studied.'),
    'benefits' => ['Understand your deeper purpose', 'Know your natural gifts and repeating lessons', 'Spiritual practices that suit you'],
    'faqs' => [$pdf],
];

$c['career-report'] = [
    'body' => "A detailed written study of your work life. It looks at the 10th house of career and its lord, the 6th house of service, the 2nd and 11th houses of income, the Dashamsa (D-10) chart, and the planets that give success in different fields — Sun for government, Mercury for business and communication, Jupiter for teaching and law, Venus for arts and finance, Saturn for industry and service, Mars for engineering and the forces, Rahu for technology and foreign work.\n\n"
        .'It answers whether job or business suits you better, which fields match your planets, how your career will develop through your dasha periods, the best periods for a job change, promotion or starting a business, and gives simple remedies for growth.',
    'process' => $steps('The 10th, 6th, 2nd and 11th houses, Dashamsa (D-10) and dasha periods are studied for career.'),
    'benefits' => ['Job or business — clear answer', 'Fields that suit your planets', 'Timing of job change and promotion', 'Remedies for growth'],
    'faqs' => [$pdf, ['I am a student. Is this useful?', 'Yes — it helps choose a stream and field of study.']],
];

$c['dhanyog-finance-report'] = [
    'body' => 'A written study of wealth in your chart: the 2nd house (savings), 11th house (gains), 5th and 9th houses (fortune and investments), the Dhana yogas and Lakshmi yogas present, and the planets Jupiter and Venus. It explains your earning style, the periods of strong income and of expenses, the risk of debt, and whether property, shares or business suit you — with simple remedies to strengthen wealth.',
    'process' => $steps('The 2nd, 5th, 9th and 11th houses, Dhana yogas, Jupiter and Venus, and dasha periods are studied for wealth.'),
    'benefits' => ['Know your strong earning periods', 'Dhana yogas explained', 'Investment and property guidance', 'Remedies for wealth'],
    'faqs' => [$pdf, ['Is this financial advice?', 'No. It is astrological guidance; please consult a financial adviser for investments.']],
];

$c['love-report'] = [
    'body' => 'A written report on love and relationships from your own chart: the 5th house of romance, the 7th house of marriage, Venus and Mars, the Moon (emotions) and the Navamsa (D-9). It describes the kind of partner who suits you, how you love and what you need, possible challenges, love-marriage yogas, and the periods when relationships and marriage are most likely.',
    'process' => $steps('The 5th and 7th houses, Venus, Mars, Moon and Navamsa are studied for love and marriage.', '3–5 working days'),
    'benefits' => ['Understand your love nature', 'The partner who suits you', 'Timing of love and marriage'],
    'faqs' => [$pdf],
];

$c['name-analysis-and-correction'] = [
    'body' => "Your name is used thousands of times a year, and in numerology each letter carries a number. This report calculates your name number with Chaldean and Vedic methods and checks whether it supports your Mulank (birth day number) and Bhagyank (destiny number).\n\n"
        .'If the name is not in harmony, it suggests small spelling changes — usually one letter added or removed — with the new numbers and their meaning. The same method can be used for a business, brand or shop name.',
    'process' => $steps('Your name, Mulank and Bhagyank are analysed with Chaldean and Vedic numerology.', '3–5 working days'),
    'benefits' => ['Name in harmony with your numbers', 'Small, practical spelling changes', 'Works for business names too'],
    'faqs' => [['Do I need my birth time?', 'No, only the date of birth and the name.'], ['Do I need to change my name legally?', 'Usually not — using the new spelling in your signature and daily use is enough.']],
];

$c['fortune-report'] = [
    'body' => 'A concise report on your fortune for the next three years: the dasha you are running and the next one, the major transits, and what they mean for career, money, relationships and health — with the best periods for important decisions and simple remedies.',
    'process' => $steps('The running and next dasha periods and major transits are studied.', '3–5 working days'),
    'benefits' => ['Three-year outlook', 'Best periods for decisions', 'Affordable and concise'],
    'faqs' => [$pdf],
];

$c['fortune-plus-report'] = [
    'body' => 'A deeper version of the Fortune report covering five years, with a year-by-year view, the Varshphal theme of each year, Sade Sati and Dhaiya periods if any, and detailed remedies for each difficult period.',
    'process' => $steps('Five years of dasha, transits and Varshphal themes are studied.'),
    'benefits' => ['Five-year, year-by-year outlook', 'Sade Sati and Dhaiya periods marked', 'Detailed remedies'],
    'faqs' => [$pdf],
];

$c['baby-name-report'] = [
    'body' => "A list of meaningful, auspicious names for your baby. From the child's birth nakshatra and pada we take the traditional first syllable (for example \"Chu, Che, Cho, La\" for Ashwini), then suggest names with that sound whose numerology suits the child's birth number — with the meaning of each name, and a Hindi and English spelling.\n\n"
        .'You can tell us your preferences (modern or traditional, deity names, a family letter) and we include them.',
    'process' => $steps('The birth nakshatra and pada give the first syllable; names are checked with numerology.', '3–5 working days'),
    'benefits' => ['Names from the birth nakshatra', 'Numerology checked', 'Meaning of each name', 'Your preferences included'],
    'faqs' => [['How many names will we receive?', 'Usually 15–25 names with meanings.']],
];

$c['rahu-ketu-transit-report'] = [
    'body' => 'Rahu and Ketu change signs about every 18 months and always move backwards. Their transit can bring sudden changes — new opportunities abroad or in technology, confusion, health worries or changes in relationships. This report explains which houses Rahu and Ketu are transiting in your chart, how they interact with your natal planets and dasha, and how to make the most of the period, with simple remedies.',
    'process' => $steps('The current Rahu–Ketu transit is studied against your chart and dasha.', '3–5 working days'),
    'benefits' => ['Understand sudden changes', 'Opportunities of the transit', 'Remedies for Rahu and Ketu'],
    'faqs' => [$pdf],
];

$c['jupiter-transit-report'] = [
    'body' => 'Jupiter (Guru) changes signs about once a year and is the great benefic: where it transits it brings growth, wisdom and opportunity. This report shows the house Jupiter is transiting from your Lagna and Moon, its aspects on your natal planets, the areas of life that will grow, and the best months to act — together with remedies if Jupiter is weak in your chart.',
    'process' => $steps('The current Jupiter transit is studied from your Lagna and Moon, with its aspects.', '3–5 working days'),
    'benefits' => ['Know where growth will come', 'Best months to act', 'Remedies for a weak Jupiter'],
    'faqs' => [$pdf],
];

$c['saturn-in-birth-chart-report'] = [
    'body' => "Saturn (Shani) is the planet of karma, discipline and time. Its position in your birth chart shapes your work, responsibilities and the lessons of life. This report explains your natal Saturn — sign, house, strength, aspects and yogas — what it asks of you, its Mahadasha and Antardasha periods, current Sade Sati or Dhaiya, and practical remedies to turn Saturn's pressure into lasting success.",
    'process' => $steps('Natal Saturn, its dasha periods and current Sade Sati or Dhaiya are studied.'),
    'benefits' => ['Understand Saturn\'s lessons for you', 'Saturn dasha periods explained', 'Practical remedies'],
    'faqs' => [$pdf],
];

$c['sade-sati-report'] = [
    'body' => "Sade Sati is the seven-and-a-half-year period when Saturn transits the 12th, 1st and 2nd houses from your Moon sign. It is feared, but its results depend greatly on Saturn's place in your chart and your dasha — for some it brings hard work and big achievements.\n\n"
        .'This report tells you whether you are in Sade Sati or Dhaiya now, the exact dates of each phase (rising, peak and setting), what each phase is likely to bring for you, and simple remedies — Shani mantra, Hanuman Chalisa, daan and service.',
    'process' => $steps('Saturn\'s transit over your Moon sign is mapped with exact phase dates and your dasha.', '3–5 working days'),
    'benefits' => ['Exact dates of each Sade Sati phase', 'Personal, not general, results', 'Simple Shani remedies'],
    'faqs' => [$pdf, ['Is Sade Sati always bad?', 'No. Its results depend on Saturn\'s strength in your chart and your dasha; for many people it is a time of hard work and growth.']],
];

$c['kaal-sarp-and-manglik-dosh-report'] = [
    'body' => 'A clear written check of the two doshas people worry about most. Manglik dosh is checked from Lagna, Moon and Venus with all classical cancellations; Kaal Sarp dosh is checked for its type (Anant to Sheshnag), whether it is full or partial, and whether it is cancelled. The report explains the real effect in your chart and suggests remedies only where needed — so you can stop worrying where there is no cause.',
    'process' => $steps('Manglik and Kaal Sarp dosh are checked with types and cancellations.', '3–5 working days'),
    'benefits' => ['Honest check of both doshas', 'Cancellations explained', 'Remedies only where needed'],
    'faqs' => [$pdf],
];

$bundle = fn (string $what, array $parts) => [
    'body' => "$what\n\nThe bundle includes: ".implode('; ', $parts).". Each report is written by Acharya Ji's team after studying your chart, and all are delivered in your account as PDFs.",
    'process' => $steps('Each report in the bundle is prepared from your chart.', '7–10 working days'),
    'benefits' => ['Several related reports together', 'Saves money over buying separately', 'All PDFs in your account'],
    'faqs' => [$pdf],
];
$c['career-destiny-bundle'] = $bundle('A combination for people planning their working life: career direction, wealth and the coming years together.', ['Career Report', 'Dhanyog / Finance Report', 'Fortune Report']);
$c['kundli-suraksha-pack'] = $bundle('A protective set that checks the doshas and difficult periods in your chart and gives remedies for each.', ['Kaal Sarp and Manglik Dosh Report', 'Sade Sati Report', 'Saturn in Birth Chart Report']);
$c['love-compatibility-kit'] = $bundle('For couples and those looking for a partner: your own love nature and a compatibility study.', ['Love Report', 'Couple Kundli Compatibility Report']);
$c['seasonal-guidance-package'] = $bundle('Guidance for the season ahead from the current transits.', ['Jupiter Transit Report', 'Rahu–Ketu Transit Report']);
$c['navratri-guidance-package'] = $bundle('A Navratri package with your personal guidance for the year and a mantra suited to your chart for the nine nights.', ['Yearly Horoscope Report', 'Personal Navratri mantra and sadhana guide']);

$c['ask-one-question'] = [
    'body' => "Have one important question — about a job offer, a marriage proposal, a property, travel, health worries or exams? Send it with your birth details and receive a personal written answer from Acharya Ji's team, in Hindi or English.\n\n"
        .'The team studies the houses and planets related to your question, the running dasha and current transits, and writes the answer with the reasoning from your chart, the likely timing and simple guidance. Choose one, two or three questions.',
    'process' => [
        'Choose one, two or three questions and pay online.',
        'Choose your birth profile and write each question on a new line.',
        'Acharya Ji\'s team studies the related houses, dasha and transits.',
        'The written answer appears in your account, usually within 48 working hours, and we email you.',
    ],
    'benefits' => ['Quick, focused written answer', 'Reasoning from your chart', 'Likely timing and guidance', 'No call needed'],
    'faqs' => [$pdf, ['Can I ask a follow-up question?', 'Yes — buy another question, or book a consultation for a longer discussion.']],
];

return $c;
