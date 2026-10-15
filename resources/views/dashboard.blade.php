@extends('layouts.app')

@section('title', __('common.dashboard'))

@section('content')
    <h1 class="text-2xl font-semibold tracking-tight">{{ __('common.dashboard') }}</h1>

    <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">
        {{ __('common.dashboard_placeholder') }}
    </p>
@endsection
