<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;


class UserController extends Controller
{
    
    public function index(Request $request)
    {
        $validated = $request->validate([
            'sort_key'  => 'nullable|string',
            'sort_type' => 'required_with:sort_key|in:asc,desc',
            'name'      => 'nullable|string',
            'phone'     => 'nullable|string',
            'email'     => 'nullable|string',
            'role'      => 'nullable|string|in:user,admin',
            'page'      => 'nullable|integer|min:1',
            'per_page'  => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = $validated['per_page'] ?? 20;
        $query   = User::with(['region', 'city']);

        if (!empty($validated['name'])) {
            $query->where('name', 'like', '%' . $validated['name'] . '%');
        }
        if (!empty($validated['phone'])) {
            $query->where('phone', 'like', '%' . $validated['phone'] . '%');
        }
        if (!empty($validated['email'])) {
            $query->where('email', 'like', '%' . $validated['email'] . '%');
        }
        if (!empty($validated['role'])) {
            $query->where('role', $validated['role']);
        }

        $sortKey   = $validated['sort_key'] ?? 'id';
        $sortType  = $validated['sort_type'] ?? 'desc';
        $query->orderBy($sortKey, $sortType);

        $lists = $query->paginate($perPage);

        if ($lists->isEmpty()) {
            return response()->errorJson('Object not found', 404);
        }

        return response()->successJson($lists);
    }

    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'phone'     => 'required|string|max:20|unique:users,phone',
            'password'  => 'required|string|min:6',
            'name'      => 'required|string|max:255',
            'email'     => 'nullable|email|unique:users,email',
            'telegram'  => 'nullable|string|max:80',
            'region_id' => 'nullable|integer|exists:regions,id',
            'city_id'   => 'nullable|integer|exists:cities,id',
            'role'      => 'nullable|in:user,admin',
        ]);

        $validated['role'] = $validated['role'] ?? User::ROLE_USER;
        $user = User::create($validated);

        return response()->successJson($user);
    }

    
    public function show(string $id)
    {
        $user = User::with(['region', 'city'])->find((int) $id);

        if (!$user) {
            return response()->errorJson('Object not found', 404);
        }

        return response()->successJson($user);
    }

    
    public function update(Request $request, string $id)
    {
        $userId = (int) $id;
        $user   = User::find($userId);

        if (!$user) {
            return response()->errorJson('Object not found', 404);
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'phone'     => 'required|string|max:20|unique:users,phone,' . $userId,
            'email'     => 'nullable|email|unique:users,email,' . $userId,
            'password'  => 'nullable|string|min:6',
            'telegram'  => 'nullable|string|max:80',
            'region_id' => 'required|integer|exists:regions,id',
            'city_id'   => 'required|integer|exists:cities,id',
            'role'      => 'required|in:user,admin',
            'avatar'    => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        unset($validated['avatar']);

        $user->update($validated);

        if ($request->hasFile('avatar')) {
            $user->clearMediaCollection('avatar');
            $user->addMedia($request->file('avatar'))->toMediaCollection('avatar');
        }

        return response()->successJson($user->fresh());
    }

    
    public function destroy(string $id)
    {
        $user = User::find((int) $id);

        if (!$user) {
            return response()->errorJson('Object not found', 404);
        }

        $user->delete();

        return response()->successJson(['message' => 'User deleted']);
    }
}
