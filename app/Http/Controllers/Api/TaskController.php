<?php

namespace App\Http\Controllers\Api;

use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TaskCollection;
use App\Http\Requests\TaskStoreRequest;

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

    $team = Team::where('uuid', $request->team_id)->first();
    
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
        'team_id' => $request->team->id, 
        'assigned_to' =>$request->assigned_to,
        'due_date' => $request->due_date,
        'created_by' => $user->id,
    ]);

    return new TaskResource($task);
}



    public function show($uuid, Request $request)
    {
        $task = Task::where('uuid', $uuid)->where('user_id', $request->user()->id)->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        return new TaskResource($task, 'Task is shown below');
    }


    public function update($uuid, TaskStoreRequest $request)
    {
        $task = Task::where('uuid', $uuid)->where('user_id', $request->user()->id)->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        $task->update($request->only([
            'title',
            'description',
            'status',
            'priority',
            'due_date'
        ]));

        return new TaskResource($task, 'Task Updated successfully');
    }

    public function destroy($uuid, Request $request)
    {

        $task = Task::where('uuid', $uuid)->where('user_id', $request->user()->id)->first();

        if (!$task) {
            return response()->json([
                'success' => false,
                'message' => 'Task not found'
            ], 404);
        }

        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully'
        ]);
    }


    public function stats(Request $request)
    {
        $user = $request->user()->id;

        $stats = [
            'total_tasks' => Task::where('user_id', $user)->count(),
            'pending_tasks' => Task::where('user_id', $user)->byStatus('pending')->count(),
            'in_progress_tasks' => Task::where('user_id', $user)->byStatus('in_progress')->count(),
            'completed_tasks' => Task::where('user_id', $user)->byStatus('completed')->count(),
            'overdue_tasks' => Task::where('user_id', $user)->overdue()->count(),
            'high_priority_tasks' => Task::where('user_id', $user)->byPriority('high')->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $stats
            ]
        ]);
    }

    
}
