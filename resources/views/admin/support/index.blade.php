
@extends('layouts.app')

@section('content')
    <div
        data-react-app="admin-support"
        data-admin-name="{{ auth('admin')->user()->name }}"
        data-summary-url="{{ url('/api/admin/support/summary') }}"
        data-tickets-url="{{ url('/api/admin/support/tickets') }}"
        data-categories-url="{{ url('/api/admin/support/categories') }}"
        data-assignees-url="{{ url('/api/admin/support/assignees') }}"
        data-csrf-token="{{ csrf_token() }}"
    ></div>
@endsection
