<?php

namespace App\Http\Controllers;

use App\Models\Invitecode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InviteCodeController extends Controller
{
    /**
     * Display a listing of invite codes with stats and generator modal.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $search = $request->query('q');
        $statusFilter = $request->query('status');

        $query = Invitecode::with('user')->latest();

        if ($search) {
            $query->where('code', 'like', '%' . $search . '%');
        }

        if ($statusFilter === 'active') {
            $query->where('used', false)
                  ->where(function ($q) {
                      $q->whereNull('expired_at')
                        ->orWhere('expired_at', '>', now());
                  });
        } elseif ($statusFilter === 'expired') {
            $query->where('used', false)
                  ->whereNotNull('expired_at')
                  ->where('expired_at', '<=', now());
        } elseif ($statusFilter === 'claimed') {
            $query->where('used', true);
        }

        $inviteCodes = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Invitecode::count(),
            'active' => Invitecode::where('used', false)
                ->where(function ($q) {
                    $q->whereNull('expired_at')
                      ->orWhere('expired_at', '>', now());
                })->count(),
            'used' => Invitecode::where('used', true)->count(),
            'expired' => Invitecode::where('used', false)
                ->whereNotNull('expired_at')
                ->where('expired_at', '<=', now())->count(),
        ];

        $suggestedCode = Invitecode::generateCode();

        return view('admin.invitecode.index', compact('user', 'inviteCodes', 'suggestedCode', 'stats', 'search', 'statusFilter'));
    }

    /**
     * Store a newly created invite code in storage.
     */
    public function store(Request $request)
    {
        $rawCode = (string) ($request->input('code') ?: Invitecode::generateCode());
        $request->merge([
            'code' => strtoupper(trim($rawCode)),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:invitecodes,code'],
            'expires_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date'],
            'generate_via' => ['nullable', 'string', 'max:50'],
        ], [
            'code.required' => 'Invite code string is required.',
            'code.unique' => 'This invite code is already registered in the system.',
        ]);

        $expiryDate = null;
        if (!empty($validated['expires_at'])) {
            $expiryDate = date('Y-m-d H:i:s', strtotime($validated['expires_at']));
        } elseif (!empty($validated['expired_at'])) {
            $expiryDate = date('Y-m-d H:i:s', strtotime($validated['expired_at']));
        }

        $inviteCode = Invitecode::create([
            'code' => $validated['code'],
            'expired_at' => $expiryDate,
            'used' => false,
            'generate_via' => $validated['generate_via'] ?? 'admin',
        ]);

        return redirect()->route('invitecode.index')
            ->with('status', "Invite Code '{$inviteCode->code}' created successfully!");
    }

    /**
     * Update the specified invite code in storage.
     */
    public function update(Request $request, string $id)
    {
        $inviteCode = Invitecode::findOrFail($id);

        $request->merge([
            'code' => strtoupper(trim((string) $request->input('code'))),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:invitecodes,code,' . $id],
            'expires_at' => ['nullable', 'date'],
            'expired_at' => ['nullable', 'date'],
            'used' => ['nullable'],
        ], [
            'code.required' => 'Invite code string is required.',
            'code.unique' => 'This invite code is already taken.',
        ]);

        $expiryDate = null;
        if (!empty($validated['expires_at'])) {
            $expiryDate = date('Y-m-d H:i:s', strtotime($validated['expires_at']));
        } elseif (!empty($validated['expired_at'])) {
            $expiryDate = date('Y-m-d H:i:s', strtotime($validated['expired_at']));
        }

        $inviteCode->update([
            'code' => $validated['code'],
            'expired_at' => $expiryDate,
            'used' => $request->boolean('used', false),
        ]);

        return redirect()->route('invitecode.index')
            ->with('status', "Invite Code '{$inviteCode->code}' updated successfully!");
    }

    /**
     * Remove the specified invite code from storage.
     */
    public function destroy(string $id)
    {
        $inviteCode = Invitecode::findOrFail($id);
        $code = $inviteCode->code;
        $inviteCode->delete();

        return redirect()->route('invitecode.index')
            ->with('status', "Invite Code '{$code}' deleted successfully!");
    }

    /**
     * Generate unique random code for AJAX requests.
     */
    public function generateRandom()
    {
        return response()->json([
            'code' => Invitecode::generateCode(),
        ]);
    }
}
