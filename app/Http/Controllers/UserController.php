<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use ApiResponse;
    /**
     * Get all users with pagination
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Pagination or all
        if ($request->has('all') && $request->all == 'true') {
            $users = $query->orderBy('name')->get();
            return $this->successResponse($users, 'Data user berhasil diambil');
        }

        $perPage = $request->per_page ?? 15;
        $users = $query->orderBy('name')->paginate($perPage);

        return $this->paginatedResponse($users, 'Data user berhasil diambil');
    }

    /**
     * Get single user
     */
    public function show(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->notFoundResponse("User dengan ID {$id} tidak ditemukan");
        }

        return $this->successResponse($user, 'Detail user berhasil diambil');
    }

    /**
     * Create new user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return $this->successResponse($user, 'User berhasil ditambahkan', 201);
    }

    /**
     * Update user
     */
    public function update(Request $request, int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->notFoundResponse("User dengan ID {$id} tidak ditemukan");
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'sometimes|required|string|min:6',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return $this->successResponse($user, 'User berhasil diperbarui');
    }

    /**
     * Delete user
     */
    public function destroy(Request $request, int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->notFoundResponse("User dengan ID {$id} tidak ditemukan");
        }

        // Prevent deleting self
        if ($request->user()->id === $user->id) {
            return $this->errorResponse('Tidak dapat menghapus akun sendiri', 422);
        }

        $user->delete();

        return $this->successResponse(null, 'User berhasil dihapus');
    }
}
