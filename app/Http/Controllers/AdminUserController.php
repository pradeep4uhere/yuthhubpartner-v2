<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'admins' => Admin::with('roles')->orderBy('name')->get(),
            'roles' => ['Admin', 'Sub Admin'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admins,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(['Admin', 'Sub Admin'])],
        ]);

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => true,
        ]);

        $admin->assignRole($validated['role']);

        return redirect()->route('admin.users.index')->with('status', 'Admin account created.');
    }

    public function updateStatus(Request $request, Admin $admin): RedirectResponse
    {
        if ($request->user('admin')->is($admin)) {
            return back()->withErrors(['admin' => 'You cannot change your own account status.']);
        }

        if ($admin->is_active && $admin->hasRole('Super Admin')) {
            $activeSuperAdmins = Admin::role('Super Admin')->where('is_active', true)->count();

            if ($activeSuperAdmins <= 1) {
                return back()->withErrors(['admin' => 'The last active Super Admin cannot be disabled.']);
            }
        }

        $admin->forceFill(['is_active' => ! $admin->is_active])->save();

        return back()->with('status', $admin->is_active ? 'Admin account activated.' : 'Admin account disabled.');
    }
}
