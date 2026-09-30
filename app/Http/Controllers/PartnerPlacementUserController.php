<?php

namespace App\Http\Controllers;

use App\Models\PartnerPlacementUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerPlacementUserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $placementUsers = PartnerPlacementUser::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('partner_placement_name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('pp_code', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.partner-placement-users.index', [
            'placementUsers' => $placementUsers,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.partner-placement-users.form', [
            'placementUser' => new PartnerPlacementUser,
            'formAction' => route('admin.partner-placement-users.store'),
            'formMethod' => 'POST',
            'pageTitle' => 'Add placement user',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $placementUser = PartnerPlacementUser::create($this->validatedData($request));

        return redirect()->route('admin.partner-placement-users.index')->with('status', "Placement user {$placementUser->name} created.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PartnerPlacementUser $partnerPlacementUser): View
    {
        return view('admin.partner-placement-users.form', [
            'placementUser' => $partnerPlacementUser,
            'formAction' => route('admin.partner-placement-users.update', $partnerPlacementUser),
            'formMethod' => 'PUT',
            'pageTitle' => 'Edit placement user',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PartnerPlacementUser $partnerPlacementUser): RedirectResponse
    {
        $partnerPlacementUser->update($this->validatedData($request, $partnerPlacementUser));

        return redirect()->route('admin.partner-placement-users.index')->with('status', "Placement user {$partnerPlacementUser->name} updated.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PartnerPlacementUser $partnerPlacementUser): RedirectResponse
    {
        $userName = $partnerPlacementUser->name;
        $partnerPlacementUser->delete();

        return redirect()->route('admin.partner-placement-users.index')->with('status', "Placement user {$userName} deleted.");
    }

    /**
     * @return array{name: string, email: string, partner_placement_name: string, contact_number: ?string, pp_code: string, status: string, password?: string}
     */
    private function validatedData(Request $request, ?PartnerPlacementUser $placementUser = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('partner_placement_users', 'email')->ignore($placementUser),
            ],
            'partner_placement_name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'pp_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('partner_placement_users', 'pp_code')->ignore($placementUser),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => $placementUser === null
                ? ['required', 'string', 'min:12', 'confirmed']
                : ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        if ($placementUser !== null && blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }
}
