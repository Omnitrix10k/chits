<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()->latest()->paginate(15),
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => User::ROLES,
        ]);
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'mobile_number' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/', Rule::unique(User::class, 'mobile_number')],
            'role' => ['required', Rule::in(User::ROLES)],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        User::query()->create([
            'name' => $validated['name'],
            'email' => Str::lower($validated['email']),
            'mobile_number' => $validated['mobile_number'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'user-created');
    }
}
