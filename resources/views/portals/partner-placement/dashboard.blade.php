
@extends('layouts.app')

@section('content')

@php
    $placementPartnerData = [
        'id' => $placementUser->id,
        'name' => $placementUser->name,
        'email' => $placementUser->email,
        'phone' => $placementUser->contact_number,
        'placementCode' => $placementUser->pp_code,
        'organisation' => $placementUser->partner_placement_name,
        'status' => $placementUser->status,
    ];
@endphp

<div
    data-react-app="placement-partner-dashboard"
    data-placement-partner-name="{{ $placementUser->name }}"
    data-placement-partner="{{ json_encode($placementPartnerData) }}"
    data-logout-url="{{ route('partner-placement.logout') }}"
    data-placement-user="{{ json_encode(auth('partner_placement')->user()) }}"
></div>

<script>
    window.placementPartnerDashboardData =
        {{ Illuminate\Support\Js::from($placementPartnerData) }};
</script>

@endsection
