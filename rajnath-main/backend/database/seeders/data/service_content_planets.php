<?php

// Detailed text for the nine Grah Shanti pujas, Navgrah Shanti and the twelve Lagna pujas.
// Built from classical facts (deity, beej mantra, jaap count, samidha, day, donation) so each
// page is specific to its planet or ascendant.

$planets = [
    'surya' => ['Surya (Sun)', 'Lord Surya', 'Om Hraam Hreem Hraum Sah Suryaya Namah', 7000, 'Sunday', 'arka (aak)', 'wheat, jaggery, red cloth, copper and red sandalwood', 'Ruby (Manik)',
        'father, authority, government work, self-confidence, eyesight and the heart', 'low confidence, conflict with father or seniors, trouble in government matters, ego clashes and weak vitality',
        'Aditya Hridaya Stotra and arghya to the rising Sun'],
    'chandra' => ['Chandra (Moon)', 'Lord Chandra and Lord Shiva', 'Om Shraam Shreem Shraum Sah Chandraya Namah', 11000, 'Monday', 'palash', 'rice, milk, white cloth, silver, sugar and curd', 'Pearl (Moti)',
        'mind, emotions, mother, sleep, fluids and public life', 'restlessness, mood swings, anxiety, poor sleep, worry about mother and emotional instability',
        'Shiva abhishek with milk on Mondays'],
    'mangal' => ['Mangal (Mars)', 'Lord Mangal and Lord Hanuman', 'Om Kraam Kreem Kraum Sah Bhaumaya Namah', 10000, 'Tuesday', 'khadira (khair)', 'red lentils (masoor), jaggery, red cloth, copper and red flowers', 'Red Coral (Moonga)',
        'courage, energy, siblings, land and property, blood and surgery', 'anger, accidents, disputes over land, delays in marriage (Mangal dosh), debt and conflict with siblings',
        'Hanuman Chalisa and Sundarkand on Tuesdays'],
    'budh' => ['Budh (Mercury)', 'Lord Budh and Lord Vishnu', 'Om Braam Breem Braum Sah Budhaya Namah', 9000, 'Wednesday', 'apamarga (chirchita)', 'green moong, green cloth, bronze, green vegetables and fodder for cows', 'Emerald (Panna)',
        'intellect, speech, education, business, accounts and the nervous system', 'poor concentration in studies, loss in trade, speech problems, nervousness and wrong decisions',
        'Vishnu Sahasranama and feeding green grass to cows'],
    'guru' => ['Guru (Jupiter)', 'Lord Brihaspati and Lord Vishnu', 'Om Graam Greem Graum Sah Gurave Namah', 19000, 'Thursday', 'peepal', 'chana dal, turmeric, yellow cloth, bananas, books and gold', 'Yellow Sapphire (Pukhraj)',
        'wisdom, teachers, children, wealth, marriage (for women) and dharma', 'delay in marriage or children, lack of guidance, financial ups and downs, liver trouble and loss of respect',
        'Vishnu puja and fasting on Thursdays'],
    'shukra' => ['Shukra (Venus)', 'Lord Shukracharya and Maa Lakshmi', 'Om Draam Dreem Draum Sah Shukraya Namah', 16000, 'Friday', 'udumbara (gular)', 'rice, white cloth, curd, sugar, perfume and silver', 'Diamond (Heera) or Opal',
        'marriage, love, comfort, vehicles, arts, beauty and luxuries', 'tension in married life, delay in marriage, lack of comforts, problems with vehicles and loss of charm',
        'Lakshmi puja and Shri Suktam on Fridays'],
    'shani' => ['Shani (Saturn)', 'Lord Shani and Lord Hanuman', 'Om Praam Preem Praum Sah Shanaischaraya Namah', 23000, 'Saturday', 'shami', 'black sesame, mustard oil, black urad, iron, black cloth and shoes for the needy', 'Blue Sapphire (Neelam)',
        'karma, discipline, servants, longevity, delays and hard work', 'Sade Sati and Dhaiya, delays at work, chronic illness, legal trouble, loneliness and heavy responsibilities',
        'Shani Chalisa, Hanuman Chalisa and lighting a mustard-oil diya under a peepal tree on Saturdays'],
    'rahu' => ['Rahu', 'Maa Durga and Lord Bhairav', 'Om Bhraam Bhreem Bhraum Sah Rahave Namah', 18000, 'Saturday', 'durva grass', 'black sesame, blankets, coconut, blue cloth and lead', 'Hessonite (Gomed)',
        'sudden events, foreign lands, technology, confusion and hidden enemies', 'confusion, fear, sudden losses, addictions, trouble with foreign matters and Kaal Sarp effects',
        'Durga Saptashati path and feeding birds'],
    'ketu' => ['Ketu', 'Lord Ganesha', 'Om Straam Streem Straum Sah Ketave Namah', 17000, 'Tuesday', 'kusha grass', 'blankets, sesame, a two-coloured cloth and food for dogs', "Cat's Eye (Lehsunia)",
        'spirituality, detachment, past karma, sudden injuries and moksha', 'lack of direction, unexplained health problems, accidents, isolation and obstacles in new work',
        'Ganesh puja and Ganapati Atharvashirsha'],
];

$out = [];
foreach ($planets as $slug => [$name, $deity, $mantra, $jaap, $day, $samidha, $daan, $gem, $signifies, $troubles, $daily]) {
    $out["$slug-grah-shanti-puja"] = [
        'body' => "$name Grah Shanti Puja is a Vedic ritual to calm and strengthen $name in your birth chart. In Jyotish, $name governs $signifies. When it is weak, debilitated, combust or afflicted by malefic planets, or when its Mahadasha or Antardasha is running, people often face $troubles.\n\n"
            ."The puja is performed by Acharya Dr. Rajnath Jha and the priests of Jyotirveda Vigyan Sansthan, Patna, following the Grah Shanti vidhi of the Brihat Parashara Hora Shastra and the Karmakand tradition. It begins with Ganesh puja and sankalp in your name and gotra, followed by the worship of $deity, the jaap of the $name beej mantra — \"$mantra\" — and a havan with $samidha samidha, the wood traditionally linked to $name.\n\n"
            ."The full classical jaap count for $name is $jaap mantras. Depending on the package and your chart, the jaap is completed by a group of priests over one or more days, and the puja is usually begun on a $day in a favourable tithi and nakshatra checked in the Panchang.\n\n"
            ."Acharya Ji recommends this puja only after looking at your chart. If you are not sure whether $name is the cause of your difficulty, book a consultation or a Janma Kundali reading first — the puja is most useful when the right planet is chosen.",
        'process' => [
            "Acharya Ji checks your birth details and chooses a muhurat on a $day with a favourable tithi and nakshatra.",
            'Ganesh puja, Gauri puja, kalash sthapana and sankalp in the names and gotra you send.',
            "Navgrah mandal is set up and $deity is invoked with Vedic mantras.",
            "Jaap of the $name beej mantra ($mantra) — up to the full count of $jaap as per the package.",
            "Havan with $samidha samidha, ghee and the planet's samagri; purnahuti and aarti.",
            "Daan on your behalf of $daan, as prescribed for $name.",
            'Photos (and video where possible) are added to your account; prasad is posted with the Family package.',
        ],
        'benefits' => [
            "Reduces the troublesome effects of a weak or afflicted $name",
            "Supportive during the Mahadasha or Antardasha of $name",
            "Strengthens the areas $name rules: ".explode(',', $signifies)[0].' and more',
            'Brings calm, confidence and a sense of protection',
            "Can be combined with wearing $gem when your chart allows it",
        ],
        'faqs' => [
            ["Do I need to be present for the $name Grah Shanti Puja?", 'No. The puja is performed in your name and gotra by Acharya Ji\'s team. You can watch the live link where the temple allows it, and photos are added to your account. You may also attend in Patna.'],
            ["When should I do the $name Shanti Puja?", "When $name is weak or afflicted in your chart, during its dasha, or when it transits a difficult house. The best day to begin is $day."],
            ['What should I do at home after the puja?', "Simple daily practice helps: $daily. Acharya Ji may also suggest a gemstone ($gem) only after studying your chart."],
        ],
        'includes' => [
            "Sankalp in your name and gotra, with $deity puja",
            "$name beej mantra jaap and havan with $samidha samidha",
            'Muhurat chosen from the Panchang',
            "Daan of $name items on your behalf",
            'Photos in your account; video link where the temple allows it',
            'Prasad by courier within India with the Family package',
        ],
        'audience' => ["People running the Mahadasha or Antardasha of $name", "Those whose chart shows a weak or afflicted $name", "Anyone advised a $name remedy by an astrologer"],
    ];
}

$out['navgrah-shanti-puja'] = [
    'body' => "Navgrah Shanti Puja honours all nine planets — Surya, Chandra, Mangal, Budh, Guru, Shukra, Shani, Rahu and Ketu — together. It is done when several planets are troubling the chart at once, before important beginnings such as marriage, a new house or a new business, and as a yearly puja for the peace and progress of the family.\n\n"
        ."Acharya Dr. Rajnath Jha and his team set up the Navgrah mandal with the colour, grain and direction of each planet, invoke each graha with its Vedic mantra and perform a havan with the nine samidhas — aak, palash, khair, apamarga, peepal, gular, shami, durva and kusha.\n\n"
        ."Because every planet is worshipped, this puja is a good general remedy when you are not sure which planet is causing a problem, or when the dasha changes. Where one planet is clearly the cause, Acharya Ji may suggest that planet's own Grah Shanti instead, or as well.",
    'process' => [
        'Muhurat chosen from the Panchang and your chart.',
        'Ganesh and Gauri puja, kalash sthapana and sankalp in your name and gotra.',
        'Navgrah mandal set up with the colour, grain and direction of each planet.',
        'Invocation and jaap for each of the nine planets with their Vedic and beej mantras.',
        'Havan with the nine samidhas, purnahuti and aarti.',
        'Daan for the nine planets on your behalf; photos and video added to your account.',
    ],
    'benefits' => ['Balances all nine planets in one ritual', 'A good start before marriage, griha pravesh or a new business', 'Helpful when the dasha changes or many problems come together', 'Brings peace and steadiness to the family'],
    'faqs' => [
        ['Navgrah Shanti or a single Grah Shanti — which should I choose?', 'If one planet is clearly troubling you, that planet\'s Grah Shanti is more focused. If several planets are involved or you are not sure, Navgrah Shanti covers all nine. Acharya Ji can advise after seeing your chart.'],
        ['Can Navgrah Shanti be done every year?', 'Yes. Many families do it once a year, often around Makar Sankranti, a birthday or the start of a new venture.'],
    ],
    'includes' => ['Navgrah mandal with all nine planets', 'Jaap and havan with nine samidhas', 'Sankalp in your name and gotra', 'Daan for the nine planets', 'Photos and video link in your account', 'Prasad with the Family package'],
    'audience' => ['Families wanting a yearly puja for peace', 'People starting a marriage, house or business', 'Anyone facing several problems at once'],
];

$lagnas = [
    'mesh' => ['Mesha (Aries)', 'Mangal', 'Lord Hanuman and Lord Kartikeya', 'courage, quick action and leadership', 'impatience, anger and accidents', 'Surya and Guru'],
    'vrishabh' => ['Vrishabha (Taurus)', 'Shukra', 'Maa Lakshmi', 'stability, patience and love of comfort', 'stubbornness, delays in money and throat trouble', 'Shani'],
    'mithun' => ['Mithuna (Gemini)', 'Budh', 'Lord Vishnu and Lord Ganesha', 'intelligence, communication and versatility', 'restlessness, nervousness and indecision', 'Shukra'],
    'kark' => ['Karka (Cancer)', 'Chandra', 'Lord Shiva and Maa Parvati', 'care, emotion and family love', 'mood swings, worry and attachment', 'Mangal'],
    'simha' => ['Simha (Leo)', 'Surya', 'Lord Surya and Lord Rama', 'confidence, dignity and authority', 'ego, conflict with seniors and heart strain', 'Mangal'],
    'kanya' => ['Kanya (Virgo)', 'Budh', 'Lord Ganesha and Lord Vishnu', 'analysis, service and practical skill', 'over-thinking, digestion and criticism', 'Shukra'],
    'tula' => ['Tula (Libra)', 'Shukra', 'Maa Lakshmi and Maa Durga', 'balance, fairness and charm', 'indecision, relationship strain and kidney trouble', 'Shani'],
    'vrishchik' => ['Vrishchika (Scorpio)', 'Mangal', 'Lord Hanuman and Lord Shiva', 'depth, determination and research', 'secrecy, sudden events and anger', 'Surya and Chandra'],
    'dhanu' => ['Dhanu (Sagittarius)', 'Guru', 'Lord Vishnu and Lord Dattatreya', 'optimism, faith and learning', 'overconfidence, wasteful spending and liver strain', 'Surya and Mangal'],
    'makar' => ['Makara (Capricorn)', 'Shani', 'Lord Shani, Lord Hanuman and Lord Shiva', 'discipline, ambition and endurance', 'delays, heavy responsibility and joint pain', 'Shukra'],
    'kumbh' => ['Kumbha (Aquarius)', 'Shani', 'Lord Shiva and Lord Hanuman', 'service, innovation and fairness', 'isolation, sudden changes and nervous strain', 'Shukra'],
    'meen' => ['Meena (Pisces)', 'Guru', 'Lord Vishnu and Lord Shiva', 'compassion, devotion and imagination', 'escapism, confusion and over-giving', 'Mangal and Chandra'],
];
foreach ($lagnas as $slug => [$name, $lord, $deity, $strengths, $weak, $yoga]) {
    $out["$slug-lagna-puja"] = [
        'body' => "Your Lagna (ascendant) is the sign rising in the east at the moment of birth. It is the foundation of the whole kundali: it shapes your body, nature, health and the way life unfolds. For people born with $name Lagna, the Lagna lord is $lord, and the planets that support this Lagna most are $yoga.\n\n"
            ."$name Lagna Puja strengthens the Lagna and its lord so that the natural strengths of this ascendant — $strengths — come forward, and its weak points — $weak — are reduced. It is especially useful when the Lagna lord is weak, combust, debilitated or placed in the 6th, 8th or 12th house, or when its dasha is running.\n\n"
            ."Acharya Dr. Rajnath Jha and his team perform the puja of $deity with the jaap of $lord's mantra, Lagna-sukta mantras and a havan, after Ganesh puja and sankalp in your name and gotra, on a muhurat chosen for your chart.\n\n"
            .'If you do not know your Lagna, use the free Lagna calculator on this site or book a Janma Kundali reading — an exact birth time is needed to know the Lagna.',
        'process' => [
            "Acharya Ji confirms your $name Lagna from your birth details and chooses a muhurat.",
            'Ganesh puja, kalash sthapana and sankalp in your name and gotra.',
            "Worship of $deity, the deity linked to $name Lagna.",
            "Jaap of the mantra of $lord, the Lagna lord.",
            'Havan, purnahuti and aarti; daan as prescribed for the Lagna lord.',
            'Photos (and video where possible) in your account; prasad with the Family package.',
        ],
        'benefits' => ["Strengthens the Lagna lord $lord", "Brings out the qualities of $name Lagna: $strengths", 'Supports health, confidence and direction in life', 'A good foundation remedy before other pujas'],
        'faqs' => [
            ['How do I know my Lagna?', 'Your Lagna depends on your exact time and place of birth. Use the free Lagna calculator on this site, or share your birth details when you book and Acharya Ji will confirm it.'],
            ["Who should do the $name Lagna Puja?", "People born with $name Lagna, especially when $lord is weak in the chart or its dasha is running."],
        ],
        'includes' => ["$deity puja with sankalp in your name and gotra", "$lord mantra jaap and havan", 'Muhurat chosen for your chart', 'Photos and video link in your account', 'Prasad with the Family package'],
        'audience' => ["People born with $name Lagna", "Those whose Lagna lord $lord is weak", 'Anyone wanting a foundation remedy for health and direction'],
    ];
}

return $out;
