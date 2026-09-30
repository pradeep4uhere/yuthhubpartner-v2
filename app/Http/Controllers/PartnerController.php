<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartnerController extends Controller
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

        $partners = Partner::query()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('partner_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.partners.index', [
            'partners' => $partners,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.partners.form', [
            'partner' => new Partner,
            'formAction' => route('admin.partners.store'),
            'formMethod' => 'POST',
            'pageTitle' => 'Add partner',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $partner = Partner::create($this->validatedData($request));

        return redirect()->route('admin.partners.index')->with('status', "Partner {$partner->name} created.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Partner $partner): View
    {
        return view('admin.partners.form', [
            'partner' => $partner,
            'formAction' => route('admin.partners.update', $partner),
            'formMethod' => 'PUT',
            'pageTitle' => 'Edit partner',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $partner->update($this->validatedData($request, $partner));

        return redirect()->route('admin.partners.index')->with('status', "Partner {$partner->name} updated.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partner $partner): RedirectResponse
    {
        if ($partner->centers()->exists()) {
            return back()->withErrors([
                'partner' => 'This partner cannot be deleted while partner centers are assigned to it.',
            ]);
        }
        $partnerName = $partner->name;
        $partner->delete();

        return redirect()->route('admin.partners.index')->with('status', "Partner {$partnerName} deleted.");
    }

    /**
     * @return array{partner_code: string, name: string, contact_number: ?string, email: ?string, status: string}
     */
    private function validatedData(Request $request, ?Partner $partner = null): array
    {
        $validated = $request->validate([
            'partner_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('partners', 'partner_code')->ignore($partner),
            ],
            'name' => ['required', 'string', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:30'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('partners', 'email')->ignore($partner),
            ],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => $partner === null
                ? ['required', 'string', 'min:12', 'confirmed']
                : ['nullable', 'string', 'min:12', 'confirmed'],
        ]);

        if ($partner !== null && blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        return $validated;
    }
}
