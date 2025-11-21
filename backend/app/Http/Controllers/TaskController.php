<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Get list of tasks (tenant-scoped).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Task::query();

        // Role-based filtering
        if ($user->isAgent()) {
            // Agents only see their own tasks
            $query->where('assigned_to', $user->id);
        } elseif ($user->isTeamLead()) {
            // Team leads see tasks assigned to them or their team
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
        }
        // Managers see all tasks (no additional filter)

        // Status filter
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $tasks = $query->with(['assignedUser', 'creator'])
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($tasks);
    }

    /**
     * Create a new task.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $tenant = tenancy()->tenant;

        $task = Task::create([
            'tenant_id' => $tenant->id,
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? 'pending',
            'assigned_to' => $request->assigned_to,
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task->load(['assignedUser', 'creator']),
        ], 201);
    }

    /**
     * Get a specific task.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $task = Task::with(['assignedUser', 'creator'])->findOrFail($id);

        // Role-based access check
        if ($user->isAgent() && $task->assigned_to !== $user->id) {
            return response()->json([
                'message' => 'You do not have permission to view this task'
            ], 403);
        }

        return response()->json([
            'task' => $task,
        ]);
    }

    /**
     * Update a task.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $task = Task::findOrFail($id);

        // Role-based access check
        if ($user->isAgent() && $task->assigned_to !== $user->id) {
            return response()->json([
                'message' => 'You do not have permission to update this task'
            ], 403);
        }

        $task->update($request->only(['title', 'description', 'status', 'assigned_to']));

        return response()->json([
            'message' => 'Task updated successfully',
            'task' => $task->load(['assignedUser', 'creator']),
        ]);
    }

    /**
     * Delete a task.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $task = Task::findOrFail($id);

        // Only managers and task creators can delete
        if (!$user->isManager() && $task->created_by !== $user->id) {
            return response()->json([
                'message' => 'You do not have permission to delete this task'
            ], 403);
        }

        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }
}
