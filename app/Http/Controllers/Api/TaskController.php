<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskCollection;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Requests\UpdateStatusRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Task::with(['team', 'assignedUser', 'creator']);

        if ($user->isAdmin()) {
            $tasks = $query->get();
        } elseif ($user->isTeamLead()) {

            $teamIds = $user->teamsAsLead()->pluck('id');
            $tasks = $query->whereIn('team_id', $teamIds)->get();
        } else {
            $teamIds = $user->teamsAsMember()->pluck('id');
            $tasks = $query->where(function ($q) use ($user, $teamIds) {
                $q->where('assigned_to', $user->id)
                    ->orWhereIn('team_id', $teamIds);
            })->get();
        }


        return new TaskCollection($tasks);
    }


    public function store(TaskStoreRequest $request)
    {
        $user = $request->user();

        try {
            $team = Team::where('uuid', $request->team_id)->firstOrFail();
            $assignedUser = User::where('uuid', $request->assigned_to)->first();
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found'
            ], 404);
        }

        if (!$user->isAdmin() && $team->team_lead_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only create tasks for teams you lead'
            ], 403);
        }

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->get('status', 'pending'),
            'priority' => $request->get('priority', 'medium'),
            'team_id' => $team->id,
            'assigned_to' => $assignedUser->id,
            'due_date' => $request->due_date,
            'created_by' => $user->id,
        ]);

        return new TaskResource($task);
    }

    public function show(Task $task, Request $request)
    {
        $user = $request->user();

        if (!$this->canAccessTask($user, $task)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $task->load(['team', 'assignedUser', 'creator']);


        return response()->json([
            'success' => true,
            'message' => 'Task shown below',
            'data' => [
                'task' => new TaskResource($task)
            ]
        ]);
    }


    public function update(Task $task, TaskStoreRequest $request)
    {

        $user = $request->user();

        if (!$this->canModifyTask($user, $task)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }
        if ($request->has('assigned_to') && $request->assigned_to) {
            $assignedUser = $task->team->members()->where('user_id', $request->assigned_to)->first();
            if (!$assignedUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assigned user must be a member of the team'
                ], 400);
            }
        }


        $updateData = $request->only(['title', 'description', 'priority', 'status', 'due_date']);
        if ($request->status === 'completed' && $task->status !== 'completed') {
            $updateData['completed_at'] = now();
        } elseif ($request->status !== 'completed') {
            $updateData['completed_at'] = null;
        }

        $task->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Task Updated Successfully',
            'data' => [
                'task' => new TaskResource($task)
            ]
        ]);
    }

    public function destroy(Task $task, Request $request)
    {
        $user = $request->user();

        if (!$this->canModifyTask($user, $task)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully'
        ]);
    }


    public function updateStatus(UpdateStatusRequest $request, Task $task)
    {
        $user = $request->user();


        if (!$this->canAccessTask($user, $task)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }


        $updateData = ['status' => $request->status];
        if ($request->status === 'completed') {
            $updateData['completed_at'] = now();
        } else {
            $updateData['completed_at'] = null;
        }

        $task->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully',
            'data' => $task
        ]);
    }


    private function canAccessTask($user, $task)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($task->team->team_lead_id === $user->id) {
            return true;
        }

        if ($task->assigned_to === $user->id) {
            return true;
        }

        return $task->team->members()->where('user_id', $user->id)->exists();
    }

    private function canModifyTask($user, $task)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($task->team->team_lead_id === $user->id) {
            return true;
        }

        return false;
    }
}
