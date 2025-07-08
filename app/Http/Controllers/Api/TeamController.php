<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddMemberRequest;
use App\Http\Requests\TeamStoreRequest;
use App\Http\Requests\TeamUpdateRequest;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeamController extends Controller
{


    public function index(Request $request)
    {
        $user = $request->user();
        $query = Team::with(['teamLead', 'members', 'tasks']);

        if ($user->isAdmin()) {
            $teams = $query->get();
        } elseif ($user->isTeamLead()) {
            $teams = $query->where('team_lead_id', $user->id)->get();
        } else {
            $teams = $query->whereHas('members', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->get();
        }

        return response()->json([
            'success' => true,
            'data' => $teams->map(function ($team) {
                return [
                    'id' => $team->id,
                    'uuid' => $team->uuid,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'description' => $team->description,
                    'team_lead_id' => $team->team_lead_id,
                    'created_by' => $team->created_by,
                    'is_active' => $team->is_active,
                    'created_at' => $team->created_at,
                    'updated_at' => $team->updated_at,
                    'team_lead' => $team->teamLead,
                    'members' => $team->members->map(function ($member) use ($team) {
                        return [
                            'id' => $member->id,
                            'team_uuid' => $team->uuid,
                            'user_id' => (int) $member->user_id,
                            'user' => $member->user,
                            'joined_at' => $member->joined_at,
                            'created_at' => $member->created_at,
                            'updated_at' => $member->updated_at,
                        ];
                    }),
                    'tasks' => $team->tasks,
                ];
            })
        ]);
    }


    public function store(TeamStoreRequest $request)
    {

        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can create teams'
            ], 403);
        }

        DB::beginTransaction();
        try {

            $leadUser = User::where('uuid', $request->team_lead_id)->first();
            $team = Team::create([
                'name' => $request->name,
                'description' => $request->description,
                'team_lead_id' => $leadUser->id,
                'created_by' => $user->id
            ]);


            $team->members()->create([
                'user_id' => $leadUser->id,
                'joined_at' => now()
            ]);


            if ($request->has('member_ids')) {
                $memberIds = array_diff($request->member_ids, [$request->team_lead_id]);

                foreach ($memberIds as $memberId) {
                    $member = User::where('uuid', $memberId)->first();
                    $team->members()->create([
                        'user_id' => $member->id,
                        'joined_at' => now()
                    ]);
                }
            }

            DB::commit();

            $team->load(['teamLead', 'members', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Team created successfully',
                'data' => [
                    'uuid' => $team->uuid,
                    'name' => $team->name,
                    'description' => $team->description,
                    'team_lead' => $team->teamLead,
                    'created_by' => $team->creator,
                    'slug' => $team->slug,
                    'created_at' => $team->created_at,
                    'updated_at' => $team->updated_at,
                    'members' => $team->members->map(function ($member) {
                        return [
                            'id' => $member->id,
                            'team_uuid' => $member->team->uuid,
                            'user_id' => (int) $member->user_id,
                            'user' => $member->user,
                            'joined_at' => $member->joined_at,
                            'created_at' => $member->created_at,
                            'updated_at' => $member->updated_at,
                        ];
                    })
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create team'
            ], 500);
        }
    }

    public function show(Request $request, Team $team)
    {
        $user = $request->user();

        if (!$this->canAccessTeam($user, $team)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $team->load(['teamLead', 'members', 'tasks.assignedUser', 'creator']);

        
        return response()->json([
            'success' => true,
            'message' => 'Team created successfully',
            'data' => [
                'uuid' => $team->uuid,
                'name' => $team->name,
                'description' => $team->description,
                'team_lead' => $team->teamLead,
                'created_by' => $team->creator,
                'slug' => $team->slug,
                'created_at' => $team->created_at,
                'updated_at' => $team->updated_at,
                'members' => $team->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'team_uuid' => $member->team->uuid,
                        'user_id' => (int) $member->user_id,
                        'user' => $member->user,
                        'joined_at' => $member->joined_at,
                        'created_at' => $member->created_at,
                        'updated_at' => $member->updated_at,
                    ];
                })
            ]
        ], 201);
    }

    public function update(TeamUpdateRequest $request, Team $team)
    {
        $user = $request->user();

        if (!$user->isAdmin() && $team->team_lead_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }


        $team->update($request->only(['name', 'description', 'team_lead_id']));

        return response()->json([
            'success' => true,
            'message' => 'Team Updated successfully',
            'data' => [
                'uuid' => $team->uuid,
                'name' => $team->name,
                'description' => $team->description,
                'team_lead' => $team->teamLead,
                'created_by' => $team->creator,
                'slug' => $team->slug,
                'created_at' => $team->created_at,
                'updated_at' => $team->updated_at,
                'members' => $team->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'team_uuid' => $member->team->uuid,
                        'user_id' => (int) $member->user_id,
                        'user' => $member->user,
                        'joined_at' => $member->joined_at,
                        'created_at' => $member->created_at,
                        'updated_at' => $member->updated_at,
                    ];
                })
            ]
        ], 201);
    }

    public function destroy(Request $request, Team $team)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Only admins can delete teams'
            ], 403);
        }

        $team->delete();

        return response()->json([
            'success' => true,
            'message' => 'Team deleted successfully'
        ]);
    }

    public function addMember(AddMemberRequest $request, Team $team)
    {
        $user = $request->user();

        if (!$user->isAdmin() && $team->team_lead_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $member = User::where('uuid', $request->user_id)->first();

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        if ($team->members()->where('user_id', $member->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User is already a member of this team'
            ], 400);
        }

        $team->members()->create([
            'user_id' => $member->id,
            'joined_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Member added successfully'
        ]);
    }

    public function removeMember(Request $request, Team $team, User $user)
    {
        $currentUser = $request->user();

        if (!$currentUser->isAdmin() && $team->team_lead_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        if ($team->team_lead_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot remove team lead from team'
            ], 400);
        }

        $team->members()->delete($user->id);

        return response()->json([
            'success' => true,
            'message' => 'Member removed successfully'
        ]);
    }


    private function canAccessTeam($user, $team)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($team->team_lead_id === $user->id) {
            return true;
        }

        return $team->members()->where('user_id', $user->id)->exists();
    }
}
