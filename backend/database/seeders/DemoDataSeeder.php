<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if demo data already exists
        $existingTenant1 = Tenant::where('slug', 'acme')->first();
        if ($existingTenant1) {
            $this->command->info('Demo data already exists. Skipping...');
            return;
        }

        // Create Demo Tenant 1 - Use DB directly to set name and slug columns
        $tenantId1 = Str::uuid()->toString();
        DB::table('tenants')->insert([
            'id' => $tenantId1,
            'name' => 'Acme Corporation',
            'slug' => 'acme',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tenant1 = Tenant::find($tenantId1);

        // Create users for tenant 1
        $manager1 = User::create([
            'name' => 'John Manager',
            'email' => 'manager@acme.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        $teamLead1 = User::create([
            'name' => 'Jane Team Lead',
            'email' => 'teamlead@acme.com',
            'password' => Hash::make('password'),
            'role' => 'team_lead',
        ]);

        $agent1 = User::create([
            'name' => 'Bob Agent',
            'email' => 'agent@acme.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);

        $agent2 = User::create([
            'name' => 'Alice Agent',
            'email' => 'alice@acme.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);

        // Link users to tenant 1
        $tenant1->users()->attach([$manager1->id, $teamLead1->id, $agent1->id, $agent2->id]);

        // Create subscription for tenant 1
        tenancy()->initialize($tenant1);
        Subscription::create([
            'tenant_id' => $tenant1->id,
            'plan' => 'pro',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // Create tasks for tenant 1
        Task::create([
            'tenant_id' => $tenant1->id,
            'title' => 'Setup new project',
            'description' => 'Initialize new project repository and documentation',
            'status' => 'pending',
            'assigned_to' => $agent1->id,
            'created_by' => $manager1->id,
        ]);

        Task::create([
            'tenant_id' => $tenant1->id,
            'title' => 'Review code changes',
            'description' => 'Review and approve pending pull requests',
            'status' => 'in_progress',
            'assigned_to' => $teamLead1->id,
            'created_by' => $manager1->id,
        ]);

        Task::create([
            'tenant_id' => $tenant1->id,
            'title' => 'Update documentation',
            'description' => 'Update API documentation with latest changes',
            'status' => 'completed',
            'assigned_to' => $agent2->id,
            'created_by' => $teamLead1->id,
        ]);

        // End tenancy for tenant 1
        tenancy()->end();

        // Create Demo Tenant 2 - Use DB directly to set name and slug columns
        $tenantId2 = Str::uuid()->toString();
        DB::table('tenants')->insert([
            'id' => $tenantId2,
            'name' => 'Tech Startup Inc',
            'slug' => 'techstartup',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $tenant2 = Tenant::find($tenantId2);

        // Create users for tenant 2
        $manager2 = User::create([
            'name' => 'Sarah Manager',
            'email' => 'sarah@techstartup.com',
            'password' => Hash::make('password'),
            'role' => 'manager',
        ]);

        $agent3 = User::create([
            'name' => 'Mike Agent',
            'email' => 'mike@techstartup.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);

        // Link users to tenant 2
        $tenant2->users()->attach([$manager2->id, $agent3->id]);

        // Create subscription for tenant 2
        tenancy()->initialize($tenant2);
        Subscription::create([
            'tenant_id' => $tenant2->id,
            'plan' => 'basic',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // Create tasks for tenant 2
        Task::create([
            'tenant_id' => $tenant2->id,
            'title' => 'Design landing page',
            'description' => 'Create mockups for new landing page',
            'status' => 'pending',
            'assigned_to' => $agent3->id,
            'created_by' => $manager2->id,
        ]);

        tenancy()->end();

        $this->command->info('Demo data seeded successfully!');
        $this->command->info('Tenant 1 (Acme): manager@acme.com / password');
        $this->command->info('Tenant 2 (TechStartup): sarah@techstartup.com / password');
    }
}
