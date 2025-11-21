<?php

namespace App\Http\Controllers;

use App\Models\DashboardWidget;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get dashboard widgets based on user role.
     */
    public function widgets(Request $request): JsonResponse
    {
        $user = $request->user();
        $widgets = DashboardWidget::forRole($user->role)
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'widgets' => $widgets,
        ]);
    }

    /**
     * Get dashboard statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = tenancy()->tenant;

        $stats = [];

        // Common stats for all roles
        $stats['total_tasks'] = Task::count();
        $stats['pending_tasks'] = Task::where('status', 'pending')->count();
        $stats['completed_tasks'] = Task::where('status', 'completed')->count();

        // Role-specific stats
        if ($user->isManager()) {
            $stats['in_progress_tasks'] = Task::where('status', 'in_progress')->count();
            $stats['cancelled_tasks'] = Task::where('status', 'cancelled')->count();
        } elseif ($user->isTeamLead()) {
            $stats['team_tasks'] = Task::where('assigned_to', $user->id)
                ->orWhereIn('assigned_to', function ($query) use ($user) {
                    // Get team members (assuming team structure)
                    // For demo, we'll just return user's tasks
                })
                ->count();
        } elseif ($user->isAgent()) {
            $stats['my_tasks'] = Task::where('assigned_to', $user->id)->count();
            $stats['my_pending_tasks'] = Task::where('assigned_to', $user->id)
                ->where('status', 'pending')
                ->count();
        }

        return response()->json([
            'stats' => $stats,
        ]);
    }
}
