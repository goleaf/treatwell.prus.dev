<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\HandlesApiErrors;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreUserRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use AuthorizesRequests, HandlesApiErrors;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', User::class);
        $query = User::query();

        // Search
        if ($request->has('search') && $request->input('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        }

        // Filter by email verification status
        if ($request->has('verified') && $request->input('verified') !== null) {
            $verified = filter_var($request->input('verified'), FILTER_VALIDATE_BOOLEAN);
            if ($verified) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Sorting
        $sort = $request->input('sort', 'name');
        switch ($sort) {
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'email':
                $query->orderBy('email', 'asc');
                break;
            case 'email_desc':
                $query->orderBy('email', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            default:
                $query->orderBy('name', 'asc');
        }

        $perPage = $request->input('per_page', 15);
        $users = $query->paginate($perPage);

        return new UserCollection($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $this->authorize('create', User::class);

        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = $this->executeInTransaction(function () use ($data) {
            $user = User::create($data);
            $this->logApiOperation('create', $user, $data);

            return $user;
        });

        if ($user instanceof JsonResponse) {
            return $user;
        }

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        // Check for concurrent modifications
        $concurrencyError = $this->checkConcurrentModification($user, $request->header('If-Unmodified-Since'));
        if ($concurrencyError) {
            return $concurrencyError;
        }

        // Only hash password if it's provided
        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $updatedUser = $this->executeInTransaction(function () use ($user, $data) {
            $user->update($data);
            $this->logApiOperation('update', $user, $data);

            return $user;
        });

        if ($updatedUser instanceof JsonResponse) {
            return $updatedUser;
        }

        return new UserResource($updatedUser);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);

        // Validate that the user can be deleted
        $deletionError = $this->validateDeletion($user);
        if ($deletionError) {
            return $deletionError;
        }

        $result = $this->executeInTransaction(function () use ($user) {
            $this->logApiOperation('delete', $user);
            $user->delete();

            return true;
        });

        if ($result instanceof JsonResponse) {
            return $result;
        }

        return response()->noContent();
    }

    /**
     * Get critical relationships that prevent deletion.
     */
    protected function getCriticalRelationships(Model $model): array
    {
        // Users typically don't have critical relationships that prevent deletion
        // but you might want to add relationships like 'venues', 'reviews', etc.
        return [];
    }

    /**
     * Get user profile information.
     */
    public function profile(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Get users with admin privileges.
     */
    public function admins()
    {
        $users = User::all()->filter(function ($user) {
            return $user->isAdmin();
        });

        return new UserCollection($users);
    }
}
