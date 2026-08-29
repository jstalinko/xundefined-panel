<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Show the cyber login form.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Process authentication request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Identity email is required.',
            'email.email' => 'Please provide a valid operative email format.',
            'password.required' => 'Security passcode is required.',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            // Check if user is banned
            if ($user->isBanned()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors([
                        'email' => 'ACCESS DENIED: Operative account has been permanently terminated (Banned).',
                    ]);
            }

            // Check if user is inactive
            if ($user->isInactive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withInput($request->only('email', 'remember'))
                    ->withErrors([
                        'email' => 'CLEARANCE PENDING: Account awaiting cryptographic activation clearance.',
                    ]);
            }

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('status', "ACCESS GRANTED // Identity verified: {$user->name}");
        }

        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors([
                'email' => 'AUTHENTICATION FAILED // Invalid credentials or passcode mismatch.',
            ]);
    }

    /**
     * Show the cyber registration form.
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Process cyber user registration with invite_key.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'invite_key' => ['required', 'string', 'max:64'],
        ], [
            'name.required' => 'Operative alias / handle is required.',
            'email.required' => 'Cryptographic identity email is required.',
            'email.unique' => 'This identity email is already registered in the mainframe.',
            'password.required' => 'Passcode creation is required.',
            'password.min' => 'Passcode must be at least 8 characters long.',
            'password.confirmed' => 'Passcode confirmation does not match.',
            'invite_key.required' => 'Valid clearance Invite Key is mandatory for mainframe registration.',
            'invite_key.max' => 'Invite key must not exceed 64 characters.',
        ]);

        $inviteCodeStr = strtoupper(trim($validated['invite_key']));
        $dbInvite = \App\Models\Invitecode::where('code', $inviteCodeStr)->first();
        if (!$dbInvite || !$dbInvite->isValid()) {
            return back()->withErrors([
                'invite_key' => 'Invalid, expired, or already claimed Invite Code. Please obtain a valid clearance key.'
            ])->withInput();
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_MEMBER,
            'invite_key' => $inviteCodeStr,
        ]);

        $dbInvite->markAsUsed($user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('status', "INITIALIZATION COMPLETE // Clearance granted. Welcome to Mainframe, {$user->name}");
    }

    /**
     * AJAX endpoint to check if email already exists in system.
     */
    public function checkEmail(Request $request)
    {
        $email = trim((string) $request->query('email'));
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['valid' => false, 'exists' => false, 'message' => 'Please enter a valid email format.']);
        }

        $exists = User::where('email', $email)->exists();

        return response()->json([
            'valid' => true,
            'exists' => $exists,
            'message' => $exists ? 'This identity email is already registered.' : 'Email is available.',
        ]);
    }

    /**
     * AJAX endpoint to check invite code validity.
     */
    public function checkInvite(Request $request)
    {
        $code = strtoupper(trim((string) $request->query('code')));
        if (!$code) {
            return response()->json(['valid' => false, 'message' => 'Invite code required.']);
        }

        $invite = \App\Models\Invitecode::where('code', $code)->first();
        if (!$invite) {
            return response()->json(['valid' => false, 'message' => 'Invalid or unrecognized Invite Code.']);
        }

        if (!$invite->isValid()) {
            return response()->json(['valid' => false, 'message' => 'Invite code is expired or already claimed.']);
        }

        return response()->json(['valid' => true, 'message' => 'Invite code verified and valid.']);
    }

    /**
     * Terminate operative session.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('status', 'SESSION TERMINATED // Neural link disconnected successfully.');
    }
}
