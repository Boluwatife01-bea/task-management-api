<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskStoreRequest;
use App\Http\Resources\TaskCollection;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {

        $query = Task::where('user_id', $request->user()->id);
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->has('overdue') && $request->boolean('overdue')) {
            $query->overdue();
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->get('per_page', 15);
        $tasks = $query->paginate($perPage);

        return new TaskCollection($tasks);
    }


    public function store(TaskStoreRequest $request)
    {

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->get('status', 'pending'),
            'priority' => $request->get('priority', 'medium'),
            'due_date' => $request->due_date,
            'user_id' => $request->user()->id,
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
