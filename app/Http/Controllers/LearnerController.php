<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Learner;
use App\Models\Partner;
use App\Models\PartnerCenter;
use App\Models\FieldAgent;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\View;


class LearnerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
  public function index(Request $request): \Illuminate\View\View
{
    $filters = $request->validate([
        'search' => ['nullable', 'string', 'max:100'],
        'status' => ['nullable', Rule::in(['active', 'inactive'])],
        'partner_id' => ['nullable', 'integer', 'exists:partners,id'],
        'course_completed' => ['nullable', Rule::in(['0', '1'])],
    ]);

    $learners = Learner::query()
        ->with([
            'partner:id,name,partner_code',
            'partnerCenter:id,name,partner_code',
            'fieldAgent:id,name,sakhi_id',
        ])

        ->when($filters['search'] ?? null, function ($query, string $search): void {
            $query->where(function ($query) use ($search): void {

                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhere('normalized_mobile', 'like', "%{$search}%")
                    ->orWhere('program_code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")

                    ->orWhereHas('partner', fn ($relation) =>
                        $relation->where('name', 'like', "%{$search}%"))

                    ->orWhereHas('partnerCenter', fn ($relation) =>
                        $relation->where('name', 'like', "%{$search}%"))

                    ->orWhereHas('fieldAgent', fn ($relation) =>
                        $relation->where('name', 'like', "%{$search}%")
                            ->orWhere('sakhi_id', 'like', "%{$search}%"));
            });
        })

        ->when(
            $filters['partner_id'] ?? null,
            fn ($query, int $partnerId) =>
            $query->where('partner_id', $partnerId)
        )

        ->when(
            $filters['status'] ?? null,
            fn ($query, string $status) =>
            $query->where('status', $status)
        )

        ->when(
            array_key_exists('course_completed', $filters)
                && $filters['course_completed'] !== null,
            fn ($query) =>
            $query->where('course_completed', (bool) $filters['course_completed'])
        )

        ->orderBy('first_name')
        ->paginate(10)
        ->withQueryString();

    return view('admin.learners.index', [
        'learners' => $learners,
        'filters' => $filters,
        'partners' => Partner::orderBy('name')
            ->get(['id', 'name', 'partner_code']),
    ]);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $learner = new Learner();

        return view('admin.learners.create', [
            'pageTitle'  => 'Add New Learner',
            'formAction' => route('admin.learners.store'),
            'formMethod' => 'POST',
            'learner'=>$learner,
            'partners' => Partner::orderBy('name')->get(),
            'fieldAgents' => FieldAgent::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        $data['normalized_mobile'] = $this->normalizeMobile(
            $data['phone_number'] ?? null
        );

        Learner::create($data);

        return redirect()
            ->route('admin.learners.index')
            ->with('status', "Learner created successfully.");
    }

    /**
     * Show the form for editing the resource.
     */
    public function edit(Learner $learner)
    {
        return view('admin.learners.edit', [
        'pageTitle'  => 'Edit Learner',
        'formAction' => route('admin.learners.update', $learner),
        'formMethod' => 'PUT',
        'learner'    => $learner,

        'partners'   => Partner::orderBy('name')->get(),
        'fieldAgents' => FieldAgent::orderBy('name')->get(),
    ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, Learner $learner): RedirectResponse
    {
        $data = $this->validatedData($request, $learner);

        $data['normalized_mobile'] = $this->normalizeMobile(
            $data['phone_number'] ?? null
        );

        $learner->update($data);

        return redirect()
            ->route('admin.learners.index')
            ->with('status', "Learner updated successfully.");
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(Learner $learner): RedirectResponse
    {
        $learner->delete();

        return redirect()
            ->route('admin.learners.index')
            ->with('status', "Learner deleted successfully.");
    }

    /**
     * Validation.
     */
    private function validatedData(Request $request, ?Learner $learner = null): array
    {
        return $request->validate([


            'program_code' => [
                'required',
                'string',
                'max:100',
            ],

            'phone_number' => [
                'required',
                'string',
                'max:20',
            ],

            'first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'last_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'gender' => [
                'nullable',
                Rule::in(['male', 'female', 'other']),
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],
            'status' => ['required', 'in:active,inactive'],



        ]);
    }

    /**
     * Normalize mobile.
     */
    private function normalizeMobile(?string $mobile): ?string
    {
        if (blank($mobile)) {
            return null;
        }

        $mobile = preg_replace('/\D+/', '', $mobile);

        if (strlen($mobile) > 10) {
            $mobile = substr($mobile, -10);
        }

        return strlen($mobile) === 10
            ? $mobile
            : null;
    }
}
