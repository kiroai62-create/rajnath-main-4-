<?php

// Detailed service pages written for each service (Nov 2026). Keys: summary, body (paragraphs
// separated by a blank line), process (steps), benefits, faqs ([question, answer]), includes,
// audience, disclaimer. Missing keys keep the existing text.

$c = [];

/* ───────────── Consultations ───────────── */

$c['consultation-with-acharya-ji'] = [
    'body' => "Sit down one-to-one with Acharya Dr. Rajnath Jha — Ph.D. in Astrology, with more than 30 years of practice in Patna and 17 years as Jyotish and Karmakand consultant at Jyotish Mandap, Mahavir Mandir Trust. You can ask about anything that is on your mind: career and job change, business, marriage and relationships, children, health worries, property, foreign travel, court matters or spiritual direction.\n\n"
        ."Before the session Acharya Ji studies your Janma Kundali — Lagna, Moon sign, Nakshatra, planetary strengths, yogas, doshas and the running Vimshottari Mahadasha and Antardasha — together with the current transits (gochar). During the call he explains what your chart shows, why things are happening, and which periods ahead are supportive or need care.\n\n"
        ."Remedies are always simple and practical: mantra, daan, fasting, a puja only when truly needed, and a gemstone only after a full check. He does not frighten people or push expensive rituals.\n\n"
        .'Choose a video or audio call from anywhere in the world, or meet him in person at the Kankarbagh, Patna office. Couple sessions read both charts together, and urgent sessions are given the earliest available time.',
    'process' => [
        'Choose call type (audio, video or in person) and length (30 minutes or 1 hour).',
        'Pick a free time on the calendar and add a saved birth profile with your date, time and place of birth.',
        'Pay online; the time is held for you while you pay.',
        'Acharya Ji studies your chart before the session.',
        'Join the call from your account or the app at the booked time (or visit the Patna office).',
        'Write down the remedies; your booking stays in your account for reference.',
    ],
    'benefits' => ['Clear answers to your real questions, from an experienced astrologer', 'Timing of good and difficult periods ahead', 'Simple, affordable remedies suited to your chart', 'Privacy — the call is not recorded', 'Available worldwide by video or audio'],
    'faqs' => [
        ['What if I do not know my exact birth time?', 'Acharya Ji can still guide you using Prashna Kundali (a chart for the moment of your question) and palmistry. Mention it in the booking.'],
        ['Can I ask about more than one topic?', 'Yes. In 30 minutes most people cover two or three questions; choose 1 hour for a complete life review or a couple session.'],
        ['Which languages are used?', 'Hindi (all dialects), Maithili, Sanskrit and English.'],
        ['Can I move my booking?', 'Yes, once, up to 24 hours before the start. Cancellations up to 24 hours before are refunded to your wallet.'],
    ],
];

$c['janma-kundali-reading'] = [
    'body' => "Your Janma Kundali is a map of the sky at the exact moment and place of your birth. It shows the twelve houses (bhavas) of life, the nine planets in them, and the time periods (dashas) through which their results unfold. A careful reading explains your nature, strengths and weak points, and when important events are likely.\n\n"
        ."Acharya Dr. Rajnath Jha reads the chart in the classical Parashari tradition: Lagna and Lagna lord, Moon sign and Nakshatra, the strength of each planet (exaltation, own sign, combustion, retrogression), the divisional charts such as Navamsa (D-9) for marriage and Dashamsa (D-10) for career, important yogas such as Raj yoga and Dhana yoga, and doshas such as Manglik, Kaal Sarp and Sade Sati.\n\n"
        ."The Vimshottari Mahadasha and Antardasha timeline is then matched with current transits of Shani, Guru and Rahu–Ketu to tell you which years are good for career growth, marriage, property or travel, and which need patience.\n\n"
        .'The reading is a conversation. Ask your questions freely; at the end you receive simple remedies chosen for your chart.',
    'process' => [
        'Share your date, time and place of birth (a saved birth profile).',
        'The chart is calculated with the Swiss Ephemeris (Lahiri ayanamsha) and checked by Acharya Ji.',
        'Acharya Ji studies the Lagna, houses, planets, divisional charts, yogas and doshas.',
        'He maps your Mahadasha–Antardasha timeline with current transits.',
        'During the session he explains the results and answers your questions.',
        'You receive practical remedies — mantra, daan, puja or gemstone only if needed.',
    ],
    'benefits' => ['A complete picture of career, money, marriage, health and family', 'Know your strong and weak periods in advance', 'Understand doshas correctly — without fear', 'Remedies that fit your chart and budget'],
    'faqs' => [
        ['How accurate does my birth time need to be?', 'As exact as possible — the Lagna changes about every two hours and divisional charts change faster. A birth certificate or hospital record is best.'],
        ['Is a Kundali reading the same as a written report?', 'No. The reading is a live session with Acharya Ji. If you want a written record, choose the Premium Personalized Kundli report.'],
        ['Will you tell me about Manglik or Kaal Sarp dosh?', 'Yes, every dosha present is checked from Lagna, Moon and Venus, and cancellations are explained honestly.'],
    ],
];

$c['love-marriage-compatibility'] = [
    'body' => "Kundali Milan is the traditional way of checking whether two people are suited for marriage. The Ashtakoota system gives a score out of 36 from eight factors — Varna, Vashya, Tara, Yoni, Graha Maitri, Gana, Bhakoot and Nadi. But a good marriage needs more than a number, and a low score does not always mean a bad match.\n\n"
        ."Acharya Dr. Rajnath Jha studies both charts in full: the 7th house and its lord, Venus and Jupiter, Mangal dosh from Lagna, Moon and Venus (and whether it is cancelled), Nadi and Bhakoot dosh and their exceptions, the Navamsa (D-9) of both partners, and the dasha periods each person will be running after marriage.\n\n"
        ."For love marriages he gives honest, practical guidance — how to talk to the family, which months are favourable for the wedding, and simple remedies where there are real concerns. For arranged marriages he helps families decide with clarity rather than fear.\n\n"
        ."Both partners' birth details are needed. The session can be by video call from anywhere or in person at the Patna office.",
    'process' => [
        'Share the birth details of both partners.',
        'Both charts are calculated and the 36-guna Ashtakoota milan is prepared.',
        'Acharya Ji checks Mangal, Nadi and Bhakoot dosh and their cancellations.',
        '7th house, Venus, Jupiter and the Navamsa of both partners are compared.',
        'In the session he explains the match, the timing of marriage and remedies.',
        'If you wish, he suggests a vivah muhurat for the wedding.',
    ],
    'benefits' => ['Goes beyond the 36-guna score', 'Correct, honest view of Mangal and Nadi dosh', 'Guidance for love and arranged marriages', 'Wedding muhurat on request'],
    'faqs' => [
        ['Our guna score is below 18. Should we not marry?', 'Not necessarily. Many doshas are cancelled by other factors, and the 7th house and Navamsa matter more. Acharya Ji will explain your case honestly.'],
        ['Is Mangal dosh a problem if only one of us has it?', 'It depends on its strength and on cancellations in both charts. Often it is cancelled; where it is not, simple remedies are suggested.'],
        ['Can only one partner join the call?', 'Yes. Both sets of birth details are needed, but one person or the parents can take the session.'],
    ],
];

$c['business-astrology'] = [
    'body' => "Business decisions involve money, partners and timing. Business astrology looks at your own chart — the 10th house of work, the 7th house of partnership, the 2nd and 11th houses of income and gains, and the planets Mercury, Jupiter and Saturn — to see whether business or service suits you better, and which fields match your planets.\n\n"
        ."Acharya Dr. Rajnath Jha then studies the timing: the dasha you are running, the transits of Jupiter and Saturn, and the muhurat for registration, opening, signing contracts or launching a product. If you have partners, their charts can be compared with yours.\n\n"
        ."If your office, shop or factory is facing repeated losses, he also reviews the Vastu of the premises — entrance, cash counter, owner's seat, storage and the north (Kuber) zone — and suggests corrections that usually need no demolition.\n\n"
        .'The advice is practical. Astrology gives timing and direction; it does not replace financial or legal advice.',
    'process' => [
        'Share your birth details and, if relevant, those of partners and the business start date.',
        'Acharya Ji studies the 2nd, 7th, 10th and 11th houses and the running dasha.',
        'Supportive and risky periods for investment and expansion are identified.',
        'Muhurat for registration, opening or contracts is suggested.',
        'Optional Vastu review of the shop or office from a floor plan.',
        'You receive a clear plan with remedies for growth.',
    ],
    'benefits' => ['Know whether business or job suits you', 'Choose the right sector and partners', 'Start on an auspicious muhurat', 'Vastu tips for the shop or office'],
    'faqs' => [
        ['Can you suggest a name for my business?', 'Yes — business name numerology is part of the session, and a full Name Analysis report is also available.'],
        ['Do you check partnership compatibility?', 'Yes. Share the partner\'s birth details and Acharya Ji compares both charts.'],
    ],
];

$c['numerology'] = [
    'body' => "Numerology studies the influence of numbers on life. Your Mulank (root number) comes from the day of birth and shows your basic nature; your Bhagyank (destiny number) comes from the full date of birth and shows the path of life; and your Namank (name number) comes from the letters of your name and shows how the world receives you.\n\n"
        ."Acharya Dr. Rajnath Jha combines Chaldean and Vedic numerology. He checks whether your name number supports your Mulank and Bhagyank, and if not, suggests a small change in spelling. The same method is used for business and brand names, mobile numbers, vehicle numbers and house numbers.\n\n"
        ."You also learn your favourable dates, days and colours, the ruling planet of your numbers, and your personal year number for the coming year — useful for planning important steps.\n\n"
        .'Numerology works without a birth time, so it is a good choice when the exact time of birth is not known.',
    'process' => [
        'Share your full date of birth and the name you use (and any business name).',
        'Mulank, Bhagyank and Namank are calculated with Chaldean and Vedic methods.',
        'Acharya Ji checks how well the numbers support each other.',
        'Name spelling corrections are suggested if needed.',
        'Lucky dates, days, colours and your personal year are explained.',
    ],
    'benefits' => ['Works without a birth time', 'Name correction for people and businesses', 'Check mobile, vehicle and house numbers', 'Plan the year with your personal year number'],
    'faqs' => [
        ['Do I need to change my name legally?', 'No. Usually a small change in the spelling you use daily (signature, business name, social media) is enough.'],
        ['Is numerology different from astrology?', 'Yes, it is a separate system based on numbers, but Acharya Ji uses both together when a birth chart is available.'],
    ],
];

$c['palmistry'] = [
    'summary' => 'Palm reading by Acharya Ji\'s team — send photos of your palms from anywhere for a written report, show your palms on a video call, or visit the Patna office.',
    'body' => "Hastrekha Shastra (palmistry) reads the lines, mounts, shape and special signs of your hands. Your palms change slowly through life, so they show both what you were born with and the direction you are moving in. Palmistry works well alongside the horoscope and is especially useful when the birth time is not known.\n\n"
        ."You can use this service from anywhere. Choose the online option, pay, and upload one clear photo of each palm from your phone — your account takes you to the upload page right after payment. Acharya Ji's team studies the life, head, heart, fate and sun lines, the planetary mounts (Guru, Shani, Surya, Budh, Shukra, Chandra and Mangal), marriage and relationship lines, and special signs such as the fish, trident, star and cross, and writes your report by hand — no computer reading.\n\n"
        ."Prefer to talk? Book a video call and show your palms to the camera, an audio call (after sending photos), or meet in person at the Patna office.\n\n"
        .'If a photo is not clear, we will ask you for a new one; your palm photos are private and are deleted automatically after the report period.',
    'process' => [
        'Choose "Online — written report from palm photos", or a video, audio or in-person session.',
        'Pay securely online.',
        'For the online report: upload a clear photo of the left and right palm from your phone, tell us which hand you write with, and add one question.',
        'Acharya Ji\'s team studies the lines, mounts and signs.',
        'Your report appears in your account with a PDF; you get a notification and email.',
        'For a call: show your palms on video, or meet Acharya Ji in Patna.',
    ],
    'benefits' => ['Works without a birth time', 'Use it from anywhere — only two phone photos needed', 'Written by a person, not generated by a computer', 'Answer to one question you ask', 'Hindi or English'],
    'faqs' => [
        ['How do I take a good palm photo?', 'Use daylight or a bright room, hold the palm flat and open, fill the frame with the whole palm and fingers, and keep the camera straight above the hand. No flash.'],
        ['Which hand is read?', 'Both. The hand you write with shows your present and future; the other hand shows what you were born with.'],
        ['How long does the written report take?', 'Usually 5–7 working days after clear photos are received.'],
        ['Are my photos safe?', 'Yes. They are stored privately, seen only by the team preparing your report, and deleted automatically after the report period.'],
    ],
    'includes' => ['Life, head, heart, fate and sun lines', 'All seven planetary mounts', 'Marriage and relationship lines', 'Special signs and their meaning', 'Answer to one question you ask', 'PDF report in Hindi or English (online option)'],
    'audience' => ['People anywhere in India or abroad', 'People who do not know their birth time', 'Anyone wanting a second view alongside the horoscope'],
    'disclaimer' => 'Palmistry is a traditional interpretive practice. It is not a medical, legal or financial assessment. Photos are kept private and deleted automatically after the report period.',
];

$c['baby-muhurat-consultation'] = [
    'body' => "The sixteen samskaras mark the important steps in a child's life. Doing them at an auspicious time (muhurat) is an old tradition meant to bless the child with health, intelligence and a good start.\n\n"
        ."Acharya Dr. Rajnath Jha checks the Panchang — tithi, vara, nakshatra, yoga and karana — together with the child's birth nakshatra and the family's charts, to suggest dates and times for Namkaran (naming), Annaprashan (first rice), Mundan (first haircut), Karnavedha (ear piercing), Vidyarambh (start of learning) and Upanayan.\n\n"
        ."For Namkaran he also suggests the first syllable of the name from the child's birth nakshatra pada, and if you wish, a Baby Name report with meaningful names.\n\n"
        .'If the child was born in a Gand Mool nakshatra (Ashwini, Ashlesha, Magha, Jyeshtha, Mool or Revati), he explains when the Gand Mool Shanti puja should be done.',
    'process' => [
        'Share the child\'s birth details and the parents\' details.',
        'Acharya Ji studies the Panchang and the child\'s nakshatra.',
        'Suitable dates and time windows are chosen for each samskara you need.',
        'He explains the simple puja to do on the day, or arranges the puja for you.',
    ],
    'benefits' => ['Correct muhurat for every samskara', 'First syllable of the name from the nakshatra', 'Advice on Gand Mool Shanti if needed', 'Simple puja guidance for the family'],
    'faqs' => [
        ['Can you choose a date for a caesarean birth?', 'No. The timing and method of childbirth are medical decisions for your doctors and must never be changed for astrological reasons.'],
        ['My child was born in Mool nakshatra. What should I do?', 'Gand Mool Shanti puja is traditionally done on about the 27th day when the Moon returns to the same nakshatra. Acharya Ji will give the exact date.'],
    ],
];

$c['tarot-reading'] = [
    'body' => "Tarot is a system of 78 picture cards — 22 Major Arcana and 56 Minor Arcana in four suits (Wands, Cups, Swords and Pentacles). Each card carries a meaning, and the way the cards fall in a spread shows the energies around your question: what is happening now, what is hidden, and the likely direction if things continue.\n\n"
        ."In a live reading the reader shuffles and draws the cards for your question on camera (or describes them on an audio call or chat), using spreads such as the three-card past–present–future, the Celtic Cross, or a relationship spread. Questions about love, career, money, studies and choices between two options work best.\n\n"
        ."Tarot does not need your birth details, which makes it quick and easy. For timing and deeper life questions, Acharya Ji recommends a Kundali reading as well.\n\n"
        .'Tarot is offered online by video, audio or chat for 30 minutes, so you can use it from anywhere.',
    'process' => [
        'Choose video, audio or chat and pick a free time.',
        'Pay online and write your question in the booking.',
        'At the booked time the reader shuffles and draws the cards for you.',
        'Each card and its position in the spread is explained.',
        'You get guidance and simple steps you can take.',
    ],
    'benefits' => ['No birth details needed', 'Clear guidance on one or two focused questions', 'Live and interactive — from anywhere', 'Short and affordable'],
    'faqs' => [
        ['How many questions can I ask?', 'Two or three focused questions in 30 minutes.'],
        ['Can tarot predict dates?', 'Tarot shows direction and energy rather than exact dates. For timing, a Kundali reading is better.'],
    ],
    'includes' => ['30-minute live reading by video, audio or chat', 'Spreads chosen for your question', 'Explanation of each card', 'Practical guidance'],
    'audience' => ['Anyone with a question on love, career or a decision', 'People who do not know their birth time', 'Those who want a quick, focused reading'],
    'disclaimer' => 'Tarot is a traditional interpretive practice for reflection and guidance. It does not replace medical, legal or financial advice.',
];

/* ───────────── Vastu and remedies ───────────── */

$c['vastu-consultation'] = [
    'summary' => 'Vastu for homes, flats, shops, offices, factories and plots — online from your floor plan, or a site visit in Patna, elsewhere in Bihar or outside Bihar.',
    'body' => "Vastu Shastra is the Indian science of space. It divides a building into sixteen zones around the Brahmasthan (centre), each linked to an element (earth, water, fire, air, space), a direction and a part of life — wealth in the north, health in the north-east, fame in the south, relationships in the south-west and so on. When important rooms or objects are in the wrong zone, people often face repeated problems that have no obvious cause.\n\n"
        ."Acharya Dr. Rajnath Jha studies the entrance, kitchen, bedrooms, toilets, staircase, water tank, puja room, cash locker and the slope and shape of the plot. He then gives a zone map and corrections that usually need no demolition — changes in placement and colour, elemental balancing, metal strips, mirrors and Vastu pyramids.\n\n"
        ."Online review: send a floor plan with the north direction marked (a phone compass reading is enough) and photos; Acharya Ji explains the findings on a 45-minute video call.\n\n"
        .'Site visit: Acharya Ji visits the property, takes compass readings and checks the surroundings. Prices: Patna ₹11,000; elsewhere in Bihar ₹15,000; outside Bihar ₹25,000. After payment our team calls you within one working day to fix the visit date.',
    'process' => [
        'Choose online review or a site visit (Patna, elsewhere in Bihar, or outside Bihar).',
        'Pay online. For a visit, our team calls you within one working day to fix the date.',
        'Online: share the floor plan with north marked and photos. Visit: Acharya Ji takes compass readings on site.',
        'All sixteen zones, the entrance, kitchen, bedrooms, toilets and staircase are checked.',
        'You receive a zone map and no-demolition corrections.',
        'Remedy installation muhurat (pyramids, yantras) is suggested where needed.',
    ],
    'benefits' => ['Find the hidden cause of repeated problems at home or work', 'Corrections mostly without demolition', 'Plot selection and planning before construction', 'Vastu for shops, offices and factories too'],
    'faqs' => [
        ['I live in a rented flat. Can Vastu help?', 'Yes. Most corrections are placement, colour and pyramid remedies that need no construction.'],
        ['What do I need for an online review?', 'A floor plan (even a hand drawing with measurements) with the north direction marked, and a few photos of the entrance and main rooms.'],
        ['What does the site visit price include?', 'The visit and inspection by Acharya Ji and the written corrections. Travel within the option you choose is included.'],
        ['How soon can a visit happen?', 'Usually within a week in Patna; outside Patna it is planned with you.'],
    ],
    'includes' => ['Sixteen-zone analysis with a zone map', 'Entrance, kitchen, bedroom, toilet and staircase corrections', 'No-demolition remedies', 'Vastu for homes, shops, offices and factories', 'Plot selection and pre-construction planning'],
];

$c['gemstone-guidance'] = [
    'body' => "A gemstone strengthens the planet it belongs to. That is why the wrong stone can do harm — for example, wearing a Blue Sapphire when Saturn is a malefic for your Lagna. Acharya Dr. Rajnath Jha recommends a gemstone only after studying your Lagna, the planet's lordship and strength, the running dasha and your goals.\n\n"
        ."The recommendation is complete: the stone (or a cheaper uparatna alternative), its weight in carats or ratti, the metal (gold, silver, panchdhatu), the finger, the day and hora for first wearing, the mantra and how many times to chant it, and how to purify (energise) the stone with Ganga jal and raw milk.\n\n"
        ."He also tells you which stones you should avoid, and checks stones you are already wearing.\n\n"
        .'Certified natural gemstones are available in our store. Prices depend on weight, colour, clarity and origin.',
    'process' => [
        'Share your birth details and any stones you already wear.',
        'Acharya Ji studies the Lagna, planetary lordship, strength and dasha.',
        'He recommends the stone or uparatna, weight, metal and finger.',
        'You receive the day, muhurat and mantra for wearing it.',
        'Optionally buy a certified stone from our store.',
    ],
    'benefits' => ['No guesswork — stones chosen from your chart', 'Exact weight, metal, finger and wearing muhurat', 'Affordable uparatna options', 'Check stones you already wear'],
    'faqs' => [
        ['Can I wear a stone for my zodiac sign?', 'Not safely. The right stone depends on your Lagna and planetary strengths, not only the Moon sign.'],
        ['How soon does a gemstone show results?', 'Usually within a few weeks of wearing it correctly. The effect depends on the planet\'s dasha and transits.'],
    ],
];

$c['vastu-pyramid'] = [
    'body' => "Vastu pyramids are a no-construction remedy. A pyramid shape is believed to focus and balance energy, and placing the right pyramids in the right zones can correct Vastu defects such as a toilet in the north-east, a cut or extended corner, a south-west entrance or a kitchen in the wrong zone.\n\n"
        ."Based on your floor plan, Acharya Dr. Rajnath Jha recommends the type (single, 9×9 multi-pyramid plates, pyramid strips), the material (copper, brass, crystal, wood), the number of pyramids, and the exact placement zones — on the floor, under a bed, on the ceiling or along a wall.\n\n"
        .'He also gives the muhurat for installation and a short puja so the remedy is done properly. This suits rented homes, flats, offices and shops where structural changes are not possible.',
    'process' => ['Share your floor plan with north marked.', 'Acharya Ji identifies the defective zones.', 'He recommends pyramid type, material, quantity and placement.', 'You receive the installation muhurat and simple puja.'],
    'benefits' => ['Corrects Vastu defects without construction', 'Ideal for rented homes and offices', 'Exact placement plan', 'Installation muhurat included'],
    'faqs' => [['Can I buy the pyramids from you?', 'Yes, Vastu pyramids and yantras are available in our store, or you may buy them locally to the specification given.']],
];

$c['personalized-mantra'] = [
    'body' => "A mantra is a sacred sound repeated with attention and faith. The right mantra at the right time can bring calm, strength and protection — but which mantra suits you depends on your chart, your running dasha and your own inclination.\n\n"
        ."After studying your horoscope, Acharya Dr. Rajnath Jha prescribes a personal mantra: an Ishta devata mantra for daily practice, and beej mantras for planets that need strengthening. He explains the correct pronunciation (uchcharan), the viniyog, the mala to use (rudraksha, tulsi, sphatik or chandan), the direction to face, the best time of day, and the number of repetitions — daily and for a full anushthan.\n\n"
        .'The routine is kept simple enough to follow at home, even on busy days.',
    'process' => ['Share your birth details and your spiritual inclination.', 'Acharya Ji studies the chart and the running dasha.', 'He chooses your Ishta mantra and planetary beej mantras.', 'You learn the pronunciation, mala, direction, time and count.'],
    'benefits' => ['A mantra chosen for you, not a generic one', 'Correct pronunciation and method', 'Simple daily routine', 'Can be practised anywhere'],
    'faqs' => [['Do I need initiation (diksha)?', 'For most mantras given here, no. Where a mantra traditionally needs diksha, Acharya Ji will tell you.']],
];

return $c;
