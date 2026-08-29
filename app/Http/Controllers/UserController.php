<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of registered users/operatives.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('q');
        $roleFilter = $request->query('role');

        $query = User::withCount(['domains', 'orders'])->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('invite_key', 'like', '%' . $search . '%');
            });
        }

        if ($roleFilter !== null && $roleFilter !== '') {
            $query->where('role', (int) $roleFilter);
        }

        $users = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => User::count(),
            'admins' => User::where('role', User::ROLE_ADMIN)->count(),
            'members' => User::where('role', User::ROLE_MEMBER)->count(),
            'with_domains' => User::has('domains')->count(),
        ];

        return view('admin.user.index', compact('user', 'users', 'stats', 'search', 'roleFilter'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $user = Auth::user();
        return view('admin.user.create', compact('user'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'integer', 'in:1,2'],
            'invite_key' => ['nullable', 'string', 'max:64'],
        ], [
            'name.required' => 'User handle/name is mandatory.',
            'email.required' => 'Valid email address is mandatory.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must contain at least 8 characters.',
        ]);

        $newUser = User::create([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role' => (int) $validated['role'],
            'invite_key' => !empty($validated['invite_key']) ? strtoupper(trim($validated['invite_key'])) : 'ADMIN-GENESIS',
        ]);

        return redirect()->route('user.index')
            ->with('status', "User '{$newUser->name}' created successfully with clearance role!");
    }

    /**
     * Display the specified user details.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $targetUser = User::with(['domains', 'orders.product'])->withCount(['domains', 'orders'])->findOrFail($id);

        return view('admin.user.show', compact('user', 'targetUser'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(string $id)
    {
        $user = Auth::user();
        $targetUser = User::findOrFail($id);

        return view('admin.user.edit', compact('user', 'targetUser'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, string $id)
    {
        $targetUser = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', 'integer', 'in:1,2'],
            'invite_key' => ['nullable', 'string', 'max:64'],
        ], [
            'name.required' => 'User handle/name is mandatory.',
            'email.required' => 'Valid email address is mandatory.',
            'email.unique' => 'This email is already registered by another operative.',
        ]);

        $updateData = [
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'role' => (int) $validated['role'],
            'invite_key' => !empty($validated['invite_key']) ? strtoupper(trim($validated['invite_key'])) : $targetUser->invite_key,
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $targetUser->update($updateData);

        return redirect()->route('user.index')
            ->with('status', "User '{$targetUser->name}' profile updated successfully!");
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(string $id)
    {
        if ((int) Auth::id() === (int) $id) {
            return redirect()->route('user.index')
                ->with('error', 'Cannot delete your own active administrator account.');
        }

        $targetUser = User::findOrFail($id);
        $name = $targetUser->name;
        $targetUser->delete();

        return redirect()->route('user.index')
            ->with('status', "User '{$name}' terminated and removed from mainframe.");
    }
}
