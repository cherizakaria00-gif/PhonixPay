<?php

namespace Database\Seeders;

use App\Models\RewardLevel;
use Illuminate\Database\Seeder;

class RewardLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'level_number' => 1,
                'name' => 'Level 1',
                'required_qualified_referrals' => 10,
                'benefits' => [
                    'discount_percent' => 50,
                    'discount_duration_months' => 3,
                ],
                'is_active' => true,
            ],
            [
                'level_number' => 2,
                'name' => 'Level 2',
                'required_qualified_referrals' => 20,
                'benefits' => [
                    'badge' => true,
                    'priority_support' => true,
                ],
                'is_active' => true,
            ],
            [
                'level_number' => 3,
                'name' => 'Level 3',
                'required_qualified_referrals' => 50,
                'benefits' => [
                    'revenue_share_bps' => 50,
                ],
                'is_active' => true,
            ],
        ];

        foreach ($levels as $level) {
            RewardLevel::updateOrCreate(
                ['level_number' => $level['level_number']],
                $level
            );
        }
    }
}
