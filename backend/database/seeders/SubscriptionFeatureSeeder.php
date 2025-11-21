<?php

namespace Database\Seeders;

use App\Models\SubscriptionFeature;
use Illuminate\Database\Seeder;

class SubscriptionFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $features = [
            // Basic Plan Features
            [
                'plan' => 'basic',
                'feature_key' => 'max_agents',
                'feature_name' => 'Maximum Agents',
                'description' => 'Maximum number of agents allowed',
                'is_enabled' => true,
                'limit_value' => 5,
            ],
            [
                'plan' => 'basic',
                'feature_key' => 'basic_tasks',
                'feature_name' => 'Basic Task Management',
                'description' => 'Create and manage basic tasks',
                'is_enabled' => true,
                'limit_value' => null,
            ],
            [
                'plan' => 'basic',
                'feature_key' => 'no_automation',
                'feature_name' => 'No Automation',
                'description' => 'Automation features not available',
                'is_enabled' => false,
                'limit_value' => null,
            ],

            // Pro Plan Features
            [
                'plan' => 'pro',
                'feature_key' => 'max_agents',
                'feature_name' => 'Maximum Agents',
                'description' => 'Maximum number of agents allowed',
                'is_enabled' => true,
                'limit_value' => 20,
            ],
            [
                'plan' => 'pro',
                'feature_key' => 'advanced_tasks',
                'feature_name' => 'Advanced Task Management',
                'description' => 'Advanced task features and customization',
                'is_enabled' => true,
                'limit_value' => null,
            ],
            [
                'plan' => 'pro',
                'feature_key' => 'basic_automation',
                'feature_name' => 'Basic Automation',
                'description' => 'Basic automation tools',
                'is_enabled' => true,
                'limit_value' => null,
            ],
            [
                'plan' => 'pro',
                'feature_key' => 'reports',
                'feature_name' => 'Reports',
                'description' => 'Access to reporting features',
                'is_enabled' => true,
                'limit_value' => null,
            ],

            // Enterprise Plan Features
            [
                'plan' => 'enterprise',
                'feature_key' => 'max_agents',
                'feature_name' => 'Maximum Agents',
                'description' => 'Maximum number of agents allowed',
                'is_enabled' => true,
                'limit_value' => null, // Unlimited
            ],
            [
                'plan' => 'enterprise',
                'feature_key' => 'all_features',
                'feature_name' => 'All Features',
                'description' => 'Access to all features',
                'is_enabled' => true,
                'limit_value' => null,
            ],
            [
                'plan' => 'enterprise',
                'feature_key' => 'advanced_automation',
                'feature_name' => 'Advanced Automation',
                'description' => 'Advanced automation tools',
                'is_enabled' => true,
                'limit_value' => null,
            ],
            [
                'plan' => 'enterprise',
                'feature_key' => 'advanced_reports',
                'feature_name' => 'Advanced Reports',
                'description' => 'Advanced reporting and analytics',
                'is_enabled' => true,
                'limit_value' => null,
            ],
            [
                'plan' => 'enterprise',
                'feature_key' => 'api_access',
                'feature_name' => 'API Access',
                'description' => 'Access to API endpoints',
                'is_enabled' => true,
                'limit_value' => null,
            ],
        ];

        foreach ($features as $feature) {
            SubscriptionFeature::updateOrCreate(
                [
                    'plan' => $feature['plan'],
                    'feature_key' => $feature['feature_key']
                ],
                $feature
            );
        }
    }
}
