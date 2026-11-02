@extends('layouts.app')

@section('content')

    <div
        id="admin-learner-sync-root"
        data-react-app="admin-learner-sync"
        data-admin-name="{{ auth('admin')->user()->name }}"
        data-csrf-token="{{ csrf_token() }}"
        data-dashboard-url="{{ route('admin.learner-sync.data') }}"
        data-run-url="{{ route('admin.learner-sync.run') }}"
    ></div>

@endsection
