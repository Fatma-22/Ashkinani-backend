<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Player;

class CleanClubsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'players:clean-clubs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean and normalize player clubs in the database';

    /**
     * Mapping for normalization
     */
    protected $mapping = [
        // الكويت
        'الكويت الكويتي' => 'الكويت',
        'نادي الكويت' => 'الكويت',
        
        // العربي
        'العربي الكويتي' => 'العربي',
        'النادي العربي الرياضي' => 'العربي',
        'نادي العربي الكويتي' => 'العربي',
        
        // القادسية
        'القادسية الكويتي' => 'القادسية',
        'القادسية الكويتي العاب القوى' => 'القادسية',
        
        // النصر
        'النصر الكويتي' => 'النصر',
        'نادي النصر' => 'النصر',
        
        // الصليبخات
        'صليبخات' => 'الصليبخات',
        'الصليبيخات' => 'الصليبخات',
        
        // خيطان
        'خيطان الكويتي' => 'خيطان',
        'نادي خيطان تحت ٢٠' => 'خيطان',
        'الخيطان' => 'خيطان',
        
        // اليرموك
        'اليرموك الكويتي' => 'اليرموك',
        
        // التضامن
        'التضامن الكويتي' => 'التضامن',
        
        // السالمية
        'السالمية الكويتي' => 'السالمية',
        
        // كاظمة
        'كاظمة الكويتي' => 'كاظمة',
        
        // الساحل
        'الساحل - الكويتي' => 'الساحل',
        
        // النادي البحري
        'الرياضيات البحري الكويتية' => 'النادي البحري',
        'البحري' => 'النادي البحري',
        
        // جهات أخرى
        'وزارة التربية - شاطئية' => 'وزارة التربية',
        'لاعب منتخب الكويت للتنس' => 'منتخب الكويت',
        'الزوراء SC' => 'الزوراء',
        'الحدود SC' => 'الحدود',
        'دهوك SC' => 'دهوك',
        'تي إس جالاكسي إف سي' => 'تي إس جالاكسي',
        'نادي الوحدات' => 'الوحدات',
        'نادي البديع' => 'البديع',
        'الكوكب المراكوشي المغربي' => 'الكوكب المراكشي',
        'أرارات إريوان' => 'أرارات يريفان',
        'الغراف' => 'الغرافة',
        'الجديده السوري' => 'جديدة السوري',
        'باشوند. الملوك' => 'باشوندارا كينغز',
        'الاهلي' => 'الأهلي',

        // تفريغ
        'مكافحة اللعاب' => null,

        // ---------------------------------------------------- //
        // ENGLISH NORMALIZATION (Catching Machine Translations) //
        // ---------------------------------------------------- //
        
        // Kuwait SC
        'Kuwait Club' => 'Kuwait SC',
        'Kuwait Kuwaiti' => 'Kuwait SC',
        'Kuwait' => 'Kuwait SC',

        // Arabi SC
        'Al Arabi Club' => 'Al Arabi SC',
        'Kuwaiti Arab' => 'Al Arabi SC',
        'Arab Sports Club' => 'Al Arabi SC',
        'Arabic' => 'Al Arabi SC',
        
        // Qadsia SC
        'Al-Qadisiyah Kuwaiti' => 'Qadsia SC',
        'Al-Qadisiyah Kuwait Athletics' => 'Qadsia SC',

        // Nasr SC
        'Victory' => 'Al Nasr SC',
        'Al-Nasr Club' => 'Al Nasr SC',
        'Kuwaiti victory' => 'Al Nasr SC',

        // Yarmouk SC
        'Yarmouk Kuwaiti' => 'Yarmouk SC',
        'Yarmouk' => 'Yarmouk SC',

        // Sulaibikhat SC
        'Sulaibikhat' => 'Sulaibikhat SC',
        'Al-Sulaibikhat' => 'Sulaibikhat SC',
        
        // Khaitan SC
        'Khaitan Club Down Under' => 'Khaitan SC',
        'The two threads' => 'Khaitan SC',
        'Khaitan Kuwaiti' => 'Khaitan SC',

        // Salmiya SC
        'Salmiya Kuwaiti' => 'Salmiya SC',

        // Kazma SC
        'Kazma Al Kuwaiti' => 'Kazma SC',

        // Al Sahel SC
        'Al Sahel - Kuwait' => 'Al Sahel SC',

        // Marine Club
        'Kuwaiti marine mathematics' => 'Marine Club',
        'Marine' => 'Marine Club',

        // Tadhamon SC
        'Kuwait Solidarity' => 'Tadhamon SC',
        'Solidarity' => 'Tadhamon SC',

        // Burgan SC
        'Burgan' => 'Burgan SC',

        // Shabab SC
        'Youth' => 'Al Shabab SC',

        // Others
        'Kuwait national tennis team player' => 'Kuwait National Team',
        'Ministry of Education - Beach' => 'Ministry of Education',
        'Al-Zawraa SC' => 'Al-Zawraa',
        'Al-Hedod SC' => 'Al-Hedod',
        'Duhok SC' => 'Duhok',
        'TS Galaxy FC' => 'TS Galaxy',
        'Al-Wehdat SC' => 'Al-Wehdat',
        'Budaiya Club' => 'Al-Budaiya',
        'The Moroccan planet Marrakoshi' => 'Kawkab Marrakech',
        'Ararat Erewan' => 'FC Ararat Yerevan',
        'Al-Gharraf' => 'Al-Gharafa',
        'New Syrian' => 'Jdeidet Artouz',
        'Bashund. Kings' => 'Bashundhara Kings',
        'Al-Ahli' => 'Al-Ahli SC',
        'Al-Jeel' => 'Al-Jeel Club',

        // Nullify
        'Combat saliva' => null,
    ];

    public function handle()
    {
        $players = Player::whereNotNull('club')
            ->orWhereNotNull('club_ar')
            ->get();
            
        $totalChanged = 0;

        foreach ($players as $player) {
            /** @var \App\Models\Player $player */
            $changed = false;

            // Normalize English/Main club column
            if ($player->club && array_key_exists($player->club, $this->mapping)) {
                $player->club = $this->mapping[$player->club];
                $changed = true;
            }

            // Normalize Arabic club column
            if ($player->club_ar && array_key_exists($player->club_ar, $this->mapping)) {
                $player->club_ar = $this->mapping[$player->club_ar];
                $changed = true;
            }

            if ($changed) {
                // If it was nullified, we make sure it saves as proper NULL correctly in DB
                $player->save();
                $totalChanged++;
            }
        }

        $this->info("--------------------------------------------------");
        $this->info("Total players modified: " . $totalChanged);
        $this->info("Clubs database cleaned and standardized successfully.");
    }
}
