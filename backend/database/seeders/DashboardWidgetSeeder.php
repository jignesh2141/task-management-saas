<?php

namespace Database\Seeders;

use App\Models\DashboardWidget;
use Illuminate\Database\Seeder;

class DashboardWidgetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $widgets = [
            // Manager widgets
            [
                'role' => 'manager',
                'widget_key' => 'user_management',
                'widget_name' => 'User Management',
                'description' => 'Manage users and their roles',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'role' => 'manager',
                'widget_key' => 'reports',
                'widget_name' => 'Reports',
                'description' => 'View detailed reports and analytics',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'role' => 'manager',
                'widget_key' => 'analytics',
                'widget_name' => 'Analytics',
                'description' => 'Performance metrics and analytics',
                'is_active' => true,
                'order' => 3,
            ],
            [
                'role' => 'manager',
                'widget_key' => 'activity_logs',
                'widget_name' => 'Activity Logs',
                'description' => 'View system activity logs',
                'is_active' => true,
                'order' => 4,
            ],
            [
                'role' => 'manager',
                'widget_key' => 'subscription_overview',
                'widget_name' => 'Subscription Overview',
                'description' => 'Current subscription and billing',
                'is_active' => true,
                'order' => 5,
            ],

            // Team Lead widgets
            [
                'role' => 'team_lead',
                'widget_key' => 'team_tasks',
                'widget_name' => 'Team Tasks',
                'description' => 'Tasks assigned to your team',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'role' => 'team_lead',
                'widget_key' => 'performance_metrics',
                'widget_name' => 'Performance Metrics',
                'description' => 'Team performance statistics',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'role' => 'team_lead',
                'widget_key' => 'team_activity',
                'widget_name' => 'Team Activity',
                'description' => 'Recent team activity',
                'is_active' => true,
                'order' => 3,
            ],

            // Agent widgets
            [
                'role' => 'agent',
                'widget_key' => 'my_tasks',
                'widget_name' => 'My Tasks',
                'description' => 'Tasks assigned to you',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'role' => 'agent',
                'widget_key' => 'notifications',
                'widget_name' => 'Notifications',
                'description' => 'Your notifications',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'role' => 'agent',
                'widget_key' => 'personal_stats',
                'widget_name' => 'Personal Stats',
                'description' => 'Your performance statistics',
                'is_active' => true,
                'order' => 3,
            ],
        ];

        foreach ($widgets as $widget) {
            DashboardWidget::updateOrCreate(
                ['widget_key' => $widget['widget_key']],
                $widget
            );
        }
    }
}
