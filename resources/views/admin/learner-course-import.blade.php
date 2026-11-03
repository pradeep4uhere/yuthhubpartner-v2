@extends('layouts.app')

@section('content')

<link rel="stylesheet" href="{{ asset('css/admin-learner-course-import.css') }}">

<main class="admin-learner-course-import-page">
    <div
        id="admin-learner-course-import-root"
        data-react-app="admin-learner-course-import"
        data-admin-name="{{ auth('admin')->user()->name }}"
        data-csrf-token="{{ csrf_token() }}"
    ></div>
</main>

@endsection
