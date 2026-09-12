<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\StorePlayerRequest;
use App\Http\Requests\Player\UpdatePlayerRequest;
use App\Http\Resources\V1\PlayerResource;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ArabicSearchService;
use Illuminate\Support\Str;

class PlayerController extends Controller
{
    protected $arabicSearchService;

    public function __construct(ArabicSearchService $arabicSearchService)
    {
        $this->arabicSearchService = $arabicSearchService;
    }
    /**
     * Get all unique nationalities (English and Arabic).
     */
    public function nationalities(): JsonResponse
    {
        $query = Player::select('nationality', 'nationality_ar')->distinct();

        // Apply same filtering as index() to match what user can actually see

        // Basic Public Filtering
        if (!Auth::guard('sanctum')->check() || Auth::guard('sanctum')->user()->role === 'PUBLIC') {
            $query->where('is_visible', true)
                  ->where('is_approved', true);

            // Relaxed date filtering: players are visible if approved & visible, regardless of dates, 
            // unless they have a past end date.
            $now = now()->toDateString();
            $query->where(function ($q) use ($now) {
                $q->whereNull('contract_end_date')
                  ->orWhereDate('contract_end_date', '>=', $now)
                  ->orWhereIn('contract_status', ['ACTIVE', 'PENDING', 'NEGOTIATION']);
            });
        }

        // Agent Isolation: Agents should only see their assigned players
        if (Auth::guard('sanctum')->check() && Auth::guard('sanctum')->user()->role === 'AGENT') {
            $agent = Auth::guard('sanctum')->user()->agent;
            if ($agent) {
                $query->where('agent_id', $agent->id);
            } else {
                // Return empty if agent profile is missing
                $query->whereRaw('1 = 0');
            }
        }

        // Filter to only rows with actual nationality data (not both null/empty)
        // IMPORTANT: Group the OR condition properly
        $query->where(function ($q) {
            $q->where(function ($q1) {
                $q1->whereNotNull('nationality')
                    ->where('nationality', '!=', '');
            })->orWhere(function ($q2) {
                $q2->whereNotNull('nationality_ar')
                    ->where('nationality_ar', '!=', '');
            });
        });

        $dbPlayers = $query->get();

        // 🌍 Comprehensive Global Nationalities (UN Members + Major Regions)
        $baseNationalities = [
            // GCC & Middle East
            ['nationality' => 'Kuwaiti', 'nationality_ar' => 'كويتي'],
            ['nationality' => 'Saudi', 'nationality_ar' => 'سعودي'],
            ['nationality' => 'Emirati', 'nationality_ar' => 'إماراتي'],
            ['nationality' => 'Qatari', 'nationality_ar' => 'قطري'],
            ['nationality' => 'Bahraini', 'nationality_ar' => 'بحريني'],
            ['nationality' => 'Omani', 'nationality_ar' => 'عماني'],
            ['nationality' => 'Iraqi', 'nationality_ar' => 'عراقي'],
            ['nationality' => 'Jordanian', 'nationality_ar' => 'أردني'],
            ['nationality' => 'Lebanese', 'nationality_ar' => 'لبناني'],
            ['nationality' => 'Palestinian', 'nationality_ar' => 'فلسطيني'],
            ['nationality' => 'Syrian', 'nationality_ar' => 'سوري'],
            ['nationality' => 'Yemeni', 'nationality_ar' => 'يمني'],
            ['nationality' => 'Egyptian', 'nationality_ar' => 'مصري'],
            ['nationality' => 'Libyan', 'nationality_ar' => 'ليبي'],
            ['nationality' => 'Tunisian', 'nationality_ar' => 'تونسي'],
            ['nationality' => 'Algerian', 'nationality_ar' => 'جزائري'],
            ['nationality' => 'Moroccan', 'nationality_ar' => 'مغربي'],
            ['nationality' => 'Sudanese', 'nationality_ar' => 'سوداني'],
            ['nationality' => 'Somali', 'nationality_ar' => 'صومالي'],
            ['nationality' => 'Mauritanian', 'nationality_ar' => 'موريتاني'],
            ['nationality' => 'Djiboutian', 'nationality_ar' => 'جيبوتي'],
            ['nationality' => 'Comorian', 'nationality_ar' => 'قمري'],

            // Europe
            ['nationality' => 'English', 'nationality_ar' => 'إنجليزي'],
            ['nationality' => 'French', 'nationality_ar' => 'فرنسي'],
            ['nationality' => 'German', 'nationality_ar' => 'ألماني'],
            ['nationality' => 'Spanish', 'nationality_ar' => 'إسباني'],
            ['nationality' => 'Italian', 'nationality_ar' => 'إيطالي'],
            ['nationality' => 'Portuguese', 'nationality_ar' => 'برتغالي'],
            ['nationality' => 'Dutch', 'nationality_ar' => 'هولندي'],
            ['nationality' => 'Belgian', 'nationality_ar' => 'بلجيكي'],
            ['nationality' => 'Swiss', 'nationality_ar' => 'سويسري'],
            ['nationality' => 'Austrian', 'nationality_ar' => 'نمساوي'],
            ['nationality' => 'Swedish', 'nationality_ar' => 'سويدي'],
            ['nationality' => 'Norwegian', 'nationality_ar' => 'نرويجي'],
            ['nationality' => 'Danish', 'nationality_ar' => 'دنماركي'],
            ['nationality' => 'Finnish', 'nationality_ar' => 'فنلندي'],
            ['nationality' => 'Irish', 'nationality_ar' => 'أيرلندي'],
            ['nationality' => 'Scottish', 'nationality_ar' => 'اسكتلندي'],
            ['nationality' => 'Welsh', 'nationality_ar' => 'ويلزي'],
            ['nationality' => 'Greek', 'nationality_ar' => 'يوناني'],
            ['nationality' => 'Turkish', 'nationality_ar' => 'تركي'],
            ['nationality' => 'Russian', 'nationality_ar' => 'روسي'],
            ['nationality' => 'Ukrainian', 'nationality_ar' => 'أوكراني'],
            ['nationality' => 'Polish', 'nationality_ar' => 'بولندي'],
            ['nationality' => 'Croatian', 'nationality_ar' => 'كرواتي'],
            ['nationality' => 'Serbian', 'nationality_ar' => 'صربي'],
            ['nationality' => 'Czech', 'nationality_ar' => 'تشيكي'],
            ['nationality' => 'Hungarian', 'nationality_ar' => 'مجري'],
            ['nationality' => 'Romanian', 'nationality_ar' => 'روماني'],
            ['nationality' => 'Bulgarian', 'nationality_ar' => 'بلغاري'],
            ['nationality' => 'Slovak', 'nationality_ar' => 'سلوفاكي'],
            ['nationality' => 'Slovenian', 'nationality_ar' => 'سلوفيني'],
            ['nationality' => 'Albanian', 'nationality_ar' => 'ألباني'],
            ['nationality' => 'Macedonian', 'nationality_ar' => 'مقدوني'],
            ['nationality' => 'Bosnian', 'nationality_ar' => 'بوسني'],
            ['nationality' => 'Icelandic', 'nationality_ar' => 'أيسلندي'],
            ['nationality' => 'Estonian', 'nationality_ar' => 'إستوني'],
            ['nationality' => 'Latvian', 'nationality_ar' => 'لاتفي'],
            ['nationality' => 'Lithuanian', 'nationality_ar' => 'ليتواني'],
            ['nationality' => 'Maltese', 'nationality_ar' => 'مالطي'],
            ['nationality' => 'Cypriot', 'nationality_ar' => 'قبرصي'],
            ['nationality' => 'Luxembourgish', 'nationality_ar' => 'لوكسمبورغي'],

            // Americas
            ['nationality' => 'American', 'nationality_ar' => 'أمريكي'],
            ['nationality' => 'Canadian', 'nationality_ar' => 'كندي'],
            ['nationality' => 'Mexican', 'nationality_ar' => 'مكسيكي'],
            ['nationality' => 'Brazilian', 'nationality_ar' => 'برازيلي'],
            ['nationality' => 'Argentinian', 'nationality_ar' => 'أرجنتيني'],
            ['nationality' => 'Chilean', 'nationality_ar' => 'تشيلي'],
            ['nationality' => 'Colombian', 'nationality_ar' => 'كولومبي'],
            ['nationality' => 'Uruguayan', 'nationality_ar' => 'أوروغوياني'],
            ['nationality' => 'Paraguayan', 'nationality_ar' => 'باراغوياني'],
            ['nationality' => 'Peruvian', 'nationality_ar' => 'بيروفي'],
            ['nationality' => 'Venezuelan', 'nationality_ar' => 'فنزويلي'],
            ['nationality' => 'Ecuadorian', 'nationality_ar' => 'إكوادوري'],
            ['nationality' => 'Bolivian', 'nationality_ar' => 'بوليفي'],
            ['nationality' => 'Costa Rican', 'nationality_ar' => 'كوستاريكي'],
            ['nationality' => 'Panamanian', 'nationality_ar' => 'بنـمي'],
            ['nationality' => 'Honduran', 'nationality_ar' => 'هندوراسي'],
            ['nationality' => 'Guatemalan', 'nationality_ar' => 'غواتيمالي'],
            ['nationality' => 'Jamaican', 'nationality_ar' => 'جامايكي'],
            ['nationality' => 'Cuban', 'nationality_ar' => 'كوبي'],
            ['nationality' => 'Haitian', 'nationality_ar' => 'هايتي'],
            ['nationality' => 'Dominican', 'nationality_ar' => 'دومينيكاني'],
            ['nationality' => 'Trinidadian', 'nationality_ar' => 'ترينيدادي'],

            // Asia
            ['nationality' => 'Chinese', 'nationality_ar' => 'صيني'],
            ['nationality' => 'Japanese', 'nationality_ar' => 'ياباني'],
            ['nationality' => 'Korean', 'nationality_ar' => 'كوري'],
            ['nationality' => 'South Korean', 'nationality_ar' => 'كوري جنوبي'],
            ['nationality' => 'North Korean', 'nationality_ar' => 'كوري شمالي'],
            ['nationality' => 'Indian', 'nationality_ar' => 'هندي'],
            ['nationality' => 'Pakistani', 'nationality_ar' => 'باكستاني'],
            ['nationality' => 'Bangladeshi', 'nationality_ar' => 'بنغلاديشي'],
            ['nationality' => 'Iranian', 'nationality_ar' => 'إيراني'],
            ['nationality' => 'Afghan', 'nationality_ar' => 'أفغاني'],
            ['nationality' => 'Thai', 'nationality_ar' => 'تايلاندي'],
            ['nationality' => 'Vietnamese', 'nationality_ar' => 'فيتنامي'],
            ['nationality' => 'Indonesian', 'nationality_ar' => 'إندونيسي'],
            ['nationality' => 'Malaysian', 'nationality_ar' => 'ماليزي'],
            ['nationality' => 'Filipino', 'nationality_ar' => 'فلبيني'],
            ['nationality' => 'Singaporean', 'nationality_ar' => 'سنغافوري'],
            ['nationality' => 'Kazakh', 'nationality_ar' => 'كازاخستاني'],
            ['nationality' => 'Uzbek', 'nationality_ar' => 'أوزبكي'],
            ['nationality' => 'Turkmen', 'nationality_ar' => 'تركماني'],
            ['nationality' => 'Kyrgyz', 'nationality_ar' => 'قرغيزي'],
            ['nationality' => 'Tajik', 'nationality_ar' => 'طاجيكي'],
            ['nationality' => 'Sri Lankan', 'nationality_ar' => 'سريلانكي'],
            ['nationality' => 'Nepalese', 'nationality_ar' => 'نيبالي'],
            ['nationality' => 'Burmese', 'nationality_ar' => 'بورمي'],

            // Africa
            ['nationality' => 'Nigerian', 'nationality_ar' => 'نيجيري'],
            ['nationality' => 'Senegalese', 'nationality_ar' => 'سنغالي'],
            ['nationality' => 'Cameroonian', 'nationality_ar' => 'كاميروني'],
            ['nationality' => 'Ghanaian', 'nationality_ar' => 'غاني'],
            ['nationality' => 'Ivorian', 'nationality_ar' => 'إيفواري'],
            ['nationality' => 'South African', 'nationality_ar' => 'جنوب أفريقي'],
            ['nationality' => 'Kenyan', 'nationality_ar' => 'كيني'],
            ['nationality' => 'Ethiopian', 'nationality_ar' => 'إثيوبي'],
            ['nationality' => 'Eritrean', 'nationality_ar' => 'إريتري'],
            ['nationality' => 'Tanzanian', 'nationality_ar' => 'تنزاني'],
            ['nationality' => 'Ugandan', 'nationality_ar' => 'أوغندي'],
            ['nationality' => 'Zambian', 'nationality_ar' => 'زامبي'],
            ['nationality' => 'Zimbabwean', 'nationality_ar' => 'زيمبابوي'],
            ['nationality' => 'Angolan', 'nationality_ar' => 'أنغولي'],
            ['nationality' => 'Malian', 'nationality_ar' => 'مالي'],
            ['nationality' => 'Guinean', 'nationality_ar' => 'غيني'],
            ['nationality' => 'Burkinabe', 'nationality_ar' => 'بوركيني'],
            ['nationality' => 'Togolese', 'nationality_ar' => 'توغولي'],
            ['nationality' => 'Beninese', 'nationality_ar' => 'بنيني'],
            ['nationality' => 'Gabonese', 'nationality_ar' => 'غابوني'],
            ['nationality' => 'Congolese', 'nationality_ar' => 'كونغولي'],
            ['nationality' => 'Rwandan', 'nationality_ar' => 'رواندي'],
            ['nationality' => 'Liberian', 'nationality_ar' => 'ليبيري'],
            ['nationality' => 'Sierra Leonean', 'nationality_ar' => 'سيراليوني'],
            ['nationality' => 'Gambian', 'nationality_ar' => 'غامبي'],

            // Oceania
            ['nationality' => 'Australian', 'nationality_ar' => 'أسترالي'],
            ['nationality' => 'New Zealander', 'nationality_ar' => 'نيوزيلندي'],
            ['nationality' => 'Fijian', 'nationality_ar' => 'فيجي'],
            ['nationality' => 'Papua New Guinean', 'nationality_ar' => 'غيني جديد'],
            ['nationality' => 'Solomon Islander', 'nationality_ar' => 'سليماني'],
            ['nationality' => 'Vanuatuan', 'nationality_ar' => 'فانواتي'],
            ['nationality' => 'Samoan', 'nationality_ar' => 'ساموي'],
            ['nationality' => 'Tongan', 'nationality_ar' => 'تونغي'],

            // Missing Africa
            ['nationality' => 'Malawian', 'nationality_ar' => 'ملاوي'],
            ['nationality' => 'Mozambican', 'nationality_ar' => 'موزمبيقي'],
            ['nationality' => 'Madagascan', 'nationality_ar' => 'مدغشكري'],
            ['nationality' => 'Mauritian', 'nationality_ar' => 'موريتاني'],
            ['nationality' => 'Seychellois', 'nationality_ar' => 'سيشيلي'],
            ['nationality' => 'Namibian', 'nationality_ar' => 'ناميبي'],
            ['nationality' => 'Botswanan', 'nationality_ar' => 'بوتسواني'],
            ['nationality' => 'Lesotho', 'nationality_ar' => 'ليسوتو'],
            ['nationality' => 'Eswatini', 'nationality_ar' => 'إسواتيني'],
            ['nationality' => 'Central African', 'nationality_ar' => 'وسط أفريقي'],
            ['nationality' => 'Equatoguinean', 'nationality_ar' => 'غيني استوائي'],
            ['nationality' => 'Chadian', 'nationality_ar' => 'تشادي'],
            ['nationality' => 'Nigerien', 'nationality_ar' => 'نيجري'],

            // Missing Asia & Others
            ['nationality' => 'Mongolian', 'nationality_ar' => 'منغولي'],
            ['nationality' => 'Cambodian', 'nationality_ar' => 'كمبودي'],
            ['nationality' => 'Lao', 'nationality_ar' => 'لاوسي'],
            ['nationality' => 'Bruneian', 'nationality_ar' => 'بروني'],
            ['nationality' => 'Timorese', 'nationality_ar' => 'تيموري'],
            ['nationality' => 'Maldivian', 'nationality_ar' => 'مالديفي'],
            ['nationality' => 'Bhutanese', 'nationality_ar' => 'بوتاني'],
            
            // Missing Americas
            ['nationality' => 'Bahamian', 'nationality_ar' => 'باهامي'],
            ['nationality' => 'Belizean', 'nationality_ar' => 'بليزي'],
            ['nationality' => 'Salvadoran', 'nationality_ar' => 'سلفادوري'],
            ['nationality' => 'Nicaraguan', 'nationality_ar' => 'نيكاراغوي'],
            ['nationality' => 'Guyanese', 'nationality_ar' => 'غوياني'],
            ['nationality' => 'Surinamese', 'nationality_ar' => 'سورينامي'],
            ['nationality' => 'Barbadian', 'nationality_ar' => 'باربادوسي'],
            ['nationality' => 'Saint Lucian', 'nationality_ar' => 'سانت لوسي'],

            // Missing Europe
            ['nationality' => 'San Marinese', 'nationality_ar' => 'سان مارينو'],
            ['nationality' => 'Monegasque', 'nationality_ar' => 'موناكو'],
            ['nationality' => 'Andorran', 'nationality_ar' => 'أندوري'],
            ['nationality' => 'Liechtensteiner', 'nationality_ar' => 'ليختنشتايني'],
            
            // Special
            ['nationality' => 'Bedoon', 'nationality_ar' => 'بدون'],
            ['nationality' => 'No Nationality', 'nationality_ar' => 'بدون جنسية'],
        ];

        // 1. Load from comprehensive hardcoded list
        $allSeeds = collect($baseNationalities)->map(fn($n) => (object) $n);

        // 2. Load from Database 'nationalities' table
        $dbNationalities = DB::table('nationalities')->get()->map(fn($n) => (object)[
            'nationality' => $n->name_en,
            'nationality_ar' => $n->name_ar
        ]);

        // 3. Load unique from Players table
        $playerNationalities = Player::select('nationality', 'nationality_ar')
            ->distinct()
            ->whereNotNull('nationality')
            ->get()
            ->map(fn($n) => (object) [
                'nationality' => $n->nationality,
                'nationality_ar' => $n->nationality_ar
            ]);

        // Merge all sources
        $combined = $allSeeds->concat($dbNationalities)->concat($playerNationalities);

        $results = [];
        $seen = [];

        foreach ($combined as $nat) {
            $en = trim($nat->nationality ?? '');
            $ar = trim($nat->nationality_ar ?? '');

            // ⛔ STRICT EXCLUSION: Israel
            if (stripos($en, 'Israel') !== false || str_contains($ar, 'إسرائيل')) {
                continue;
            }

            if (empty($en) && empty($ar)) continue;

            $key = $en . '|' . $ar;
            if (in_array($key, $seen)) continue;
            
            $seen[] = $key;
            $results[] = [
                'nationality' => $en ?: null,
                'nationality_ar' => $ar ?: null,
            ];
        }

        // Sort alphabetically by Arabic name
        usort($results, function($a, $b) {
            return strcmp($a['nationality_ar'] ?? '', $b['nationality_ar'] ?? '');
        });

        return response()->json($results);
    }

    public function sports(): JsonResponse
    {
        // Category 1: Team Sports
        $teamSports = [
            'Football', 'Basketball', 'Volleyball', 'Handball', 'Futsal', 
            'Water Polo', 'Cricket', 'Rugby Union', 'Rugby League', 
            'American Football', 'Baseball', 'Softball', 'Ice Hockey', 
            'Field Hockey', 'Lacrosse', 'Paintball', 'Beach Soccer', 'Beach Volleyball'
        ];

        // Category 2: Individual Sports
        $individualSports = [
            'Tennis', 'Padel', 'Squash', 'Table Tennis', 'Badminton',
            'MMA', 'Boxing', 'Kickboxing', 'Muay Thai', 'Judo', 'Karate', 'Taekwondo', 'Wrestling', 'Jiu Jitsu', 'Sambo', 'Fencing',
            'Swimming', 'Diving', 'Rowing', 'Sailing', 'Surfing', 'Motosurf', 'Jet Ski',
            'Formula 1', 'Rally', 'Motocross', 'Karting',
            'Athletics', 'Pole Vault', 'Gymnastics', 'Triathlon', 'Cycling', 'Weightlifting',
            'Shooting', 'Archery', 'Bowling', 'Darts', 'Golf', 'Billiards', 'Snooker',
            'MOBA', 'FPS', 'Esports Strategy', 'Esports Sports',
            'Equestrian', 'Chess', 'Camel Racing', 'Falconry', 'Horse Racing'
        ];

        $allSports = array_merge($teamSports, $individualSports);
        return response()->json($allSports);
    }

    public function positions(): JsonResponse
    {
        $basePositions = [
            // Football
            'GK', 'CB', 'LCB', 'RCB', 'RB', 'LB', 'RWB', 'LWB', 
            'CDM', 'LDM', 'RDM', 'CM', 'LCM', 'RCM', 'CAM', 'LCAM', 'RCAM', 
            'RM', 'LM', 'RW', 'LW', 'CF', 'ST', 'SS', 'LS', 'RS',
            // Basketball
            'PG', 'SG', 'SF', 'PF', 'C',
            // Volleyball
            'S', 'OH', 'OPP', 'MB', 'L',
            // Handball
            'LW', 'LB', 'CB', 'RB', 'RW', 'P',
            // Futsal/Beach Soccer
            'FIXO', 'ALA', 'PIVOT',
            // Water Polo
            'WINGS', 'FLATS',
            // Cricket
            'BATSMAN', 'BOWLER', 'WK', 'ALL_ROUNDER',
            // Rugby
            'PROP', 'HOOKER', 'LOCK', 'FLANKER', 'SH', 'FH', 'CENTER', 'WING', 'FB', 'SECOND_ROW', 'SO',
            // American Football
            'QB', 'AM_RB', 'WR', 'TE', 'OL', 'DL', 'AM_LB', 'DB', 'K',
            // Baseball / Softball
            'PITCHER', 'CATCHER', '1B', '2B', 'BASE_SS', '3B', 'OF',
            // Hockey / Lacrosse
            'DEFENSE', 'WINGER', 'ATTACK', 'MIDFIELDER', 'FORWARD',
            // Paintball
            'FRONT', 'MID', 'BACK', 'SNAKE', 'DORITO',
            // Racket
            'SINGLE', 'DOUBLE', 'NET', 'RIGHT', 'LEFT',
            // Combat
            'STRIKER', 'GRAPPLER', 'KATA', 'KUMITE', 'GI', 'NO_GI', 'FOIL', 'EPEE', 'SABRE', 'FIGHTER',
            // Water / Racing
            'FREE', 'BREAST', 'FLY', 'BACK', 'MEDLEY', 'SPRINGBOARD', 'PLATFORM', 'STROKE', 'BOW', 'COX', 'HELM', 'TRIM', 'SHORTBOARD', 'LONGBOARD', 'RACER', 'FREESTYLE', 'DRIVER', 'CO_DRIVER',
            // Athletics / Gymnastics
            'SPRINT', 'MIDDLE', 'LONG', 'HURDLE', 'JUMP', 'THROW', 'DECATHLON', 'MARATHON', 'VAULT', 'POMMEL', 'RINGS', 'BARS', 'BEAM', 'FLOOR', 'SNATCH', 'CLEAN_JERK',
            // Target / Esports / Traditional
            'PISTOL', 'RIFLE', 'TRAP', 'RECURVE', 'COMPOUND', 'PLAYER', 'TANK', 'JUNGLE', 'AWP', 'ENTRY', 'IGL', 'LURKER', 'JOCKEY', 'FALCONER', 'ATHLETE'
        ];

        $mapping = [
            'حارس مرمى' => 'GK', 'قلب دفاع' => 'CB', 'مدافع' => 'CB', 'دفاع' => 'CB', 'Defender' => 'CB',
            'ظهير ايسر' => 'LB', 'ظهير أيسر' => 'LB', 'ظهير' => 'RB', 'وسط مدافع' => 'CDM',
            'خط وسط دفاع' => 'CDM', 'DM' => 'CDM', 'خط وسط' => 'CM', 'لاعب وسط' => 'CM',
            'صانع ألعاب' => 'CAM', 'خط وسط مهاجم' => 'CAM', 'ZM' => 'CM',
            'Striker' => 'ST', 'مهاجم' => 'ST', 'رأس حربة' => 'ST',
            'Left winger' => 'LW', 'Left Wing' => 'LW', 'جناح' => 'RW', 'جناح أيمن' => 'RW',
            'عشاري' => 'DECATHLON', 'جري' => 'SPRINT', 'سباحة' => 'FREE'
        ];

        $allPlayersPositions = Player::select('positions')
            ->whereNotNull('positions')
            ->get()
            ->pluck('positions')
            ->flatten()
            ->merge($basePositions)
            ->map(function($p) use ($mapping) {
                $p = trim((string)$p);
                $upper = strtoupper($p);
                return $mapping[$p] ?? $mapping[$upper] ?? $upper;
            })
            ->unique()
            ->values();

        return response()->json($allPlayersPositions);
    }

    /**
     * Get all unique deal statuses from the database.
     */
    public function dealStatuses(): JsonResponse
    {
        $rawStatuses = Player::select('deal_status')
            ->whereNotNull('deal_status')
            ->where('deal_status', '!=', '')
            ->distinct()
            ->pluck('deal_status');

        $harmonized = $rawStatuses->map(function ($s) {
            $s = trim($s);
            $map = [
                'لاعب حر' => 'FREE_AGENT',
                'وكيل حر' => 'FREE_AGENT',
                'نظام الإعارة' => 'LOAN',
                'إعارة' => 'LOAN',
                'الشراء التعاقدي' => 'PURCHASE',
                'شراء' => 'PURCHASE',
                'لاعب هاوي' => 'AMATEUR',
                'هاوي' => 'AMATEUR',
                'تم التوقيع' => 'PURCHASE', // Mapping legacy to rationalized
            ];
            return $map[$s] ?? $s;
        })->unique()->filter()->values();

        return response()->json($harmonized);
    }

    /**
     * Display archived players (expired contracts) - Public access.
     */
    public function archived(Request $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();
        
        // Block all unauthenticated access to the directory
        if (!$user) {
            return $this->error('Unauthorized access. Profile sharing links are required for guests.', 403);
        }

        $query = Player::with(['mainPhoto', 'agent']);

        // Only visible and approved players
        $query->where('is_visible', true)
              ->where('is_approved', true);

        // Only players with EXPIRED contracts:
        // 1. contract_nature = TERMINATION (regardless of end date)
        // 2. OR end date is set and in the past
        // AND they have no active Ashkanani agency contract
        $now = now()->toDateString();
        $query->where(function ($q) use ($now) {
            $q->where('contract_nature', 'TERMINATION')
              ->orWhere(function ($sq) use ($now) {
                  $sq->whereNotNull('contract_end_date')
                     ->whereDate('contract_end_date', '<', $now);
              });
        })
        ->whereDoesntHave('documents', function ($c) use ($now) {
            $c->where('type', 'contract')
              ->whereNotNull('end_date')
              ->whereDate('end_date', '>=', $now);
        });

        // Search by name
        if ($request->filled('search')) {
            $search = trim($request->search);
            $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            $firstWord = array_shift($words);

            $query->where(function ($q) use ($firstWord, $words, $search) {
                // English Search
                $q->where(function ($sq) use ($firstWord, $words) {
                    $sq->where('name', 'like', $firstWord . '%');
                    foreach ($words as $word) {
                        $sq->where('name', 'like', '%' . $word . '%');
                    }
                });

                // Arabic Search (OR)
                $q->orWhere(function ($sq) use ($firstWord, $words) {
                    $sq->where('name_ar', 'REGEXP', '^' . $this->arabicSearchService->generateRegexPattern($firstWord));
                    foreach ($words as $word) {
                        $sq->where('name_ar', 'REGEXP', $this->arabicSearchService->generateRegexPattern($word));
                    }
                });

                // Nationality Search (Starts With)
                $q->orWhere('nationality', 'like', "{$search}%")
                  ->orWhere('nationality_ar', 'REGEXP', '^' . $this->arabicSearchService->generateRegexPattern($search));
            });
        }

        // Filter by sport
        if ($request->has('sport')) {
            $sport = $request->sport;
            $teamSports = [
                'Football', 'Basketball', 'Volleyball', 'Handball', 'Futsal', 
                'Water Polo', 'Cricket', 'Rugby Union', 'Rugby League', 
                'American Football', 'Baseball', 'Softball', 'Ice Hockey', 
                'Field Hockey', 'Lacrosse', 'Paintball', 'Beach Soccer', 'Beach Volleyball'
            ];
            $individualSports = [
                'Tennis', 'Padel', 'Squash', 'Table Tennis', 'Badminton',
                'MMA', 'Boxing', 'Kickboxing', 'Muay Thai', 'Judo', 'Karate', 'Taekwondo', 'Wrestling', 'Jiu Jitsu', 'Sambo', 'Fencing',
                'Swimming', 'Diving', 'Rowing', 'Sailing', 'Surfing', 'Motosurf', 'Jet Ski',
                'Formula 1', 'Rally', 'Motocross', 'Karting',
                'Athletics', 'Pole Vault', 'Gymnastics', 'Triathlon', 'Cycling', 'Weightlifting',
                'Shooting', 'Archery', 'Bowling', 'Darts', 'Golf', 'Billiards', 'Snooker',
                'MOBA', 'FPS', 'Esports Strategy', 'Esports Sports',
                'Equestrian', 'Chess', 'Camel Racing', 'Falconry', 'Horse Racing'
            ];

            $isTeam = $sport === 'TEAM_CATEGORY' || (is_array($sport) && in_array('TEAM_CATEGORY', $sport));
            $isIndividual = $sport === 'INDIVIDUAL_CATEGORY' || (is_array($sport) && in_array('INDIVIDUAL_CATEGORY', $sport));

            if ($isTeam) {
                $query->whereIn('sport', $teamSports);
            } else if ($isIndividual) {
                $query->whereIn('sport', $individualSports);
            } else if (is_array($sport)) {
                $query->whereIn('sport', $sport);
            } else if ($sport !== 'All') {
                $query->where('sport', $sport);
            }
        }

        // Filter by positions
        if ($request->has('position') || $request->has('positions')) {
            $positions = $request->input('positions', $request->input('position'));
            if (!is_array($positions)) {
                $positions = [$positions];
            }
            if (!in_array('All', $positions)) {
                $query->where(function($q) use ($positions) {
                    foreach($positions as $pos) {
                        $q->orWhereJsonContains('positions', $pos);
                    }
                });
            }
        }

        // Nationality filter
        if ($request->has('nationality')) {
            $nationality = $request->input('nationality');
            $nationalityArray = is_array($nationality) ? $nationality : [$nationality];
            $query->where(function ($q) use ($nationalityArray) {
                $q->whereIn('nationality', $nationalityArray)
                    ->orWhereIn('nationality_ar', $nationalityArray);
            });
        }
        if ($request->has('is_local')) {
            $isLocal = filter_var($request->is_local, FILTER_VALIDATE_BOOLEAN);
            $localNatsEn = ['Kuwaiti', 'Saudi', 'Bahraini', 'No Nationality', 'Bidoon'];
            $localNatsAr = ['كويتي', 'سعودي', 'بحريني', 'بدون', 'غير محدد الجنسية'];

            if ($isLocal) {
                $query->where(function ($q) use ($localNatsEn, $localNatsAr) {
                    $q->whereIn('nationality', $localNatsEn)
                        ->orWhereIn('nationality_ar', $localNatsAr)
                        ->orWhere('born_in_kuwait', true);
                });
            } else {
                $query->where(function ($q) use ($localNatsEn, $localNatsAr) {
                    $q->where(function ($sq) use ($localNatsEn) {
                        $sq->whereNotIn('nationality', $localNatsEn)
                            ->orWhereNull('nationality');
                    })->where(function ($sq) use ($localNatsAr) {
                        $sq->whereNotIn('nationality_ar', $localNatsAr)
                            ->orWhereNull('nationality_ar');
                    })->where('born_in_kuwait', '!=', true);
                });
            }
        }

        // Contract Nature
        if ($request->has('contract_nature')) {
            $nature = $request->contract_nature;
            if (is_array($nature)) {
                $query->whereIn('contract_nature', $nature);
            } else {
                $query->where('contract_nature', $nature);
            }
        }

        $query->orderBy('contract_start_date', 'desc')
              ->orderBy('id', 'desc');

        $players = $query->paginate($request->get('per_page', 8));

        return $this->success(PlayerResource::collection($players)->response()->getData(true));
    }

    /**
     * Display a listing of players with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        if (!Auth::guard('sanctum')->check() && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $userModel = $accessToken->tokenable;
                Auth::setUser($userModel);
                Auth::guard('sanctum')->setUser($userModel);
                $request->setUserResolver(function () use ($userModel) {
                    return $userModel;
                });
            }
        }

        $user = Auth::guard('sanctum')->user();

        // Allow limited public access ONLY for the ticker
        $isTickerRequest = $request->has('ticker');

        // Block all other unauthenticated access to the directory
        if (!$user && !$isTickerRequest) {
            return $this->error('Unauthorized access. Profile sharing links are required for guests.', 403);
        }

        $query = Player::with(['mainPhoto', 'agent', 'scout', 'club']);

        // If it's a ticker request, we only want visible and approved players
        if ($isTickerRequest) {
            $query->where('is_visible', true)
                  ->where('is_approved', true);
        }

        // Role-based Filtering — applies to PUBLIC role or explicit public_view flag
        if (($user && $user->role === 'PUBLIC') || $request->has('public_view')) {
            $query->where('is_visible', true)
                  ->where('is_approved', true);
            
            // Note: We removed the contract end date filter here to show both active and archived 
            // players in a single unified list, respecting pagination.
        }

        // Agent Isolation: Agents should only see their assigned players
        if (Auth::guard('sanctum')->check() && $user && $user->role === 'AGENT') {
            $agent = $user->agent;
            if ($agent) {
                $query->where('agent_id', $agent->id);
            } else {
                // Return empty if agent profile is missing
                $query->whereRaw('1 = 0');
            }
        }

        // Search by name and national ID
        if ($request->filled('search')) {
            $search = trim($request->search);
            $words = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);
            $firstWord = array_shift($words);
            
            $query->where(function ($q) use ($firstWord, $words, $search) {
                // English
                $q->where(function ($sq) use ($firstWord, $words) {
                    $sq->where('name', 'like', $firstWord . '%');
                    foreach ($words as $word) {
                        $sq->where('name', 'like', '%' . $word . '%');
                    }
                });

                // Arabic (Or)
                $q->orWhere(function ($sq) use ($firstWord, $words) {
                    $sq->where('name_ar', 'REGEXP', '^' . $this->arabicSearchService->generateRegexPattern($firstWord));
                    foreach ($words as $word) {
                        $sq->where('name_ar', 'REGEXP', $this->arabicSearchService->generateRegexPattern($word));
                    }
                });

                // National ID (Or)
                $q->orWhere('national_id', 'like', "{$search}%");
            });
        }

        // Filter by sport/position
        if ($request->has('sport')) {
            $sport = $request->sport;
            $teamSports = [
                'Football', 'Basketball', 'Volleyball', 'Handball', 'Futsal', 
                'Water Polo', 'Cricket', 'Rugby Union', 'Rugby League', 
                'American Football', 'Baseball', 'Softball', 'Ice Hockey', 
                'Field Hockey', 'Lacrosse', 'Paintball', 'Beach Soccer', 'Beach Volleyball'
            ];
            $individualSports = [
                'Tennis', 'Padel', 'Squash', 'Table Tennis', 'Badminton',
                'MMA', 'Boxing', 'Kickboxing', 'Muay Thai', 'Judo', 'Karate', 'Taekwondo', 'Wrestling', 'Jiu Jitsu', 'Sambo', 'Fencing',
                'Swimming', 'Diving', 'Rowing', 'Sailing', 'Surfing', 'Motosurf', 'Jet Ski',
                'Formula 1', 'Rally', 'Motocross', 'Karting',
                'Athletics', 'Pole Vault', 'Gymnastics', 'Triathlon', 'Cycling', 'Weightlifting',
                'Shooting', 'Archery', 'Bowling', 'Darts', 'Golf', 'Billiards', 'Snooker',
                'MOBA', 'FPS', 'Esports Strategy', 'Esports Sports',
                'Equestrian', 'Chess', 'Camel Racing', 'Falconry', 'Horse Racing'
            ];

            $isTeam = $sport === 'TEAM_CATEGORY' || (is_array($sport) && in_array('TEAM_CATEGORY', $sport));
            $isIndividual = $sport === 'INDIVIDUAL_CATEGORY' || (is_array($sport) && in_array('INDIVIDUAL_CATEGORY', $sport));

            if ($isTeam) {
                $query->whereIn('sport', $teamSports);
            } else if ($isIndividual) {
                $query->whereIn('sport', $individualSports);
            } else if (is_array($sport)) {
                $query->whereIn('sport', $sport);
            } else if ($sport !== 'All') {
                $query->where('sport', $sport);
            }
        }

        if ($request->has('position') || $request->has('positions')) {
            $positions = $request->input('positions', $request->input('position'));
            if (!is_array($positions)) {
                $positions = [$positions];
            }
            if (!in_array('All', $positions)) {
                $query->where(function($q) use ($positions) {
                    foreach($positions as $pos) {
                        $q->orWhereJsonContains('positions', $pos);
                    }
                });
            }
        }

        // Role filter
        if ($request->has('role')) {
            $role = $request->role;
            if (is_array($role)) {
                $query->whereIn('profile_role', $role);
            } else {
                $query->where('profile_role', $role);
            }
        }

        // Designer type filter
        if ($request->has('designer_type')) {
            $designerType = $request->designer_type;
            if (is_array($designerType)) {
                $query->whereIn('designer_type', $designerType);
            } else {
                $query->where('designer_type', $designerType);
            }
        }

        // Designer type filter
        if ($request->has('designer_type')) {
            $designerType = $request->designer_type;
            if (is_array($designerType)) {
                $query->whereIn('designer_type', $designerType);
            } else {
                $query->where('designer_type', $designerType);
            }
        }

        // Nationality filter
        if ($request->has('nationality')) {
            $nationality = $request->input('nationality');
            $nationalityArray = is_array($nationality) ? $nationality : [$nationality];

            $query->where(function ($q) use ($nationalityArray) {
                $q->whereIn('nationality', $nationalityArray)
                    ->orWhereIn('nationality_ar', $nationalityArray);
            });
        }

        // Club filter (supports both club_id and legacy name search)
        if ($request->has('club_id') && !empty($request->club_id)) {
            $query->where('club_id', $request->club_id);
        } elseif ($request->has('club') && !empty($request->club)) {
            $club = $request->club;
            $query->where(function ($q) use ($club) {
                if (is_array($club)) {
                    $q->whereIn('club_name_legacy', $club)->orWhereIn('club_name_ar_legacy', $club);
                } else {
                    $q->where('club_name_legacy', 'like', "%{$club}%")
                        ->orWhere('club_name_ar_legacy', 'like', "%{$club}%");
                }
            });
        }

        // Deal status
        if ($request->has('deal_status')) {
            $dealStatus = $request->deal_status;
            $dealStatusArr = is_array($dealStatus) ? $dealStatus : [$dealStatus];

            $hasFreeAgent = in_array('FREE_AGENT', $dealStatusArr) || in_array('FREE_AGENT_COACH', $dealStatusArr);
            $otherStatuses = array_filter($dealStatusArr, fn($s) => $s !== 'FREE_AGENT' && $s !== 'FREE_AGENT_COACH');

            $query->where(function ($q) use ($dealStatusArr, $hasFreeAgent, $otherStatuses) {
                if ($hasFreeAgent) {
                    // FREE_AGENT: deal_status matches AND no club assigned
                    $freeAgentStatuses = array_values(array_filter($dealStatusArr, fn($s) => $s === 'FREE_AGENT' || $s === 'FREE_AGENT_COACH'));
                    $q->where(function ($sq) use ($freeAgentStatuses) {
                        $sq->whereIn('deal_status', $freeAgentStatuses)
                           ->where(function ($clubQ) {
                               $clubQ->whereNull('club_id')
                                     ->where(function ($legacyQ) {
                                         $legacyQ->whereNull('club_name_legacy')
                                                 ->orWhere('club_name_legacy', '');
                                     });
                           });
                    });
                }
                if (!empty($otherStatuses)) {
                    // Non-free-agent statuses: just match deal_status normally
                    $q->orWhereIn('deal_status', array_values($otherStatuses));
                }
            });
        }

        // Legal Status
        if ($request->has('legal_status')) {
            $legalStatus = $request->legal_status;
            if (is_array($legalStatus)) {
                $query->whereIn('legal_status', $legalStatus);
            } else {
                $query->where('legal_status', $legalStatus);
            }
        }

        // Preferred Foot
        if ($request->has('preferred_foot')) {
            $foot = $request->preferred_foot;
            if (is_array($foot)) {
                $query->whereIn('preferred_foot', $foot);
            } else {
                $query->where('preferred_foot', $foot);
            }
        }

        // Contract Status (ACTIVE/EXPIRED)
        if ($request->has('contract_status')) {
            $status = $request->contract_status;
            $now = now()->toDateString();
            
            $statusArr = is_array($status) ? $status : [$status];
            
            if (in_array('ACTIVE', $statusArr) || in_array('PENDING', $statusArr) || in_array('NEGOTIATION', $statusArr)) {
                $query->where(function ($q) use ($now, $statusArr) {
                    $q->where(function ($mq) use ($now, $statusArr) {
                        $mq->where(function ($sq) use ($now, $statusArr) {
                            $sq->whereIn('contract_status', $statusArr)
                              ->orWhere(function ($ssq) use ($now) {
                                  $ssq->whereNull('contract_end_date')
                                     ->orWhereDate('contract_end_date', '>=', $now);
                              });
                        })
                        ->where(function ($sq) {
                            $sq->whereNull('contract_nature')
                              ->orWhere('contract_nature', '!=', 'TERMINATION');
                        });
                    })
                    ->orWhereHas('documents', function ($c) use ($now) {
                        $c->where('type', 'contract')
                          ->whereNotNull('end_date')
                          ->whereDate('end_date', '>=', $now);
                    });
                });
            } elseif (in_array('EXPIRED', $statusArr)) {
                $query->where(function ($q) use ($now) {
                    $q->where('contract_nature', 'TERMINATION')
                      ->orWhere(function ($sq) use ($now) {
                          $sq->whereNotNull('contract_end_date')
                             ->whereDate('contract_end_date', '<', $now);
                      });
                })
                ->whereDoesntHave('documents', function ($c) use ($now) {
                    $c->where('type', 'contract')
                      ->whereNotNull('end_date')
                      ->whereDate('end_date', '>=', $now);
                });
            }
        }

        // Stale CVs Filter (Updated more than 1 year ago)
        if ($request->has('stale_cvs') && filter_var($request->stale_cvs, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('updated_at', '<=', now()->subYear());
        }

        // Contract Nature
        if ($request->has('contract_nature')) {
            $nature = $request->contract_nature;
            if (is_array($nature)) {
                $query->whereIn('contract_nature', $nature);
            } else {
                $query->where('contract_nature', $nature);
            }
        }
        // --- Coach Certificate Filters ---
        if ($request->hasAny(['certificate_type', 'issuing_body', 'level', 'source_type', 'certificate_name'])) {
            $query->whereHas('certificates', function ($q) use ($request) {
                if ($request->filled('certificate_type')) {
                    $type = $request->certificate_type;
                    if (is_array($type)) {
                        $q->whereIn('certificate_type', $type);
                    } else {
                        $q->where('certificate_type', $type);
                    }
                }

                if ($request->filled('issuing_body')) {
                    $q->where('issuing_body', 'like', '%' . $request->issuing_body . '%');
                }

                if ($request->filled('level')) {
                    $level = $request->level;
                    if (is_array($level)) {
                        $q->whereIn('level', $level);
                    } else {
                        $q->where('level', $level);
                    }
                }

                if ($request->filled('source_type')) {
                    $source = $request->source_type;
                    if (is_array($source)) {
                        $q->whereIn('source_type', $source);
                    } else {
                        $q->where('source_type', $source);
                    }
                }

                if ($request->filled('certificate_name')) {
                    $q->where('certificate_name', 'like', '%' . $request->certificate_name . '%');
                }
            });
        }

        if ($request->has('is_local')) {
            $isLocal = filter_var($request->is_local, FILTER_VALIDATE_BOOLEAN);
            $localNatsEn = ['Kuwaiti', 'Saudi', 'Bahraini', 'No Nationality', 'Bidoon'];
            $localNatsAr = ['كويتي', 'سعودي', 'بحريني', 'بدون', 'غير محدد الجنسية'];

            if ($isLocal) {
                $query->where(function ($q) use ($localNatsEn, $localNatsAr) {
                    $q->whereIn('nationality', $localNatsEn)
                        ->orWhereIn('nationality_ar', $localNatsAr)
                        ->orWhere('born_in_kuwait', true);
                });
            } else {
                $query->where(function ($q) use ($localNatsEn, $localNatsAr) {
                    $q->where(function ($sq) use ($localNatsEn) {
                        $sq->whereNotIn('nationality', $localNatsEn)
                            ->orWhereNull('nationality');
                    })->where(function ($sq) use ($localNatsAr) {
                        $sq->whereNotIn('nationality_ar', $localNatsAr)
                            ->orWhereNull('nationality_ar');
                    })->where('born_in_kuwait', '!=', true);
                });
            }
        }
        if ($request->has('is_approved')) {
            $isApproved = filter_var($request->is_approved, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_approved', $isApproved);
        } else {
            // Default to showing only approved players for everyone
            $query->where('is_approved', true);
        }

        if ($request->has('is_visible')) {
            $isVisible = filter_var($request->is_visible, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_visible', $isVisible);
        } else if (!Auth::guard('sanctum')->check() || ($user && $user->role === 'PUBLIC')) {
            $query->where('is_visible', true);
        }

        // Market Value range
        if ($request->has('market_value_min')) {
            $query->where('market_value', '>=', $request->market_value_min);
        }
        if ($request->has('market_value_max')) {
            $query->where('market_value', '<=', $request->market_value_max);
        }

        // Age range (calculated from date_of_birth)
        if ($request->has('age_min')) {
            $year = now()->subYears($request->age_min)->year;
            $query->whereRaw('CAST(LEFT(date_of_birth, 4) AS UNSIGNED) <= ?', [$year]);
        }
        if ($request->has('age_max')) {
            $year = now()->subYears($request->age_max)->year;
            $query->whereRaw('CAST(LEFT(date_of_birth, 4) AS UNSIGNED) >= ?', [$year]);
        }

        // Date Range Filters (Contract End Date)
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('contract_end_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('contract_end_date', '<=', $request->end_date);
        }

        // Contract Filters
        if ($request->anyFilled(['contract_type', 'contract_expiry_year', 'contract_start_year', 'contract_duration', 'remaining_duration'])) {
            // New direct filters for better performance (primary)
            if ($request->has('contract_expiry_year')) {
                $years = (array) $request->contract_expiry_year;
                $query->where(function ($q) use ($years) {
                    foreach ($years as $year) {
                        $q->orWhereYear('contract_end_date', $year);
                    }
                });
            }

            if ($request->has('contract_start_year')) {
                $years = (array) $request->contract_start_year;
                $query->where(function ($q) use ($years) {
                    foreach ($years as $year) {
                        $q->orWhereYear('contract_start_date', $year);
                    }
                });
            }

            if ($request->has('contract_duration')) {
                $durations = (array) $request->contract_duration;
                $query->where(function ($q) use ($durations) {
                    foreach ($durations as $duration) {
                        if ($duration === 'lessThan1year') {
                            $q->orWhere('contract_duration', '<', 1.0);
                        } elseif ($duration === '1year') {
                            $q->orWhere('contract_duration', '=', 1.0);
                        } elseif ($duration === 'moreThan1year') {
                            $q->orWhere('contract_duration', '>', 1.0);
                        }
                    }
                });
            }

            if ($request->has('remaining_duration')) {
                $remainings = (array) $request->remaining_duration;
                $query->where(function ($q) use ($remainings) {
                    foreach ($remainings as $r) {
                        if ($r === '3months') {
                            $q->orWhere(function ($sq) {
                                $sq->where('contract_end_date', '<=', now()->addMonths(3))
                                    ->where('contract_end_date', '>=', now());
                            });
                        } elseif ($r === '2months') {
                            $q->orWhere(function ($sq) {
                                $sq->where('contract_end_date', '<=', now()->addMonths(2))
                                    ->where('contract_end_date', '>=', now());
                            });
                        } elseif ($r === '6months') {
                            $q->orWhere(function ($sq) {
                                $sq->where('contract_end_date', '<=', now()->addMonths(6))
                                    ->where('contract_end_date', '>=', now());
                            });
                        } elseif ($r === '1year') {
                            $q->orWhere(function ($sq) {
                                $sq->where('contract_end_date', '<=', now()->addYear())
                                    ->where('contract_end_date', '>=', now());
                            });
                        } elseif ($r === '2years') {
                            $q->orWhere(function ($sq) {
                                $sq->where('contract_end_date', '<=', now()->addYears(2))
                                    ->where('contract_end_date', '>=', now())
                                    ->where('contract_status', 'ACTIVE');
                            });
                        } elseif ($r === 'moreThan2years') {
                            $q->orWhere('contract_end_date', '>', now()->addYears(2));
                        }
                    }
                });
            }


            // Contract Type Filter
            if ($request->has('contract_type')) {
                $type = $request->contract_type;
                $query->where(function ($q) use ($type) {
                    // Check direct column on player
                    if (is_array($type)) {
                        $q->whereIn('contract_type', $type);
                    } else {
                        $q->where('contract_type', $type);
                    }

                    // Also check related contracts table (OR condition)
                    $q->orWhereHas('contracts', function ($sq) use ($type) {
                        if (is_array($type)) {
                            $sq->whereIn('type', $type);
                        } else {
                            $sq->where('type', $type);
                        }
                    });
                });
            }
        }

        // Nutrition Records Filter
        if ($request->hasAny(['has_nutrition', 'has_nutrition_records', 'with_records'])) {
            $hasNutrition = filter_var($request->input('has_nutrition', 
                               $request->input('has_nutrition_records', 
                               $request->input('with_records'))), FILTER_VALIDATE_BOOLEAN);
            
            $relations = ['physicalReports', 'nutritionPrograms', 'trainingPrograms', 'progressPhotos'];
            
            if ($hasNutrition) {
                $query->where(function($q) use ($relations) {
                    foreach($relations as $rel) {
                        $q->orWhereHas($rel);
                    }
                });
            } else {
                $query->where(function($q) use ($relations) {
                    foreach($relations as $rel) {
                        $q->whereDoesntHave($rel);
                    }
                });
            }
        }

        // Sponsorships Filter
        if ($request->has('has_sponsorships')) {
            $hasSponsorships = filter_var($request->has_sponsorships, FILTER_VALIDATE_BOOLEAN);
            if ($hasSponsorships) {
                $query->whereHas('sponsors');
            } else {
                $query->whereDoesntHave('sponsors');
            }
        }

        $perPage = $request->get('per_page', 8);
        $now = now()->toDateString();

        // Order: Active players first (by status or future end date), then expired/others. 
        // Secondary: newest additions first.
        $query->orderByRaw("
            CASE 
                WHEN UPPER(contract_status) = 'ACTIVE' THEN 0
                WHEN contract_end_date IS NULL THEN 0
                WHEN contract_end_date >= ? THEN 0
                ELSE 1 
            END
        ", [$now])
        ->orderBy('created_at', 'desc')
        ->orderBy('id', 'desc');

        if ($perPage == -1) {
            $players = $query->get();
            return $this->success(['data' => PlayerResource::collection($players)]);
        }

        $players = $query->paginate($perPage);

        return $this->success(PlayerResource::collection($players)->response()->getData(true));
    }

    /**
     * Store a newly created player.
     */
    public function store(StorePlayerRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Auto-assign agent if creator is an AGENT
        $user = Auth::guard('sanctum')->user();
        if ($user && $user->role === 'AGENT' && $user->agent) {
            $data['agent_id'] = $user->agent->id;
        }

        // Auto-assign scout_id if creator is an ADMIN with is_scout=true
        if ($user && $user->role === 'ADMIN' && $user->adminProfile && $user->adminProfile->is_scout) {
            $data['scout_id'] = $user->adminProfile->id;
        }

        // Map legacy club name fields to the renamed columns
        if (array_key_exists('club', $data)) {
            $data['club_name_legacy'] = $data['club'];
            unset($data['club']);
        }
        if (array_key_exists('club_ar', $data)) {
            $data['club_name_ar_legacy'] = $data['club_ar'];
            unset($data['club_ar']);
        }

        $player = Player::create($data);

        // Generate slug
        if ($player->name) {
            $player->slug = $this->generateUniqueSlug($player->name);
            $player->save();
        }

        if (isset($data['sponsors'])) {
            $player->sponsors()->sync($data['sponsors']);
        }

        if (isset($data['club_contracts'])) {
            $this->syncClubContracts($player, $data['club_contracts']);
        }

        if (isset($data['certificates'])) {
            $this->syncCertificates($player, $data['certificates']);
        }

        return $this->success(new PlayerResource($player->load(['sponsors', 'clubContracts', 'certificates', 'scout', 'club'])), 'Player created successfully', 201);
    }

    /**
     * Store a player from the public registration form.
     */
    public function publicStore(StorePlayerRequest $request): JsonResponse
    {
        $data = $request->validated();
        
        // Force unapproved and hidden
        $data['is_approved'] = false;
        $data['is_visible'] = false;
        
        // External CV submissions default to NOT_JOINED contract nature
        $data['contract_nature'] = 'NOT_JOINED';

        // Map legacy club name fields to the renamed columns
        if (array_key_exists('club', $data)) {
            $data['club_name_legacy'] = $data['club'];
            unset($data['club']);
        }
        if (array_key_exists('club_ar', $data)) {
            $data['club_name_ar_legacy'] = $data['club_ar'];
            unset($data['club_ar']);
        }
        
        $player = Player::create($data);
        
        // Generate slug
        if ($player->name) {
            $player->slug = $this->generateUniqueSlug($player->name);
            $player->save();
        }
        
        return $this->success(new PlayerResource($player->load('club')), 'Your registration request has been submitted for review.', 201);
    }

    /**
     * Display the specified player.
     */
    public function show(Request $request, $id): JsonResponse
    {
        if (!Auth::guard('sanctum')->check() && $token = $request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if ($accessToken && $accessToken->tokenable) {
                $userModel = $accessToken->tokenable;
                Auth::setUser($userModel);
                Auth::guard('sanctum')->setUser($userModel);
                $request->setUserResolver(function () use ($userModel) {
                    return $userModel;
                });
            }
        }
        // Try finding by ID, share_token, or slug
        $player = Player::where('id', $id)
                        ->orWhere('share_token', $id)
                        ->orWhere('slug', $id)
                        ->first();

        if (!$player) {
            // Handle slug-like ID (Name_ID or Name_Token) for backward compatibility
            if (is_string($id) && str_contains($id, '_')) {
                $parts = explode('_', $id);
                $actualId = end($parts);
                $player = Player::where('id', $actualId)
                                ->orWhere('share_token', $actualId)
                                ->first();
            }
        }

        if (!$player) {
            return $this->error('Player not found', 404);
        }

        $user = Auth::guard('sanctum')->user();
        $shareToken = $request->query('share_token') ?? $request->query('t') ?? ($id === $player->share_token ? $id : null);

        // Access via Slug or Share Token
        if (($id === $player->slug && $player->slug) || ($shareToken && $shareToken === $player->share_token)) {
            // Lock this guest session to this player
            if (!$user) {
                session(['guest_authorized_player_id' => $player->id]);
            }

            // Priority 1: If valid share token or slug is present, allow viewing full details regardless of login status
            $player->load(['mainPhoto', 'photos', 'agent', 'contracts', 'documents', 'sponsors', 'clubContracts', 'certificates', 'scout', 'club']);
            return $this->success(new PlayerResource($player));
        }

        // Guest Session Lock Check
        if (!$user && session()->has('guest_authorized_player_id')) {
            if (session('guest_authorized_player_id') == $player->id) {
                // Allow access if session matches
                $player->load(['mainPhoto', 'photos', 'agent', 'contracts', 'documents', 'sponsors', 'clubContracts', 'certificates', 'scout', 'club']);
                return $this->success(new PlayerResource($player));
            } else {
                return $this->error('This guest session is restricted to a specific profile.', 403);
            }
        }

        // Unauthenticated users or PUBLIC role users can only see visible/approved players
        if (!$user || $user->role === 'PUBLIC') {
            // Allow users to view their own linked record even if not visible/approved
            if (!$user || $user->id !== $player->user_id) {
                if (!$player->is_visible || !$player->is_approved) {
                    if (!$user) {
                        return $this->error('Unauthorized access. Profile sharing links are required.', 403);
                    }
                    return $this->error('Player not found', 404);
                }
            }
        }

        // Authorization check: Only OWNER, ADMIN, and AGENT (for their assigned players) and PUBLIC can view
        if ($user && $user->role === 'AGENT') {
            // Agents can only see their assigned players
            $agent = $user->agent;
            if (!$agent || $player->agent_id !== $agent->id) {
                return $this->error('Unauthorized', 403);
            }
        }

        $player->load(['mainPhoto', 'photos', 'agent', 'contracts', 'documents', 'sponsors', 'clubContracts', 'certificates', 'scout', 'club']);

        return $this->success(new PlayerResource($player));
    }

    /**
     * Serve player profile with SEO meta tags for social sharing.
     */
    public function shareProfile(Request $request, $id)
    {
        // Try finding by ID, share_token, or slug
        $player = Player::with(['mainPhoto', 'club'])
                        ->where('id', $id)
                        ->orWhere('share_token', $id)
                        ->orWhere('slug', $id)
                        ->first();
        
        if (!$player) {
            if (is_string($id) && str_contains($id, '_')) {
                $parts = explode('_', $id);
                $lastPart = end($parts);
                
                // Handle Slug_ID-TOKEN format
                if (str_contains($lastPart, '-')) {
                    $subParts = explode('-', $lastPart);
                    $actualId = $subParts[0];
                    $pathToken = $subParts[1] ?? null;
                } else {
                    $actualId = $lastPart;
                    $pathToken = null;
                }

                $query = Player::with(['mainPhoto', 'club'])
                                ->where('id', $actualId)
                                ->orWhere('share_token', $actualId);
                
                if ($pathToken) {
                    $query->orWhere('share_token', $pathToken);
                }
                
                $player = $query->first();
            }
        }

        if (!$player) {
            $frontendDomain = rtrim(env('FRONTEND_URL', 'https://ashkananitransfer.com'), '/');
            return redirect($frontendDomain . '/players');
        }

        $isAr = app()->getLocale() === 'ar';
        $playerName = $isAr ? ($player->name_ar ?: $player->name) : $player->name;
        $clubName = $player->club ? ($isAr ? ($player->club->name_ar ?: $player->club->name) : $player->club->name) : ($isAr ? ($player->club_name_ar_legacy ?: $player->club_name_legacy) : $player->club_name_legacy);
        $sport = $player->sport ?: 'Football';

        $pageTitle = "{$playerName} | {$sport}" . ($clubName ? " - {$clubName}" : "");
        $metaDescription = $isAr 
            ? "اكتشف السيرة الذاتية لـ {$playerName}، لاعب {$sport}" . ($clubName ? " في نادي {$clubName}" : "") . ". شاهد الإحصائيات، القيمة السوقية، واللقطات."
            : "Discover the professional profile of {$playerName}, {$sport} athlete" . ($clubName ? " at {$clubName}" : "") . ". View stats, market value, and highlights.";

        $frontendUrl = rtrim(env('FRONTEND_URL', 'https://ashkananitransfer.com'), '/');
        $ogImage = $frontendUrl . '/logo2.png';
        if ($player->mainPhoto && $player->mainPhoto->url) {
            $photoUrl = $player->mainPhoto->url;
            $ogImage = str_starts_with($photoUrl, 'http') ? $photoUrl : url($photoUrl);
        }

        $slug = $player->slug ?: ((string)$player->id);
        $shareToken = $request->query('share_token') ?? $request->query('t') ?? ($pathToken ?? null);
        
        if (!$shareToken) {
            if ($id === $player->share_token) {
                $shareToken = $id;
            } elseif (isset($actualId) && $actualId === $player->share_token) {
                $shareToken = $actualId;
            }
        }
        
        return view('share-player', [
            'ogTitle' => $pageTitle,
            'ogDescription' => $metaDescription,
            'ogImage' => $ogImage,
            'slug' => $slug,
            'shareToken' => $shareToken
        ]);
    }

    /**
     * Generate or refresh a share token for a player.
     */
    public function generateShareToken(Player $player): JsonResponse
    {
        $user = Auth::user();

        // Authorization check
        $isAuthorized = false;
        if (in_array($user->role, ['OWNER', 'ADMIN'])) {
            $isAuthorized = true;
        } elseif ($user->role === 'AGENT' && $player->agent_id === $user->agent?->id) {
            $isAuthorized = true;
        } elseif ($player->user_id === $user->id) {
            $isAuthorized = true;
        }

        if (!$isAuthorized) {
            return $this->error('Unauthorized', 403);
        }

        // Force very short token (8 chars) to keep URLs clean
        if (!$player->share_token || strlen($player->share_token) !== 8) {
            $player->share_token = bin2hex(random_bytes(4));
            $player->save();
        }

        return $this->success(['share_token' => $player->share_token], 'Share link retrieved successfully');
    }

    /**
     * Update the specified player.
     */
    public function update(UpdatePlayerRequest $request, Player $player): JsonResponse
    {
        $validated = $request->validated();

        // Map legacy club name fields to the renamed columns
        if (array_key_exists('club', $validated)) {
            $validated['club_name_legacy'] = $validated['club'];
            unset($validated['club']);
        }
        if (array_key_exists('club_ar', $validated)) {
            $validated['club_name_ar_legacy'] = $validated['club_ar'];
            unset($validated['club_ar']);
        }

        // When club_id is explicitly cleared, force-clear legacy club name fields too
        if (array_key_exists('club_id', $validated) && empty($validated['club_id'])) {
            $validated['club_id'] = null;
            $validated['club_name_legacy'] = null;
            $validated['club_name_ar_legacy'] = null;
        }

        $player->update($validated);

        // Update slug if name changed and it's not a generic slug
        if (isset($validated['name']) && $player->name !== $validated['name']) {
            $player->slug = $this->generateUniqueSlug($validated['name']);
            $player->save();
        }

        if (isset($validated['sponsors'])) {
            $player->sponsors()->sync($validated['sponsors']);
        }

        if (isset($validated['club_contracts'])) {
            $this->syncClubContracts($player, $validated['club_contracts']);
        }

        if (isset($validated['certificates'])) {
            $this->syncCertificates($player, $validated['certificates']);
        }

        return $this->success(new PlayerResource($player->load(['sponsors', 'clubContracts', 'certificates', 'club'])), 'Player updated successfully');
    }

    private function syncCertificates(Player $player, array $certificates): void
    {
        $existingIds = $player->certificates()->pluck('id')->toArray();
        $newIds = collect($certificates)->pluck('id')->filter()->toArray();

        // Safety guard: only delete removed certificates when at least one incoming
        // certificate has a tracked server-side ID. This prevents mass-deletion
        // caused by retried requests where IDs were not yet available (e.g. after
        // a server timeout on a previous attempt).
        if (!empty($newIds)) {
            $toDelete = array_diff($existingIds, $newIds);
            if (!empty($toDelete)) {
                $player->certificates()->whereIn('id', $toDelete)->delete();
            }
        }

        // Update or create
        foreach ($certificates as $certData) {
            if (isset($certData['id'])) {
                $id = $certData['id'];
                unset($certData['id']);
                $player->certificates()->where('id', $id)->update($certData);
            } else {
                $player->certificates()->create($certData);
            }
        }
    }

    private function syncClubContracts(Player $player, array $contracts): void
    {
        $existingIds = $player->clubContracts()->pluck('id')->toArray();
        $newIds = collect($contracts)->pluck('id')->filter()->toArray();

        // Safety guard: only delete removed contracts when at least one incoming
        // contract has a tracked server-side ID. This prevents mass-deletion
        // caused by retried requests where IDs were not yet available (e.g. after
        // a server timeout on a previous attempt).
        if (!empty($newIds)) {
            $toDelete = array_diff($existingIds, $newIds);
            if (!empty($toDelete)) {
                $player->clubContracts()->whereIn('id', $toDelete)->delete();
            }
        }

        // Update or create
        foreach ($contracts as $contractData) {
            if (isset($contractData['id'])) {
                $player->clubContracts()->where('id', $contractData['id'])->update($contractData);
            } else {
                $player->clubContracts()->create($contractData);
            }
        }
    }

    /**
     * Generate a unique slug for a player.
     */
    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);

        // If slug is empty (e.g. Arabic-only name with no transliteration),
        // fall back to a random identifier to prevent an infinite while-loop.
        if (empty($slug)) {
            return 'player-' . strtolower(Str::random(8));
        }

        $originalSlug = $slug;
        $count = 1;

        while (Player::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * One-time sync to populate slugs for all players.
     */
    public function syncSlugs(): JsonResponse
    {
        $players = Player::whereNull('slug')->orWhere('slug', '')->get();
        $count = 0;

        foreach ($players as $player) {
            if ($player instanceof Player) {
                $player->slug = $this->generateUniqueSlug($player->name);
                $player->save();
                $count++;
            }
        }

        return response()->json(['message' => "Synced {$count} slugs."]);
    }

    /**
     * Remove the specified player.
     */
    public function destroy(Player $player): JsonResponse
    {
        $player->delete();

        return $this->success(null, 'Player deleted successfully');
    }
}
