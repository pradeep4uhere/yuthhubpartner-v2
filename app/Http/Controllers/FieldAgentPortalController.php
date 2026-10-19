<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\FieldAgent;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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

   /**
     * Display the authenticated Field Agent dashboard.
     *
     * Logs out inactive agents and requires MPIN verification for agents
     * who have enabled MPIN login. Dashboard data is loaded only after
     * the required verification has been completed.
     *
     * @param Request $request The current HTTP request.
     * @return View|RedirectResponse The dashboard view or a redirect.
     */
    public function dashboard(Request $request): View|RedirectResponse
    {
        $fieldAgent = $request->user('field_agent');

        if (! $fieldAgent) {
            return redirect()->route('field-agent.login');
        }

        if ($fieldAgent->status !== 'active') {
            return $this->logout($request);
        }

        if ($fieldAgent->mpin_login_enabled) {
            if (! $request->session()->get('field_agent_mpin_verified', false)) {
                $request->session()->put('field_agent_mpin_pending', true);

                return redirect()->route('field-agent.mpin.verify');
            }
        } else {
            // Clear stale MPIN verification state if MPIN login is disabled.
            $request->session()->forget([
                'field_agent_mpin_pending',
                'field_agent_mpin_verified',
            ]);
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
        $request->session()->forget([
            'field_agent_mpin_pending',
            'field_agent_mpin_verified',
        ]);

        return redirect()->route('field-agent.login');
    }





        /**
         * Authenticate a Field Agent using their Sakhi ID and MPIN.
         *
         * MPIN login is permitted only when the account is active, MPIN login
         * is enabled, and the submitted MPIN matches the stored hash. Attempts
         * are rate-limited by account identifier and client IP.
         *
         * @param Request $request The incoming login request.
         * @return RedirectResponse Redirects to the dashboard or back with errors.
         */
        public function mpinLogin(Request $request): RedirectResponse
        {
            $validated = $request->validate([
                'sakhi_id' => ['required', 'string', 'max:255'],
                'mpin' => ['required', 'digits:6'],
            ]);

            $sakhiId = trim($validated['sakhi_id']);

            $throttleKey = Str::lower($sakhiId).'|'.$request->ip();

            if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
                return back()
                    ->withErrors([
                        'mpin' => 'Too many login attempts. Please try again later.',
                    ])
                    ->onlyInput('sakhi_id');
            }

            $fieldAgent = FieldAgent::query()
                ->where('sakhi_id', $sakhiId)
                ->where('status', 'active')
                ->first();

            if (
                !$fieldAgent
                || !$fieldAgent->mpin_login_enabled
                || empty($fieldAgent->mpin_hash)
                || !Hash::check($validated['mpin'], $fieldAgent->mpin_hash)
            ) {
                RateLimiter::hit($throttleKey, 60);

                return back()
                    ->withErrors([
                        'mpin' => 'Invalid credentials or MPIN login is not enabled.',
                    ])
                    ->onlyInput('sakhi_id');
            }

            RateLimiter::clear($throttleKey);

            Auth::guard('field_agent')->login($fieldAgent);

            $request->session()->regenerate();

            return redirect()->intended(route('field-agent.dashboard'));
        }



/**
 * Display the second-step MPIN verification screen.
 *
 * Only authenticated, active Field Agents who have enabled MPIN login
 * and are awaiting verification may access this screen.
 *
 * @param Request $request The current HTTP request.
 * @return View|RedirectResponse The verification screen or a redirect.
 */
public function showMpinVerification(Request $request): View|RedirectResponse
{
    $fieldAgent = $request->user('field_agent');

    if (! $fieldAgent || $fieldAgent->status !== 'active') {
        return $this->logout($request);
    }

    if (! $fieldAgent->mpin_login_enabled) {
        $request->session()->forget([
            'field_agent_mpin_pending',
            'field_agent_mpin_verified',
        ]);

        return redirect()->route('field-agent.dashboard');
    }

    if ($request->session()->get('field_agent_mpin_verified', false)) {
        return redirect()->route('field-agent.dashboard');
    }

    if (! $request->session()->get('field_agent_mpin_pending', false)) {
        return redirect()->route('field-agent.login');
    }

    return view('portals.field-agent.mpin-verification', [
        'fieldAgent' => $fieldAgent,
    ]);
}

    /**
     * Verify the MPIN for an already authenticated Field Agent.
     *
     * Checks the six-digit MPIN against the stored hash and marks the
     * current session as verified only when the account is active, MPIN
     * login is enabled, and verification is pending.
     *
     * @param Request $request The incoming verification request.
     * @return RedirectResponse Redirects to the dashboard or back with errors.
     */
    public function verifyMpin(Request $request): RedirectResponse
    {
        $fieldAgent = $request->user('field_agent');

        if (! $fieldAgent || $fieldAgent->status !== 'active') {
            return $this->logout($request);
        }

        if (! $fieldAgent->mpin_login_enabled) {
            $request->session()->forget([
                'field_agent_mpin_pending',
                'field_agent_mpin_verified',
            ]);

            return redirect()->route('field-agent.dashboard');
        }

        if (
            ! $request->session()->get('field_agent_mpin_pending', false)
            || $request->session()->get('field_agent_mpin_verified', false)
        ) {
            return redirect()->route('field-agent.dashboard');
        }

        $validated = $request->validate([
            'mpin' => ['required', 'digits:6'],
        ]);

        if (
            empty($fieldAgent->mpin_hash)
            || ! \Illuminate\Support\Facades\Hash::check(
                $validated['mpin'],
                $fieldAgent->mpin_hash
            )
        ) {
            return back()->withErrors([
                'mpin' => 'The MPIN entered is incorrect. Please try again.',
            ]);
        }

        $request->session()->regenerate();

        $request->session()->put('field_agent_mpin_verified', true);
        $request->session()->forget('field_agent_mpin_pending');

        return redirect()->intended(route('field-agent.dashboard'));
    }





}
