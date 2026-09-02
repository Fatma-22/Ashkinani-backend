<?php

namespace Database\Seeders;

use App\Models\Sponsor;
use App\Models\Discount;
use App\Models\Ad;
use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LandingPageSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        News::truncate();
        Ad::truncate();
        Discount::truncate();
        Sponsor::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // ═══════════════ Sponsors ═══════════════
        $sponsors = [
            ['name_en' => 'Nike', 'name_ar' => 'نايكي', 'logo_path' => 'https://upload.wikimedia.org/wikipedia/commons/a/a6/Logo_NIKE.svg'],
            ['name_en' => 'Adidas', 'name_ar' => 'أديداس', 'logo_path' => 'https://upload.wikimedia.org/wikipedia/commons/2/20/Adidas_Logo.svg'],
            ['name_en' => 'Puma', 'name_ar' => 'بوما', 'logo_path' => 'https://upload.wikimedia.org/wikipedia/ar/thumb/e/e0/Puma_logo.svg/1200px-Puma_logo.svg.png'],
            ['name_en' => 'Red Bull', 'name_ar' => 'ريد بل', 'logo_path' => 'https://upload.wikimedia.org/wikipedia/en/thumb/f/f5/Red_Bull_Racing_logo.svg/1200px-Red_Bull_Racing_logo.svg.png'],
            ['name_en' => 'Visa', 'name_ar' => 'فيزا', 'logo_path' => 'https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg'],
            ['name_en' => 'Pepsi', 'name_ar' => 'بيبسي', 'logo_path' => 'https://upload.wikimedia.org/wikipedia/commons/0/0f/Pepsi_logo_2014.svg'],
        ];

        foreach ($sponsors as $idx => $s) {
            Sponsor::create(array_merge($s, [
                'website_url' => 'https://google.com',
                'sort_order' => $idx,
                'is_active' => true
            ]));
        }

        // ═══════════════ Ads ═══════════════
        Ad::create([
            'title_en' => 'Summer Camp 2026',
            'title_ar' => 'معسكر الصيف 2026',
            'image_path' => 'https://images.unsplash.com/photo-1541534741688-6078c64ecb29?q=80&w=1200',
            'click_url' => 'https://ashkananitransfer.com',
            'type' => 'BANNER',
            'is_active' => true,
            'start_date' => now(),
            'end_date' => now()->addMonths(3)
        ]);

        Ad::create([
            'title_en' => 'Join our Elite Agency',
            'title_ar' => 'انضم إلى وكالتنا النخبة',
            'image_path' => 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?q=80&w=1200',
            'click_url' => 'https://ashkananitransfer.com/players',
            'type' => 'BANNER',
            'is_active' => true,
        ]);

        Ad::create([
            'title_en' => 'Exclusive Offer for Players',
            'title_ar' => 'عرض حصري للاعبين',
            'image_path' => 'https://images.unsplash.com/photo-1574629810360-7efbbe195018?q=80&w=1200',
            'click_url' => 'https://wa.me/96597131223',
            'type' => 'POPUP',
            'is_active' => true,
        ]);

        // ═══════════════ Discounts ═══════════════
        $discounts = [
            [
                'title_en' => '20% Off Sport Gear',
                'title_ar' => 'خصم 20% على الملابس الرياضية',
                'description_en' => 'Exclusive discount for Ashkanani players at Nike stores.',
                'description_ar' => 'خصم حصري للاعبي أشقناني في متاجر نايكي.',
                'code' => 'ASHK20',
                'image_path' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?q=80&w=400',
            ],
            [
                'title_en' => 'Free Medical Checkup',
                'title_ar' => 'فحص طبي مجاني',
                'description_en' => 'Get a full professional medical checkup once a year.',
                'description_ar' => 'احصل على فحص طبي احترافي شامل مرة واحدة في السنة.',
                'code' => 'HEALTHY',
                'image_path' => 'https://images.unsplash.com/photo-1505751172876-fa1923c5c528?q=80&w=400',
            ],
            [
                'title_en' => '15% Gym Membership',
                'title_ar' => 'خصم 15% على عضوية الجيم',
                'description_en' => 'Valid at all Gold Fitness branches.',
                'description_ar' => 'صالح في جميع فروع جولد فيتنس.',
                'code' => 'GYM15',
                'image_path' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=400',
            ],
        ];

        foreach ($discounts as $d) {
            Discount::create(array_merge($d, ['is_active' => true]));
        }

        // ═══════════════ News ═══════════════
        $newsItems = [
            [
                'title_en' => 'Ashkanani Agency Signs New Talent',
                'title_ar' => 'وكالة أشقناني توقع مع موهبة جديدة',
                'content_en' => "We are proud to announce the signing of two rising stars from the Kuwaiti league. Their performance last season was exceptional, and we believe they have a bright future in international football.\n\nOur team is working hard to ensure they get the best opportunities to grow and showcase their talent on the global stage.\n\nStay tuned for more updates on their progress and upcoming transfers.",
                'content_ar' => "نحن فخورون بالإعلان عن التوقيع مع نجمين صاعدين من الدوري الكويتي. كان أداؤهم في الموسم الماضي استثنائياً، ونؤمن أن لديهم مستقبلاً باهراً في كرة القدم الدولية.\n\nيعمل فريقنا بجد لضمان حصولهم على أفضل الفرص للنمو وإظهار مواهبهم على الساحة العالمية.\n\nتابعونا لمعرفة المزيد من التحديثات حول تقدمهم وانتقالاتهم القادمة.",
                'category_en' => 'Transfers',
                'category_ar' => 'انتقالات',
                'main_image_path' => 'https://images.unsplash.com/photo-1551958219-acbc608c6377?q=80&w=1200',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?q=80&w=800',
                    'https://images.unsplash.com/photo-1511886929837-354d827aae26?q=80&w=800'
                ]
            ],
            [
                'title_en' => 'Training Camp in Spain Starts Next Week',
                'title_ar' => 'بدء المعسكر التدريبي في إسبانيا الأسبوع القادم',
                'content_en' => "Our high-performance training camp in Madrid is set to begin next Monday. Over 20 players will participate in intensive sessions led by world-class coaches.\n\nThe camp focuses on tactical awareness, physical conditioning, and mental preparation for the upcoming season. This is part of our commitment to providing our players with the best environment to excel.",
                'content_ar' => "من المقرر أن يبدأ معسكرنا التدريبي عالي الأداء في مدريد يوم الاثنين المقبل. سيشارك أكثر من 20 لاعباً في جلسات مكثفة بقيادة مدربين عالميين.\n\nيركز المعسكر على الوعي التكتيكي، والتحسين البدني، والاستعداد الذهني للموسم القادم. هذا جزء من التزامنا بتزويد لاعبينا بأفضل بيئة للتفوق.",
                'category_en' => 'Events',
                'category_ar' => 'فعاليات',
                'main_image_path' => 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?q=80&w=1200',
                'gallery_images' => [
                    'https://images.unsplash.com/photo-1526232759583-26f186f07399?q=80&w=800'
                ]
            ],
            [
                'title_en' => 'Strategic Partnership with Global Scouts',
                'title_ar' => 'شراكة استراتيجية مع كشافين عالميين',
                'content_en' => "We have established a new partnership with a leading European scouting network. This collaboration will help our players get noticed by major clubs in Europe and Asia.\n\nThis move strengthens our position as the leading agency in the region for player development and international career management.",
                'content_ar' => "لقد أنشأنا شراكة جديدة مع شبكة كشافة أوروبية رائدة. سيساعد هذا التعاون لاعبينا على لفت انتباه الأندية الكبرى في أوروبا وآسيا.\n\nتعزز هذه الخطوة مكانتنا كوكالة رائدة في المنطقة لتطوير اللاعبين وإدارة المسيرة الدولية.",
                'category_en' => 'Agency',
                'category_ar' => 'الوكالة',
                'main_image_path' => 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?q=80&w=1200',
                'gallery_images' => []
            ],
            [
                'title_en' => 'Nutrition Workshops for Youth Players',
                'title_ar' => 'ورش عمل حول التغذية للاعبين الشباب',
                'content_en' => "Professional athletes need professional nutrition. Our upcoming workshop series will teach young players how to fuel their bodies for maximum performance and recovery.\n\nLed by certified sports nutritionists, these sessions are mandatory for all our academy prospects.",
                'content_ar' => "الرياضيون المحترفون يحتاجون إلى تغذية احترافية. ستعلم سلسلة ورش العمل القادمة لدينا اللاعبين الشباب كيفية تغذية أجسامهم لتحقيق أقصى أداء وتغطية.\n\nبقيادة خبراء تغذية رياضيين معتمدين، هذه الجلسات إلزامية لجميع مواهب أكاديميتنا.",
                'category_en' => 'Development',
                'category_ar' => 'تطوير',
                'main_image_path' => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?q=80&w=1200',
                'gallery_images' => []
            ],
            [
                'title_en' => 'New Recovery Center Opening',
                'title_ar' => 'افتتاح مركز الاستشفاء الجديد',
                'content_en' => "We are excited to open our state-of-the-art recovery center next month. Featuring cryotherapy, hydrotherapy, and advanced physiotherapy equipment.\n\nThis center will be available free of charge for all players signed with Ashkanani agency.",
                'content_ar' => "نحن متحمسون لافتتاح مركز الاستشفاء الحديث التابع لنا الشهر المقبل. يتميز بالعلاج بالتبريد، والعلاج المائي، وأحدث معدات العلاج الطبيعي.\n\nسيكون هذا المركز متاحاً مجاناً لجميع اللاعبين الموقعين مع وكالة أشقناني.",
                'category_en' => 'Facility',
                'category_ar' => 'مرافق',
                'main_image_path' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?q=80&w=1200',
                'gallery_images' => []
            ],
            [
                'title_en' => 'Top Performance: Player of the Month',
                'title_ar' => 'الأداء الأفضل: لاعب الشهر',
                'content_en' => "Congratulations to Ahmad Yusuf for being named the Player of the Month. His 5 goals in 4 matches have been crucial for his team's success.\n\nAhmad has shown incredible discipline and progress since joining our agency.",
                'content_ar' => "تهانينا لأحمد يوسف لاختياره لاعب الشهر. كانت أهدافه الخمسة في 4 مباريات حاسمة لنجاح فريقه.\n\nلقد أظهر أحمد انضباطاً وتقدماً مذهلاً منذ انضمامه إلى وكالتنا.",
                'category_en' => 'Awards',
                'category_ar' => 'جوائز',
                'main_image_path' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?q=80&w=1200',
                'gallery_images' => []
            ],
        ];

        foreach ($newsItems as $n) {
            News::create(array_merge($n, [
                'is_featured' => rand(0, 1) == 1,
                'is_active' => true,
                'published_at' => now()->subDays(rand(1, 30))
            ]));
        }
    }
}
