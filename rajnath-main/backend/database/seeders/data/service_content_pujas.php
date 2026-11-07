<?php

// Detailed text for pujas, paths and chadhavas (Nov 2026). Common steps (sankalp, photos,
// prasad) are added by $steps(); each service lists its own vidhi in the middle.

$steps = fn (array $vidhi, string $day = 'a muhurat chosen from the Panchang') => array_merge(
    ["Acharya Ji fixes $day and confirms it with you.", 'Ganesh puja, Gauri puja, kalash sthapana and sankalp in the names and gotra you send.'],
    $vidhi,
    ['Purnahuti, aarti and prayers for your wish; daan on your behalf where prescribed.', 'Photos (and video where possible) added to your account; prasad posted with the Family package.'],
);
$remote = ['Do I need to attend?', 'No. The puja is performed in your name and gotra by Acharya Dr. Rajnath Jha and the priests of Jyotirveda Vigyan Sansthan. You get the date and live link where possible, and photos in your account. You are welcome to attend in Patna.'];

$c = [];

$c['puja-rituals'] = [
    'body' => "Acharya Dr. Rajnath Jha has performed and supervised Vedic rituals for more than 30 years, including 17 years as Jyotish and Karmakand consultant at Jyotish Mandap, Mahavir Mandir Trust, Patna. Every puja follows the complete shastriya vidhi with the correct muhurat, samagri and mantra.\n\n"
        ."Book him for any puja or samskara at your home or office in Patna, or at a temple arranged with you: Satyanarayan Katha, Rudrabhishek, Sundarkand, Navgrah Shanti, Vastu Shanti, Griha Pravesh, Bhoomi Pujan, Vivah, Mundan, Namkaran, Upanayan, Shraddh karma and festival pujas.\n\n"
        .'Our team tells you the samagri list in advance, or brings the complete samagri for you. After booking, the date and time are fixed together with you from the Panchang.',
    'process' => ['Choose at your home/office (Patna) or at a temple.', 'Pay online and tell us which puja and the preferred date.', 'Acharya Ji fixes the muhurat and shares the samagri list.', 'The puja is performed with full vidhi, mantra and havan.', 'Prasad and blessings for the family.'],
    'benefits' => ['Experienced acharya for every ritual and samskara', 'Correct muhurat and complete vidhi', 'Samagri list or full samagri arranged', 'At home, office or temple'],
    'faqs' => [['Can you perform pujas outside Patna?', 'Yes, by arrangement. For online pujas performed in your name, choose the specific puja service.'], ['Do you bring the samagri?', 'We can bring the full samagri for you or give you a list to arrange it yourself.']],
];

$c['durga-saptashati-path'] = [
    'body' => "The Durga Saptashati, also called Devi Mahatmya or Chandi Path, is a sacred text of 700 verses in 13 chapters from the Markandeya Purana. It tells how Maa Durga defeated Madhu–Kaitabh, Mahishasura and Shumbh–Nishumbh, and it is recited for protection, courage, victory over obstacles and the blessings of the Divine Mother.\n\n"
        ."Acharya Dr. Rajnath Jha's team performs the complete path in the traditional order: Kavach, Argala and Kilak stotras, Navarna mantra jaap (\"Om Aim Hreem Kleem Chamundayai Vichche\"), the three charitras — Pratham (Mahakali), Madhyam (Mahalakshmi) and Uttam (Mahasaraswati) — followed by the Devi Suktam, Kshama Prarthana and a havan with the Saptashati mantras.\n\n"
        .'The path is especially powerful in Navratri, on Ashtami and Navami, on Tuesdays and Fridays, and during difficult periods of Rahu or Shani. It is also done for health, for success in a court case or competition, and for peace in the home.',
    'process' => $steps(['Durga puja with red flowers, chunri and shringar.', 'Kavach, Argala and Kilak stotras.', 'Navarna mantra jaap.', 'Recitation of all 13 chapters (700 verses).', 'Devi Suktam, Siddha Kunjika Stotra and havan with Saptashati mantras.'], 'a Navratri day, Ashtami, Tuesday or Friday'),
    'benefits' => ['Protection from negativity and enemies', 'Courage and strength in difficult times', 'Reduces the troubles of Rahu and Shani', 'Peace and prosperity in the home'],
    'faqs' => [$remote, ['How long does the path take?', 'A complete path takes about 3–4 hours including the havan. For the Family package the path can be done over more than one day.']],
];

$c['shrimad-bhagavad-gita-path'] = [
    'body' => "The Shrimad Bhagavad Gita is Lord Krishna's teaching to Arjuna on the battlefield of Kurukshetra — 700 verses in 18 chapters on duty (karma), knowledge (jnana) and devotion (bhakti). Reciting or hearing the full Gita brings clarity of mind, courage to face decisions and inner peace.\n\n"
        ."A Gita path is also traditionally done for the peace of departed souls — on the death anniversary, during Pitru Paksha or after a death in the family — and at the start of important work, a new home or a new year.\n\n"
        ."Acharya Ji's team recites all 18 chapters with the Gita Dhyanam and Gita Mahatmya, offers tulsi and panchamrit to Lord Krishna, and completes the path with a havan and the Vishnu Sahasranama.",
    'process' => $steps(['Shri Krishna puja with tulsi, panchamrit and makhan-mishri.', 'Gita Dhyanam and Gita Mahatmya.', 'Recitation of all 18 chapters.', 'Vishnu Sahasranama and havan.'], 'Ekadashi, a Thursday or the date you need'),
    'benefits' => ['Peace and clarity of mind', 'Shanti for departed family members', 'Blessings for a new beginning', 'Strength to handle difficult decisions'],
    'faqs' => [$remote, ['Can the path be done for a family member who has passed away?', 'Yes. Give their name in the sankalp; the path is done for the peace of their soul.']],
];

$c['gand-mool-nakshatra-shanti-puja'] = [
    'body' => "Of the 27 nakshatras, six lie at the junction (gandanta) of a water sign and a fire sign: Ashwini, Ashlesha, Magha, Jyeshtha, Mool and Revati. A child born in one of these is said to be born in Gand Mool. Tradition holds that such a birth can bring strain to the child or the family — for example to the father with Mool, or the mother with Ashlesha — depending on the nakshatra pada.\n\n"
        ."The remedy is the Gand Mool (Mool) Shanti puja, done when the Moon returns to the same nakshatra, about 27 days after birth. If that date has passed, it can be done in any later month when the Moon is in the birth nakshatra.\n\n"
        ."Acharya Dr. Rajnath Jha's team performs the puja of the nakshatra deity, the jaap of the nakshatra mantra, Navgrah puja and a havan with 27 kinds of samagri and water from 27 sources (as available), in the names of the child and parents. The child's father traditionally sees the child's face through a reflection in oil during the puja.\n\n"
        .'Not every Gand Mool birth needs the same remedy — the pada matters. Acharya Ji checks the birth details first and tells you whether the puja is needed and on which date.',
    'process' => $steps(['Check of the child\'s nakshatra and pada.', 'Puja of the nakshatra deity and Navgrah.', 'Jaap of the nakshatra mantra (typically 27 × 108).', 'Abhishek of the child and parents with water from many sources and 27 kinds of samagri.', 'Havan with nakshatra samagri.'], 'the day the Moon returns to the birth nakshatra'),
    'benefits' => ['Traditional remedy for a Gand Mool birth', 'Protection and good health for the child', 'Peace of mind for the parents and family', 'Done on the correct nakshatra day'],
    'faqs' => [
        ['How do I know if my child is born in Gand Mool?', 'Use the free Nakshatra calculator on this site, or send the birth details with your booking; Acharya Ji confirms the nakshatra and pada.'],
        ['The 27th day has passed. Is it too late?', 'No. The puja can be done in a later month on the day the Moon is in the birth nakshatra.'],
        $remote,
    ],
    'includes' => ['Check of nakshatra and pada', 'Nakshatra deity and Navgrah puja', 'Nakshatra mantra jaap and havan', 'Sankalp in the names of the child and parents', 'Photos and video link in your account'],
    'audience' => ['Parents of a child born in Ashwini, Ashlesha, Magha, Jyeshtha, Mool or Revati', 'Adults born in Gand Mool who never had the shanti done'],
];

$c['griha-pravesh-puja'] = [
    'body' => "Griha Pravesh is the first entry into a new home. Shastra describes three kinds: Apurva (a newly built house), Sapurva (returning to a house after a long time) and Dvandvah (after repair or renovation). Each is done on a muhurat chosen from the Panchang — avoiding Kharmas, Pitru Paksha and inauspicious tithis — and matched with the owner's chart.\n\n"
        ."Acharya Dr. Rajnath Jha performs the complete ritual at your home: Ganesh puja, Punyahavachan, Vastu Purush puja and Vastu Shanti, Navgrah puja, a havan, and the entry of the family with a kalash of water, the right foot first, followed by boiling milk in the new kitchen and a Satyanarayan Katha if you wish.\n\n"
        .'Prices: at your home in Patna ₹11,000; elsewhere in Bihar ₹15,100; outside Bihar ₹21,000 (travel included). Samagri can be arranged by us. Our team calls you after payment to fix the muhurat and share the samagri list.',
    'process' => $steps(['Punyahavachan and Vastu Purush puja.', 'Vastu Shanti and Navgrah puja.', 'Havan for the house and family.', 'Entry with kalash, right foot first; milk boiled in the new kitchen.', 'Optional Satyanarayan Katha.'], 'a Griha Pravesh muhurat for your family'),
    'benefits' => ['Begin life in the new home on an auspicious muhurat', 'Removes Vastu doshas of the building', 'Peace, health and prosperity for the family', 'Complete ritual by an experienced acharya'],
    'faqs' => [
        ['Which months are good for Griha Pravesh?', 'Usually Magha, Phalguna, Vaishakha and Jyeshtha; Kharmas, Chaturmas and Pitru Paksha are avoided. Acharya Ji gives exact dates for your family.'],
        ['Can it be done in a rented flat?', 'Yes, a simpler Griha Pravesh with Vastu Shanti is often done when moving into a rented home.'],
    ],
    'includes' => ['Muhurat for your family', 'Ganesh, Vastu and Navgrah puja', 'Havan and kalash entry', 'Samagri list or full samagri', 'Acharya Ji at your home'],
    'audience' => ['Families moving into a new or renovated home', 'Owners of a new flat or office'],
];

$c['griha-aarambh-bhoomi-pujan'] = [
    'body' => "Griha Aarambh (Bhoomi Pujan and Shilanyas) is the puja done before construction begins. It asks permission of Bhoomi Devi (Mother Earth) and the Vastu Purush, removes defects of the land (bhoomi dosh), and blesses the work so it is completed without accidents or delays.\n\n"
        ."Acharya Dr. Rajnath Jha chooses the muhurat from the Panchang and the owner's chart, and checks the direction for the first digging (usually the north-east). At the site he performs Ganesh puja, Bhoomi puja, Vastu Purush and Naag puja, places the foundation stone (shila) with a kalash, coins, panchratna and grains, and completes the ritual with a havan.\n\n"
        .'He also gives Vastu guidance for the plot — slope, shape, entrance and the placement of rooms — so the building starts right. Prices: Patna ₹11,000; elsewhere in Bihar ₹15,100; outside Bihar ₹21,000.',
    'process' => $steps(['Direction check for the first digging (usually north-east).', 'Bhoomi Devi, Vastu Purush and Naag puja.', 'Foundation stone with kalash, panchratna, coins and grains.', 'Havan at the site.'], 'a Bhoomi Pujan muhurat for the owner'),
    'benefits' => ['Removes defects of the land', 'Construction starts on an auspicious muhurat', 'Blessing for safe, timely completion', 'Vastu guidance for the plot'],
    'faqs' => [['Is Vastu advice for the plan included?', 'Basic Vastu guidance for the plot is included. For a full plan review choose the Vastu Consultation.']],
    'includes' => ['Muhurat and digging direction', 'Bhoomi, Vastu and Naag puja', 'Foundation stone ceremony', 'Havan at the site', 'Basic Vastu guidance'],
    'audience' => ['Families starting to build a house', 'Builders and business owners starting a project'],
];

$c['nyayik-uljhan-nivaran-puja'] = [
    'body' => "A long court case or legal dispute drains money, time and peace of mind. In Jyotish, legal trouble is linked to the 6th house (disputes and enemies), the 8th house (obstacles), Saturn (justice and delay), Rahu (deception) and Mars (conflict). When these are active in the dasha or transit, cases tend to drag on.\n\n"
        ."Nyayik Uljhan Nivaran Puja combines the remedies traditionally used for legal matters: worship of Maa Baglamukhi, the goddess who \"stops the tongue\" of opponents, with the jaap of her mantra; Hanuman puja with Sundarkand for courage and protection; and Shani or Rahu shanti as your chart shows.\n\n"
        ."Acharya Dr. Rajnath Jha first studies your chart to see which planets are involved and chooses the right combination. The puja supports you spiritually; please continue to follow your lawyer's advice.",
    'process' => $steps(['Chart check: 6th house, Saturn, Rahu and Mars.', 'Maa Baglamukhi puja with yellow flowers and haldi, and her mantra jaap.', 'Hanuman puja and Sundarkand path.', 'Shani or Rahu shanti as indicated.', 'Havan with yellow mustard and samagri for victory.'], 'a Tuesday, Thursday or Saturday in a favourable muhurat'),
    'benefits' => ['Spiritual support during a court case', 'Protection from opponents and false charges', 'Calm and confidence to face hearings', 'Remedies chosen from your chart'],
    'faqs' => [$remote, ['Will the puja guarantee that I win?', 'No ritual can guarantee a court result. The puja is spiritual support; follow your lawyer\'s advice.']],
    'disclaimer' => 'Rituals are performed as a spiritual practice. They are not a guaranteed outcome and do not replace legal advice.',
];

$c['mahamrityunjay-puja'] = [
    'body' => "The Mahamrityunjaya mantra — \"Om Tryambakam Yajamahe Sugandhim Pushtivardhanam, Urvarukamiva Bandhanan Mrityor Mukshiya Maamritat\" — from the Rigveda is a prayer to Lord Shiva, the three-eyed one, for health, long life and freedom from fear. Its jaap is the classical remedy in serious illness, accidents, dangerous dasha periods (such as a maraka dasha), and when the 8th house or Saturn is heavily afflicted.\n\n"
        ."Acharya Dr. Rajnath Jha and a group of priests perform the jaap with full vidhi: Shiva puja and Rudrabhishek, sankalp for the person, the samput (enclosed) Mahamrityunjaya mantra jaap with rudraksha mala, and a havan with til, ghee and guggul. The full anushthan is 1,25,000 jaap; the package sets the count and the number of priests.\n\n"
        .'Many families do this puja for a member in hospital, before major surgery, or on a birthday for long life. It supports medical treatment and does not replace it.',
    'process' => $steps(['Shiva puja and Rudrabhishek with milk, Ganga jal and bilva leaves.', 'Samput Mahamrityunjaya mantra jaap with rudraksha mala by a team of priests.', 'Dashamsh havan with til, ghee and guggul.', 'Tarpan and marjan as per vidhi.'], 'a Monday, Pradosh or Shivratri muhurat'),
    'benefits' => ['Prayer for health and recovery', 'Protection in dangerous dasha periods', 'Removes fear and anxiety', 'Blessing for long life'],
    'faqs' => [$remote, ['How many jaap are done?', 'The count depends on the package; the full anushthan is 1,25,000. Acharya Ji tells you the exact count when the puja is scheduled.'], ['Can it be done for someone in hospital?', 'Yes. Give their name and gotra in the sankalp. The puja supports medical care and never replaces it.']],
];

$c['rudrabhishek'] = [
    'body' => "Rudrabhishek is the bathing (abhishek) of the Shivling while the Rudram — the Namakam and Chamakam of the Shukla Yajurveda — is chanted. It is among the most loved Shiva pujas and is done for peace, health, removal of planetary troubles and fulfilment of wishes.\n\n"
        ."Different substances are used for different wishes: milk for peace and health, curd for children, ghee for wealth, honey for sweetness in relationships, sugarcane juice for prosperity, Ganga jal for purification and mustard oil to calm Shani.\n\n"
        ."Acharya Ji's team performs the abhishek with panchamrit and the chosen dravya, offers bilva patra, dhatura and bhasma, chants the Rudrashtadhyayi and completes the puja with aarti. Mondays, Pradosh, Masik Shivratri and the month of Shravan are especially auspicious.",
    'process' => $steps(['Shiva parivar puja.', 'Abhishek with panchamrit and the dravya for your wish.', 'Chanting of the Rudrashtadhyayi (Namakam and Chamakam).', 'Offering of bilva patra, dhatura, bhasma and flowers.'], 'a Monday, Pradosh or Shravan muhurat'),
    'benefits' => ['Blessings of Lord Shiva for peace and health', 'Calms the effects of Moon, Saturn and Rahu', 'Removes obstacles in work', 'Fulfils sincere wishes'],
    'faqs' => [$remote, ['Which abhishek should I choose?', 'Tell us your wish in the sankalp; Acharya Ji chooses the dravya (milk, ghee, honey, sugarcane juice and so on).']],
];

$c['kaal-sarp-dosh-nivaran-puja'] = [
    'body' => "Kaal Sarp Dosh is said to be present when all seven planets lie between Rahu and Ketu on one side of the chart. Depending on where Rahu sits, there are twelve kinds — Anant, Kulik, Vasuki, Shankhpal, Padma, Mahapadma, Takshak, Karkotak, Shankhchud, Ghatak, Vishdhar and Sheshnag. People with a strong Kaal Sarp often describe repeated struggle, delays despite effort, fear and disturbed sleep.\n\n"
        ."Not every Kaal Sarp is equally strong, and many are partial or cancelled. Acharya Dr. Rajnath Jha first checks your chart, then performs the Nivaran puja: Shiva and Naag devata puja, Rahu–Ketu shanti with their beej mantra jaap, the worship of the silver Naag–Nagin pair, Rudrabhishek and a havan.\n\n"
        .'The puja is traditionally done on Naag Panchami, Amavasya, Mahashivratri or a Monday, and the silver naag pair is later offered in flowing water or at a Shiva temple.',
    'process' => $steps(['Chart check of the type and strength of Kaal Sarp.', 'Shiva and Naag devata puja with the silver Naag–Nagin pair.', 'Rahu and Ketu beej mantra jaap.', 'Rudrabhishek with milk and Ganga jal.', 'Havan with sarpa-shanti samagri.'], 'Naag Panchami, Amavasya, Shivratri or a Monday'),
    'benefits' => ['Reduces the struggle and delays linked to Kaal Sarp', 'Calms Rahu and Ketu', 'Relief from fear and bad dreams', 'Stability in career and family life'],
    'faqs' => [
        ['How do I know if I have Kaal Sarp Dosh?', 'Use the free Kaal Sarp calculator on this site or book a Kundli reading; Acharya Ji confirms the type and whether it is cancelled.'],
        ['Is it done only once?', 'Usually once in life is enough, with simple daily remedies afterwards.'],
        $remote,
    ],
];

$c['kaal-bhairav-puja'] = [
    'body' => "Kaal Bhairav is the fierce form of Lord Shiva, the guardian (Kotwal) of Kashi and the lord of time. His worship is done to remove fear, protect against enemies and negative energy (nazar, tantra-badha), clear long-standing obstacles and calm Rahu and Shani.\n\n"
        ."This is a detailed anushthan performed by Acharya Dr. Rajnath Jha with experienced priests: Bhairav puja with mustard-oil diya, black til, urad, imarti and coconut, the jaap of the Bhairav mantra (\"Om Hreem Batukaya Apaduddharanaya Kuru Kuru Batukaya Hreem\") and the Kaal Bhairav Ashtakam, Batuk Bhairav stotra and a night havan.\n\n"
        .'Kaal Bhairav Ashtami (Margashirsha Krishna Ashtami), every Krishna Ashtami, Sundays and Tuesdays are the traditional days. Because of the length of the jaap and havan, the price covers several priests over the anushthan.',
    'process' => $steps(['Bhairav puja with mustard-oil diya, black til, urad, imarti and coconut.', 'Bhairav mantra jaap by a team of priests.', 'Kaal Bhairav Ashtakam and Batuk Bhairav stotra.', 'Havan with black til and protective samagri.', 'Bhog for dogs (Bhairav\'s vahan) on your behalf.'], 'Kaal Bhairav Ashtami, a Krishna Ashtami, Sunday or Tuesday'),
    'benefits' => ['Protection from enemies, nazar and negative energy', 'Removes fear and long-standing obstacles', 'Calms Rahu and Shani', 'Courage and confidence'],
    'faqs' => [$remote, ['Why is this puja priced higher?', 'It is a full anushthan with a large jaap count and havan by several priests over the anushthan period.']],
];

$c['maa-baglamukhi-puja'] = [
    'body' => "Maa Baglamukhi (Pitambara) is the eighth of the ten Mahavidyas. She is shown in yellow, holding the tongue of a demon — the power that silences enemies, stops harmful speech and gives victory in disputes, court cases, elections and competitions.\n\n"
        ."This is a full anushthan led by Acharya Dr. Rajnath Jha: Baglamukhi puja with yellow clothes, haldi, yellow flowers, chana dal and besan laddoo; jaap of the 36-letter Baglamukhi mantra with a haldi mala by a team of priests; Baglamukhi Kavach and Stotra; and a havan with yellow mustard, haldi and ghee.\n\n"
        .'It is traditionally done on Thursdays, Tuesdays, Baglamukhi Jayanti and in Navratri. Because the jaap count and the number of priests are large, the price reflects a complete anushthan.',
    'process' => $steps(['Baglamukhi puja in yellow with haldi, chana dal and yellow flowers.', 'Jaap of the 36-letter Baglamukhi mantra with haldi mala by a team of priests.', 'Baglamukhi Kavach and Stotra.', 'Havan with yellow mustard, haldi and ghee.'], 'a Thursday, Tuesday, Navratri or Baglamukhi Jayanti'),
    'benefits' => ['Victory over enemies and opponents', 'Support in court cases, elections and competitions', 'Stops gossip and harmful speech', 'Protection and self-confidence'],
    'faqs' => [$remote, ['Is this the same as Nyayik Uljhan puja?', 'Maa Baglamukhi is part of the court-case puja, but this is a complete Baglamukhi anushthan with a much larger jaap.']],
];

$c['rin-mukti-puja'] = [
    'body' => "Debt that keeps growing despite effort is one of the most stressful problems. In Jyotish the 6th house rules debt, and Mars (Mangal) is the karaka of loans; a troubled 6th house or Mars, or the 2nd and 11th houses being weak, can make it hard to repay.\n\n"
        ."Rin Mukti Puja is done to help clear debts and stop new ones. Acharya Ji's team performs Rin Mochan Mangal puja with the recitation of the Rin Mochan Mangal Stotra, Hanuman puja, Lakshmi–Ganesh puja for steady income and a havan. It is traditionally begun on a Tuesday.\n\n"
        .'Alongside the puja, Acharya Ji suggests simple steps like reciting the Rin Mochan Stotra on Tuesdays and repaying the first instalment on a Tuesday.',
    'process' => $steps(['Rin Mochan Mangal puja and stotra.', 'Hanuman puja with sindoor and jasmine oil.', 'Lakshmi–Ganesh puja for income.', 'Havan with red samagri.'], 'a Tuesday'),
    'benefits' => ['Spiritual support to clear debts', 'Calms an afflicted Mars and 6th house', 'Steady income and savings', 'Relief from financial stress'],
    'faqs' => [$remote],
];

$c['vivah-siddhi-puja'] = [
    'body' => "When marriage is delayed despite good proposals, the cause in the chart is often a weak 7th house or 7th lord, Mangal dosh, the influence of Saturn or Rahu on the 7th house, or a weak Jupiter (for women) or Venus (for men).\n\n"
        ."Vivah Siddhi Puja seeks the blessings of Lord Shiva and Maa Parvati for a suitable match and a happy marriage. Acharya Ji's team performs Gauri–Shankar puja, the jaap of the Katyayani mantra (\"Katyayani Mahamaye Mahayoginyadhishwari, Nandagopasutam Devi Patim Me Kuru Te Namah\") for girls or the Parvati Vallabh mantra for boys, Guru or Shukra shanti as needed, and a havan.\n\n"
        .'The puja is best begun on a Monday or Thursday; simple fasts and prayers to continue at home are suggested.',
    'process' => $steps(['Gauri–Shankar puja with shringar for Maa Parvati.', 'Katyayani mantra jaap (girls) or Parvati Vallabh mantra jaap (boys).', 'Guru or Shukra shanti as indicated by the chart.', 'Havan for marriage.'], 'a Monday or Thursday'),
    'benefits' => ['Removes delays in marriage', 'Blessings for a suitable partner', 'Calms Mangal dosh and 7th-house afflictions', 'Family harmony'],
    'faqs' => [$remote, ['Should I check my chart first?', 'It helps. A Kundli reading shows the cause of the delay so the puja can be focused.']],
];

$c['uma-maheshwar-puja'] = [
    'body' => "Lord Shiva and Maa Parvati (Uma–Maheshwar) are the ideal divine couple. Their worship is done for harmony and love between husband and wife, to end quarrels and misunderstanding, and for couples facing separation or a delay in marriage.\n\n"
        ."Acharya Ji's team performs Uma–Maheshwar puja with shringar for Maa Parvati, Rudrabhishek, the Uma–Maheshwar stotra and mantra jaap, and a havan in the names of both partners.\n\n"
        .'Mondays, Pradosh, Teej, Shravan and Mahashivratri are especially suitable.',
    'process' => $steps(['Uma–Maheshwar puja with shringar for Maa Parvati.', 'Rudrabhishek.', 'Uma–Maheshwar stotra and mantra jaap.', 'Havan in the names of both partners.'], 'a Monday, Pradosh, Teej or Shivratri'),
    'benefits' => ['Harmony and love in marriage', 'Ends quarrels and misunderstanding', 'Blessing for couples facing separation', 'Support for delayed marriage'],
    'faqs' => [$remote],
];

$c['parivar-sukh-samriddhi-puja'] = [
    'body' => "When there is repeated tension in the family, frequent illness, money worries or lack of peace at home, a family puja brings everyone together in prayer. Parivar Sukh Samriddhi Puja seeks the blessings of Lord Ganesha, Lord Vishnu and Maa Lakshmi, the Kul Devata and the Navgrah for harmony and prosperity.\n\n"
        ."Acharya Ji's team performs Ganesh puja, Lakshmi–Narayan puja, Navgrah puja, the Purusha Suktam and Shri Suktam and a havan, with the names of all family members in the sankalp.\n\n"
        .'It is a good puja for a family anniversary, the new year, or after a difficult period.',
    'process' => $steps(['Lakshmi–Narayan puja.', 'Kul Devata and Navgrah puja.', 'Purusha Suktam and Shri Suktam.', 'Havan with the names of all family members.']),
    'benefits' => ['Peace and unity in the family', 'Prosperity and good health', 'Blessings of the Kul Devata', 'A fresh start after a hard period'],
    'faqs' => [$remote],
];

$c['santan-prapti-puja'] = [
    'summary' => 'Puja and Santan Gopal mantra jaap for couples hoping for a child, with remedies for Jupiter and the 5th house.',
    'body' => "The blessing of a child is one of life's deepest wishes. In Jyotish the 5th house and its lord, and Jupiter (Guru, the karaka of children), show the blessing of progeny. When these are weak or afflicted by Saturn, Rahu or Ketu, couples often face delays.\n\n"
        ."Santan Prapti Puja seeks the blessings of Lord Krishna as Santan Gopal. Acharya Ji's team performs Bal Gopal puja, the jaap of the Santan Gopal mantra (\"Om Devaki Sut Govind Vasudev Jagatpate, Dehi Me Tanayam Krishna Tvamaham Sharanam Gatah\"), Guru shanti, Harivansh Purana path where chosen, and a havan in the names of both partners.\n\n"
        .'Acharya Ji studies both charts first to see the cause and the supportive periods. The puja is a spiritual practice and should go hand in hand with medical advice.',
    'process' => $steps(['Study of the 5th house and Jupiter in both charts.', 'Bal Gopal (Santan Gopal) puja with makhan-mishri.', 'Santan Gopal mantra jaap.', 'Guru shanti and Harivansh path where chosen.', 'Havan in the names of both partners.'], 'a Thursday, Ekadashi or Janmashtami'),
    'benefits' => ['Prayer for the blessing of a child', 'Strengthens Jupiter and the 5th house', 'Hope and peace of mind for the couple', 'Remedies chosen from both charts'],
    'faqs' => [$remote, ['Should we continue medical treatment?', 'Yes, always. The puja is spiritual support and never a substitute for medical care.']],
    'disclaimer' => 'Rituals are performed as a spiritual practice. They are not a guaranteed outcome and do not replace medical advice.',
];

$c['sarva-karya-siddhi-puja'] = [
    'body' => "Sometimes every effort meets an obstacle — a job that never comes through, papers stuck for months, a project that keeps stalling. Sarva Karya Siddhi Puja seeks the blessings of Lord Ganesha (Vighnaharta), Lord Hanuman and the Navgrah for success in all work.\n\n"
        ."Acharya Ji's team performs Ganesh puja with the Ganapati Atharvashirsha and Sankat Nashan Ganesh Stotra, Hanuman puja with Sundarkand, Navgrah puja and a havan. The specific work you want to succeed is named in the sankalp.\n\n"
        .'It is good before an interview, exam, important meeting, new job or any new venture.',
    'process' => $steps(['Ganesh puja with Atharvashirsha and Sankat Nashan Stotra.', 'Hanuman puja and Sundarkand.', 'Navgrah puja.', 'Havan for success in the work named in the sankalp.'], 'a Wednesday or Tuesday'),
    'benefits' => ['Removes obstacles in work', 'Success in interviews, exams and projects', 'Blessings of Ganesha and Hanuman', 'Confidence for new beginnings'],
    'faqs' => [$remote],
];

$c['shatru-badha-puja'] = [
    'summary' => 'Puja for protection from enemies, conspiracies, nazar and harmful intentions — Hanuman, Baglamukhi and Bhairav worship with havan.',
    'body' => "Shatru Badha means trouble created by enemies — at work, in business, in property matters or within the extended family — as well as nazar (evil eye) and negative energy. In Jyotish the 6th house shows enemies; Mars, Saturn and Rahu acting on it, or a weak Lagna, can make a person vulnerable.\n\n"
        ."Shatru Badha Puja combines the classical protective remedies: Hanuman puja with Sundarkand and Hanuman Kavach, the jaap of Maa Baglamukhi's mantra to silence opponents, Bhairav puja, and a havan with mustard and protective samagri. A nazar-utaran is also done in your name.\n\n"
        .'Acharya Dr. Rajnath Jha checks your chart first and focuses the puja on the planets involved.',
    'process' => $steps(['Hanuman puja with Sundarkand and Hanuman Kavach.', 'Baglamukhi mantra jaap.', 'Bhairav puja.', 'Nazar-utaran and havan with mustard and protective samagri.'], 'a Tuesday or Saturday'),
    'benefits' => ['Protection from enemies and conspiracies', 'Removes nazar and negative energy', 'Calms disputes at work and in property', 'Courage and peace of mind'],
    'faqs' => [$remote],
];

$c['nasha-mukti-puja'] = [
    'summary' => 'Puja and prayer for a family member struggling with alcohol, drugs or other addiction — Hanuman, Shiva and Rahu shanti, with support from the family.',
    'body' => "Addiction to alcohol, tobacco, gambling or drugs hurts the person and the whole family. In Jyotish, a troubled Moon (the mind), Rahu (compulsion and intoxicants), Venus or the 12th house are often active when such habits take hold.\n\n"
        ."Nasha Mukti Puja is a prayer for strength of mind and freedom from addiction. Acharya Ji's team performs Hanuman puja with Sundarkand for willpower, Shiva puja with Rudrabhishek to calm the mind, Rahu shanti with its beej mantra jaap, and a havan in the name of the person.\n\n"
        .'The family is given simple daily practices — the Hanuman Chalisa, a Monday Shiva abhishek, and how to speak with the person with patience. Please combine the puja with medical help: de-addiction centres and the Tele-MANAS helpline 14416 are available free of cost.',
    'process' => $steps(['Hanuman puja and Sundarkand for willpower.', 'Rudrabhishek to calm the mind.', 'Rahu shanti and beej mantra jaap.', 'Havan in the name of the person.'], 'a Tuesday or Saturday'),
    'benefits' => ['Prayer for strength of mind', 'Calms Rahu and the Moon', 'Support and hope for the family', 'Simple daily practices to continue at home'],
    'faqs' => [
        ['Can the person\'s name be given without their knowledge?', 'Yes. A family member can give the name and gotra in the sankalp.'],
        ['Is the puja enough on its own?', 'No. Please seek medical help as well — Tele-MANAS 14416 is free and available 24×7.'],
        $remote,
    ],
    'disclaimer' => 'Rituals are performed as a spiritual practice. They are not a guaranteed outcome and do not replace medical, psychological or addiction treatment. Free help: Tele-MANAS 14416.',
];

$c['videsh-yatra-siddhi-yagya'] = [
    'body' => "Many people wish to study, work or settle abroad, and face repeated visa refusals or delays. In Jyotish, foreign travel is shown by the 12th house (distant lands), the 9th house (long journeys and fortune), the 3rd house (short journeys) and Rahu, the planet of foreign things.\n\n"
        ."Videsh Yatra Siddhi Yagya seeks success in these matters. Acharya Ji's team performs Ganesh puja, Rahu and Chandra shanti, the jaap of the Hanuman and Durga mantras for a safe journey, and a havan, with your name and the purpose (study, job, visa, settlement) in the sankalp.\n\n"
        .'Acharya Ji also tells you the supportive periods for applying, from your dasha and transits.',
    'process' => $steps(['Rahu and Chandra shanti.', 'Hanuman and Durga mantra jaap for a safe journey.', 'Prayer for the visa, study or job named in the sankalp.', 'Havan.']),
    'benefits' => ['Support for visa, study and work abroad', 'Activates the 9th and 12th houses', 'Prayer for safe travel', 'Timing for applications from your chart'],
    'faqs' => [$remote],
];

$c['dhan-lakshmi-ganesh-puja'] = [
    'body' => "Lord Ganesha removes obstacles and Maa Lakshmi brings wealth; worshipped together they bless a home or business with steady prosperity. The puja is done on Diwali, Dhanteras, Akshaya Tritiya, the opening of a shop or a new financial year.\n\n"
        ."Acharya Ji's team performs Ganesh–Lakshmi puja with lotus, kamal gatta, kheer and coins, the Shri Suktam and Kanakdhara Stotra, Kuber puja and a havan, with your name and business in the sankalp.",
    'process' => $steps(['Ganesh–Lakshmi puja with lotus, kamal gatta and kheer.', 'Shri Suktam and Kanakdhara Stotra.', 'Kuber puja.', 'Havan for prosperity.'], 'Diwali, Dhanteras, a Friday or the muhurat you need'),
    'benefits' => ['Prosperity for home and business', 'Removes obstacles to income', 'Ideal for Diwali and new beginnings', 'Blessings of Ganesha, Lakshmi and Kuber'],
    'faqs' => [$remote],
];

$c['maha-lakshmi-puja'] = [
    'body' => "Maha Lakshmi is the goddess of wealth, fortune and beauty. Her puja is done for financial stability, to remove poverty and debt, and for grace and harmony in the home.\n\n"
        ."Acharya Ji's team performs the Shodashopachar (16-step) puja of Maa Lakshmi with lotus, red flowers, kheer and shringar, recites the Shri Suktam and Lakshmi Ashtottara, chants the Maha Lakshmi mantra and completes the puja with a havan. Fridays, Purnima and Diwali are the best days.",
    'process' => $steps(['Shodashopachar puja of Maa Lakshmi.', 'Shri Suktam and Lakshmi Ashtottara.', 'Maha Lakshmi mantra jaap.', 'Havan with lotus seeds and ghee.'], 'a Friday, Purnima or Diwali'),
    'benefits' => ['Financial stability', 'Removes poverty and debt', 'Grace and harmony at home'],
    'faqs' => [$remote],
];

$c['dhan-lakshmi-navratri-anushthan'] = [
    'body' => "In Navratri the nine forms of Maa Durga are worshipped over nine nights. A Dhan Lakshmi Navratri Anushthan adds daily Lakshmi puja and the Shri Suktam to the Navratri worship, for prosperity and the blessings of the Divine Mother through the season.\n\n"
        ."Acharya Ji's team performs ghatasthapana on the first day, daily puja of that day's Durga form, daily Shri Suktam and Lakshmi mantra jaap in your name, kanya pujan on Ashtami or Navami, and a concluding havan.",
    'process' => $steps(['Ghatasthapana on day one.', 'Daily puja of the Navdurga form of the day.', 'Daily Shri Suktam and Lakshmi mantra jaap.', 'Kanya pujan on Ashtami or Navami and concluding havan.'], 'the Navratri ghatasthapana muhurat'),
    'benefits' => ['Nine days of worship in your name', 'Blessings of Durga and Lakshmi', 'Prosperity through the season'],
    'faqs' => [$remote],
];

$c['durga-ashtami-puja'] = [
    'body' => "Durga Ashtami, the eighth day of Navratri, honours Maa Mahagauri. It is one of the most powerful days of the year for the worship of the Divine Mother.\n\n"
        ."Acharya Ji's team performs Durga puja with shringar and red chunri, recites selected chapters of the Durga Saptashati, performs Sandhi puja (at the meeting of Ashtami and Navami) with 108 lotuses or diyas where possible, kanya pujan and havan.",
    'process' => $steps(['Durga puja with shringar.', 'Selected Durga Saptashati chapters.', 'Sandhi puja with diyas.', 'Kanya pujan and havan.'], 'Durga Ashtami'),
    'benefits' => ['Blessings of Maa Durga on her holiest day', 'Protection and strength', 'Peace in the home'],
    'faqs' => [$remote],
];

$c['dev-diwali-sarva-devta-puja'] = [
    'body' => "Dev Diwali is celebrated on Kartik Purnima, fifteen days after Diwali, when the gods are said to come down to bathe in the Ganga. Diyas are lit on the ghats and all deities are worshipped together.\n\n"
        ."Acharya Ji's team performs Sarva Devta puja — Ganesh, Vishnu, Shiva, Lakshmi, Durga and the Navgrah — with deep-daan (offering of lamps) in your name, the Kartik Mahatmya and a havan.",
    'process' => $steps(['Sarva Devta puja.', 'Deep-daan of lamps in your name.', 'Kartik Mahatmya recitation.', 'Havan.'], 'Kartik Purnima (Dev Diwali)'),
    'benefits' => ['Blessings of all deities together', 'Merit of deep-daan on Kartik Purnima', 'Peace and prosperity'],
    'faqs' => [$remote],
];

$c['maha-gayatri-jaap-and-tulsi-vivah'] = [
    'body' => "The Gayatri mantra is the mother of the Vedas, chanted for wisdom, purity and inner light. Tulsi Vivah, on Kartik Shukla Ekadashi or Dwadashi, is the marriage of Tulsi Devi with Lord Shaligram (Vishnu) and marks the start of the wedding season.\n\n"
        ."In this combined puja Acharya Ji's team performs Gayatri mantra jaap in your name and the full Tulsi Vivah ceremony with mangal phere, followed by a havan. It is done for wisdom, for an early and happy marriage and for blessings on the family.",
    'process' => $steps(['Gayatri mantra jaap in your name.', 'Tulsi and Shaligram puja.', 'Tulsi Vivah with mangal phere.', 'Havan.'], 'Dev Uthani Ekadashi'),
    'benefits' => ['Wisdom and purity from the Gayatri mantra', 'Blessing for marriage', 'Merit of Tulsi Vivah'],
    'faqs' => [$remote],
];

$c['shiv-gauri-puja-karwa-chauth'] = [
    'body' => "On Karwa Chauth married women fast from sunrise to moonrise for the long life and well-being of their husbands, and worship Lord Shiva, Maa Gauri, Lord Ganesha and Kartikeya.\n\n"
        ."Acharya Ji's team performs the Shiv–Gauri puja in your name with the karwa (earthen pot), shringar for Maa Gauri, the Karwa Chauth katha and a prayer for your husband's health and long life. The Moon-rise time for your city is sent to you.",
    'process' => $steps(['Shiv–Gauri puja with karwa and shringar.', 'Karwa Chauth katha.', 'Prayer for the husband\'s long life.'], 'Karwa Chauth'),
    'benefits' => ['Prayer for the husband\'s long life', 'Blessings of Shiva and Gauri for the marriage'],
    'faqs' => [$remote],
];

$c['three-day-surya-puja'] = [
    'body' => "Lord Surya is the giver of life, health and confidence. A three-day Surya puja strengthens the Sun in the chart — useful for confidence, government work, eyesight, heart health and relations with the father.\n\n"
        ."Over three days, Acharya Ji's team offers arghya at sunrise, recites the Aditya Hridaya Stotra and Surya Ashtakam, chants the Surya beej mantra and completes the puja with a havan of aak samidha on the third day. It is good during Chhath, on Sundays, Makar Sankranti and Ratha Saptami.",
    'process' => $steps(['Daily sunrise arghya for three days.', 'Aditya Hridaya Stotra and Surya Ashtakam.', 'Surya beej mantra jaap.', 'Havan with aak samidha on day three.'], 'three days starting on a Sunday'),
    'benefits' => ['Strengthens the Sun', 'Confidence and success in government work', 'Health and vitality'],
    'faqs' => [$remote],
];

/* Chadhava */

$ch = fn (string $temple, string $offering, string $why, string $day) => [
    'body' => "A chadhava is an offering made at the temple on your behalf. Our team offers $offering at $temple in your name and gotra, with a short prayer for your wish.\n\n$why\n\nThe offering is made on $day. A photo of the offering is added to your account and you are told by notification.",
    'process' => ['Choose the chadhava and pay online.', 'Add your name, gotra and wish.', "The team offers $offering at $temple on $day.", 'A photo of the offering is added to your account.'],
    'faqs' => [['Can I send the offering for someone else?', 'Yes. Give their name and gotra in the sankalp.']],
];
$c['shani-grah-chadhava'] = $ch('a Shani temple', 'mustard oil, black til, black urad and a blue flower', 'Shani chadhava is offered during Sade Sati, Dhaiya or a difficult Shani dasha, to calm Saturn and bring patience and relief.', 'a Saturday');
$c['hanuman-garhi-bajrangbali-chadhava'] = $ch('a Hanuman temple', 'sindoor, chameli oil, a red chola and boondi laddoo', 'Bajrangbali is worshipped for courage, protection and relief from Mangal and Shani troubles.', 'a Tuesday or Saturday');
$c['mahamrityunjaya-chadhava'] = $ch('a Shiva temple', 'milk, Ganga jal, bilva patra and dhatura with Mahamrityunjaya mantra', 'Offered for health, recovery and protection.', 'a Monday');
$c['rahu-grah-chadhava'] = $ch('a temple', 'coconut, blue cloth, black til and durva', 'Offered to calm Rahu during its dasha or in Kaal Sarp, for clarity and freedom from fear.', 'a Saturday');
$c['maa-baglamukhi-chadhava'] = $ch('a Baglamukhi temple', 'yellow chunri, haldi, chana dal and besan laddoo', 'Offered for victory over opponents, in court matters and competitions.', 'a Thursday');
$c['diwali-lakshmi-ganesh-chadhava'] = $ch('a Lakshmi temple', 'lotus, kamal gatta, kheer and coins', 'Offered on Diwali for prosperity in home and business.', 'Diwali');
$c['karwa-chauth-sindoor-shringar-chadhava'] = $ch('a Gauri temple', 'sindoor, bangles and a full shringar set', 'Offered on Karwa Chauth for the long life of the husband and a happy marriage.', 'Karwa Chauth');

return $c;
