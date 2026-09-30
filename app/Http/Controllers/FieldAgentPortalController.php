<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FieldAgentPortalController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if (Auth::guard('field_agent')->check()) {
            return redirect()->route('field-agent.dashboard');
        }

        return view('portals.field_agent_login', [
            'portalName' => 'Field Agent',
            'portalDescription' => 'Sign in to access your field workspace.',
            'loginAction' => route('field-agent.login.store'),
        ]);
    }

    // public function store(Request $request): RedirectResponse
    // {
    //     $credentials = $request->validate([
    //         'email' => ['required', 'email'],
    //         'password' => ['required', 'string'],
    //     ]);

    //     if (Auth::guard('field_agent')->attempt([...$credentials, 'status' => 'active'])) {
    //         $request->session()->regenerate();

    //         return redirect()->intended(route('field-agent.dashboard'));
    //     }

    //     return back()
    //         ->withErrors(['email' => 'These credentials do not match an active field agent account.'])
    //         ->onlyInput('email');
    // }



    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'sakhi_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('field_agent')->attempt([
            'sakhi_id' => $credentials['sakhi_id'],
            'password' => $credentials['password'],
            'status' => 'active',
        ])) {
            $request->session()->regenerate();

            return redirect()->intended(route('field-agent.dashboard'));
        }

        return back()
            ->withErrors([
                'sakhi_id' => 'These credentials do not match an active field agent account.',
            ])
            ->onlyInput('sakhi_id');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        $fieldAgent = $request->user('field_agent');

        if ($fieldAgent->status !== 'active') {
            return $this->logout($request);
        }

        return view('portals.field-agent.dashboard', [
            'fieldAgent' => $fieldAgent->load([
                'partner:id,name,partner_code',
                'partnerCenter:id,partner_id,name,partner_code',
                'placementPartnerUser:id,partner_placement_name,pp_code',
            ]),
        ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        return $this->logout($request);
    }

    private function logout(Request $request): RedirectResponse
    {
        Auth::guard('field_agent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('field-agent.login');
    }
}
