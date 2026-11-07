<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Starting product list for the store: the nine Navratna stones, popular uparatna, rudraksha,
 * yantra, mala and pooja kits, with original descriptions and typical starting prices. Every item
 * is created HIDDEN with stock 0: a real stone has its own weight, origin, lab certificate and
 * photo, so the owner fills those in Admin › Gem store and switches it on sale. Existing slugs are
 * never touched.
 */
class StoreSeeder extends Seeder
{
    /** [category, name, planet / use, summary, starting price ₹, colour for the illustration] */
    public const ITEMS = [
        ['gemstone', 'Ruby (Manik)', 'Sun', 'For a well-placed Sun: confidence, leadership and recognition, worn only after a chart check.', 12000, '#c0263b'],
        ['gemstone', 'Pearl (Moti)', 'Moon', 'Natural pearl for the Moon: calm mind, emotional balance and good sleep, after chart analysis.', 3500, '#f4efe4'],
        ['gemstone', 'Red Coral (Moonga)', 'Mars', 'Italian or Japanese red coral for Mars: courage, energy and property matters.', 4500, '#d94a2b'],
        ['gemstone', 'Emerald (Panna)', 'Mercury', 'Colombian or Zambian emerald for Mercury: speech, learning, business and communication.', 9000, '#1f9d55'],
        ['gemstone', 'Yellow Sapphire (Pukhraj)', 'Jupiter', 'Ceylon yellow sapphire for Jupiter: wisdom, children, marriage and prosperity.', 15000, '#f2c230'],
        ['gemstone', 'Diamond (Heera)', 'Venus', 'Certified diamond for Venus: relationships, comfort, art and luxury.', 25000, '#d7ecff'],
        ['gemstone', 'Blue Sapphire (Neelam)', 'Saturn', 'Ceylon blue sapphire for Saturn. Strong in effect; tried only on Acharya Ji\'s advice.', 18000, '#1e4fb4'],
        ['gemstone', 'Hessonite (Gomed)', 'Rahu', 'Ceylon hessonite garnet for Rahu: clarity in confusion, career and foreign matters.', 3000, '#b5651d'],
        ['gemstone', "Cat's Eye (Lehsunia)", 'Ketu', "Chrysoberyl cat's eye for Ketu: intuition, spiritual growth and protection from sudden losses.", 6000, '#a3a35a'],
        ['gemstone', 'Opal', 'Venus (uparatna)', 'Australian opal, a gentler substitute for diamond for Venus.', 2500, '#9fd8d1'],
        ['gemstone', 'Citrine (Sunela)', 'Jupiter (uparatna)', 'Affordable substitute for yellow sapphire for Jupiter.', 1500, '#f0a830'],
        ['gemstone', 'Amethyst (Katela)', 'Saturn (uparatna)', 'Gentle substitute for blue sapphire for Saturn.', 1200, '#8e44ad'],
        ['gemstone', 'Peridot', 'Mercury (uparatna)', 'Light green stone, a substitute for emerald for Mercury.', 1200, '#9acd32'],
        ['gemstone', 'Moonstone', 'Moon (uparatna)', 'Soft substitute for pearl for the Moon.', 900, '#dfe6ef'],
        ['gemstone', 'Garnet (Tamra)', 'Sun (uparatna)', 'Deep red garnet, a substitute for ruby for the Sun.', 1100, '#8b1e2f'],
        ['rudraksha', 'One Mukhi Rudraksha', 'Sun', 'Rare one-faced rudraksha associated with Lord Shiva and focus.', 11000, '#8a4b25'],
        ['rudraksha', 'Five Mukhi Rudraksha', 'Jupiter', 'The most common rudraksha, for peace and daily jaap.', 501, '#8a4b25'],
        ['rudraksha', 'Seven Mukhi Rudraksha', 'Saturn', 'Associated with Goddess Lakshmi; worn for financial stability.', 1500, '#8a4b25'],
        ['rudraksha', 'Gauri Shankar Rudraksha', 'Moon', 'Two naturally joined beads, worn for harmony in relationships.', 3100, '#8a4b25'],
        ['yantra', 'Shri Yantra', 'Lakshmi', 'Energised copper Shri Yantra for the puja place, home or office.', 1100, '#d9a441'],
        ['yantra', 'Navgrah Yantra', 'Nine planets', 'Copper yantra of the nine planets for daily worship.', 951, '#d9a441'],
        ['yantra', 'Kuber Yantra', 'Kuber', 'Copper Kuber yantra for the cash box or shop.', 751, '#d9a441'],
        ['yantra', 'Vastu Dosh Nivaran Yantra', 'Vastu', 'Copper yantra used in traditional Vastu correction.', 1100, '#d9a441'],
        ['mala', 'Rudraksha Jaap Mala (108+1)', 'Jaap', 'Five mukhi rudraksha mala for mantra jaap.', 1100, '#8a4b25'],
        ['mala', 'Sphatik (Crystal) Mala', 'Moon / Venus', 'Clear quartz mala for Lakshmi and Shukra mantras.', 1100, '#e6f2ff'],
        ['mala', 'Tulsi Kanthi Mala', 'Vishnu', 'Tulsi bead mala for Vishnu and Krishna devotion.', 351, '#6b8e23'],
        ['kit', 'Navgrah Puja Samagri Kit', 'Puja', 'Complete samagri for a Navgrah puja at home, with vidhi booklet.', 1100, '#b4471b'],
        ['kit', 'Lakshmi Ganesh Puja Kit', 'Diwali', 'Samagri for Diwali Lakshmi Ganesh puja at home.', 1500, '#b4471b'],
        ['kit', 'Rudrabhishek Samagri Kit', 'Shiva', 'Samagri for a Rudrabhishek at home or a temple.', 1250, '#b4471b'],
        ['samagri', 'Pure Gangajal (500 ml)', 'Puja', 'Sealed Gangajal for daily puja and sankalp.', 151, '#3fa7d6'],
        ['samagri', 'Hawan Samagri (1 kg)', 'Hawan', 'Traditional mix of herbs and resins for hawan.', 351, '#8b5a2b'],
        // Added 5 Nov 2026 — full store at current Indian market rates
        ['rudraksha', 'Two Mukhi Rudraksha', 'Moon', 'Ardhnarishwar form; worn for harmony between husband and wife and emotional balance.', 551, '#8a4b25'],
        ['rudraksha', 'Three Mukhi Rudraksha', 'Mars', 'Agni form; worn for confidence and to let go of past guilt and lethargy.', 451, '#8a4b25'],
        ['rudraksha', 'Four Mukhi Rudraksha', 'Mercury', 'Brahma form; for students — memory, speech and concentration.', 351, '#8a4b25'],
        ['rudraksha', 'Six Mukhi Rudraksha', 'Venus', 'Kartikeya form; for willpower, charm and balanced desires.', 351, '#8a4b25'],
        ['rudraksha', 'Eight Mukhi Rudraksha', 'Rahu', 'Ganesha form; removes obstacles and calms Rahu.', 2100, '#8a4b25'],
        ['rudraksha', 'Nine Mukhi Rudraksha', 'Ketu', 'Durga form; for courage, energy and protection.', 2500, '#8a4b25'],
        ['rudraksha', 'Ten Mukhi Rudraksha', 'All planets', 'Vishnu form; protection from negative energy and Vastu dosh.', 2700, '#8a4b25'],
        ['rudraksha', 'Eleven Mukhi Rudraksha', 'Hanuman', 'Ekadash Rudra form; for courage, success and fearlessness.', 3100, '#8a4b25'],
        ['rudraksha', 'Twelve Mukhi Rudraksha', 'Sun', 'Surya form; for leadership, confidence and government work.', 3500, '#8a4b25'],
        ['rudraksha', 'Thirteen Mukhi Rudraksha', 'Venus', 'Kamdev form; for attraction, comfort and fulfilment of wishes.', 5100, '#8a4b25'],
        ['rudraksha', 'Fourteen Mukhi Rudraksha (Nepal)', 'Saturn', 'Deva Mani; rare bead for intuition and protection from Saturn\'s troubles.', 21000, '#8a4b25'],
        ['rudraksha', 'Ganesh Rudraksha', 'Ganesha', 'Bead with a natural trunk-like ridge; for success in new work.', 1100, '#8a4b25'],
        ['mala', 'Rudraksha Bracelet (5 Mukhi)', 'Daily wear', 'Five mukhi rudraksha bracelet on elastic, for daily wear.', 751, '#8a4b25'],
        ['yantra', 'Mahamrityunjay Yantra', 'Shiva', 'Copper Mahamrityunjay yantra for health and protection.', 1100, '#d9a441'],
        ['yantra', 'Baglamukhi Yantra', 'Maa Baglamukhi', 'Copper Baglamukhi yantra for victory in disputes and court cases.', 1100, '#d9a441'],
        ['yantra', 'Shani Yantra', 'Saturn', 'Copper Shani yantra for Sade Sati and Dhaiya.', 751, '#d9a441'],
        ['yantra', 'Mangal Yantra', 'Mars', 'Copper Mangal yantra for Mangal dosh, property and debt relief.', 751, '#d9a441'],
        ['yantra', 'Saraswati Yantra', 'Saraswati', 'Copper Saraswati yantra for students and learning.', 751, '#d9a441'],
        ['yantra', 'Hanuman Yantra', 'Hanuman', 'Copper Hanuman yantra for courage and protection.', 751, '#d9a441'],
        ['yantra', 'Durga Bisa Yantra', 'Durga', 'Copper Durga Bisa yantra for protection and prosperity.', 951, '#d9a441'],
        ['yantra', 'Ganesh Yantra', 'Ganesha', 'Copper Ganesh yantra for new beginnings and removal of obstacles.', 751, '#d9a441'],
        ['mala', 'Karungali (Ebony) Mala', 'Mars / Saturn', 'Genuine karungali wood mala, popular for Mangal and Shani mantras.', 1100, '#2b1d14'],
        ['mala', 'Red Chandan Mala', 'Mars / Devi', 'Red sandalwood mala for Devi and Hanuman mantras.', 501, '#a0322b'],
        ['mala', 'Haldi (Turmeric) Mala', 'Jupiter / Baglamukhi', 'Turmeric bead mala for Guru and Baglamukhi mantras.', 351, '#e1a91a'],
        ['mala', 'Kamal Gatta (Lotus Seed) Mala', 'Lakshmi', 'Lotus seed mala for Lakshmi mantras.', 351, '#6b4f2a'],
        ['mala', 'Vaijanti Mala', 'Vishnu / Krishna', 'Vaijanti seed mala dear to Lord Krishna.', 251, '#8f8f7a'],
        ['mala', 'Seven Chakra Bracelet', 'Balance', 'Natural stone bracelet with the seven chakra stones.', 799, '#7d4fb3'],
        ['mala', 'Tiger Eye Bracelet', 'Sun / Mars', 'Tiger eye bead bracelet for confidence and focus.', 699, '#b07a2a'],
        ['mala', 'Rose Quartz Bracelet', 'Venus', 'Rose quartz bracelet for love and calm.', 699, '#f2b7c4'],
        ['kit', 'Satyanarayan Katha Puja Kit', 'Vishnu', 'Complete samagri for Satyanarayan katha at home, with katha booklet.', 1100, '#b4471b'],
        ['kit', 'Griha Pravesh Puja Kit', 'New home', 'Complete samagri for Griha Pravesh and Vastu shanti.', 2100, '#b4471b'],
        ['kit', 'Navratri Durga Puja Kit', 'Durga', 'Ghatasthapana and nine-day Durga puja samagri with chunri and shringar.', 1500, '#b4471b'],
        ['kit', 'Mahamrityunjay Jaap Kit', 'Shiva', 'Samagri for Mahamrityunjay jaap and havan at home, with rudraksha mala.', 1800, '#b4471b'],
        ['kit', 'Copper Hawan Kund with Samagri', 'Hawan', 'Small copper hawan kund with stand, samidha and hawan samagri.', 1500, '#b06a3b'],
        ['samagri', 'Gomti Chakra (11 pieces)', 'Lakshmi', 'Natural gomti chakra for Lakshmi puja and the cash box.', 151, '#e8dcc4'],
        ['samagri', 'Pili Kauri (11 pieces)', 'Lakshmi', 'Yellow cowries used in Diwali and Lakshmi puja.', 101, '#f0d58a'],
        ['samagri', 'Pure Bhimseni Kapoor (100 g)', 'Aarti', 'Pure camphor for aarti and hawan.', 251, '#f4f4f4'],
        ['samagri', 'Navgrah Samidha Set', 'Hawan', 'Nine traditional woods for Navgrah hawan.', 301, '#8b5a2b'],
        ['samagri', 'Chandan Tika and Roli Set', 'Puja', 'Sandalwood paste, roli, akshat and kalawa for daily puja.', 151, '#d99a5b'],
        ['idol', 'Brass Ganesh Idol (4 inch)', 'Ganesha', 'Solid brass Ganesh idol for the puja room or office.', 1100, '#c8952e'],
        ['idol', 'Brass Lakshmi Ganesh Pair (4 inch)', 'Diwali', 'Solid brass Lakshmi and Ganesh idols for Diwali and daily puja.', 2100, '#c8952e'],
        ['idol', 'Narmadeshwar Shivling (3 inch)', 'Shiva', 'Natural Narmadeshwar shivling from the Narmada river.', 1500, '#7a6a5a'],
        ['idol', 'Parad Shivling (50 g)', 'Shiva', 'Mercury-alloy (parad) shivling for abhishek and prosperity.', 5100, '#b9c0c8'],
        ['idol', 'Brass Laddu Gopal (3 inch)', 'Krishna', 'Brass Bal Gopal idol for home seva.', 1100, '#c8952e'],
        ['idol', 'Brass Hanuman Idol (4 inch)', 'Hanuman', 'Solid brass Hanuman idol for courage and protection.', 1300, '#c8952e'],
        ['vastu', 'Vastu Pyramid Plate (9×9)', 'Vastu', '81-pyramid plate used under beds, seats and doors in Vastu correction.', 1500, '#c27a3a'],
        ['vastu', 'Copper Vastu Pyramid Set', 'Vastu', 'Set of copper pyramids for zone correction without construction.', 2100, '#b87333'],
        ['vastu', 'Crystal Pyramid', 'Vastu', 'Clear crystal pyramid for the north-east and study table.', 551, '#dfe9f2'],
        ['vastu', 'Copper Surya (Wall)', 'Sun / Vastu', 'Copper Sun for the east wall; traditional Vastu remedy for fame and energy.', 1100, '#d2862e'],
        ['vastu', 'Vastu Copper Strip Set', 'Vastu', 'Copper strips used to correct entrance and zone defects.', 951, '#b87333'],
    ];

    public function run(): void
    {
        foreach (self::ITEMS as $i => [$category, $name, $for, $summary, $price, $colour]) {
            $slug = Str::slug($name);
            if (DB::table('products')->where('slug', $slug)->exists()) {
                continue;
            }
            $img = 'images/art/store-'.$slug.'.jpg';
            DB::table('products')->insert([
                'ref' => (string) Str::ulid(), 'slug' => $slug, 'category' => $category, 'name' => $name, 'summary' => $summary,
                'description' => $summary."\n\nAssociated with: $for. "
                    .($category === 'gemstone'
                        ? 'Acharya Ji recommends a stone only after studying your chart, and tells you the weight, metal, finger, day and mantra for wearing it. Each stone comes with its lab certificate.'
                        : 'Sent with simple instructions for use and the related mantra.'),
                'price_minor' => $price * 100, 'stock' => 0, 'max_per_order' => $category === 'gemstone' ? 1 : 5, 'active' => false, 'sort' => $i,
                'image_path' => is_file(public_path($img)) ? $img : null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
