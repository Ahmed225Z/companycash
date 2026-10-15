@extends('layouts.guest')

@section('title', __('common.login_title'))

@section('content')
    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-neutral-200 bg-white p-8 shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('common.login_title') }}</h1>
            <p class="mt-2 text-sm text-neutral-500 dark:text-neutral-400">{{ __('common.login_subtitle') }}</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium">{{ __('common.email') }}</label>
                    <input id="email"
                           name="email"
                           type="email"
                           value="{{ old('email') }}"
                           required
                           autofocus
                           autocomplete="username"
                           dir="ltr"
                           class="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-start shadow-sm outline-none transition focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:focus:border-neutral-100">
                    @error('email')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium">{{ __('common.password') }}</label>
                    <input id="password"
                           name="password"
                           type="password"
                           required
                           autocomplete="current-password"
                           dir="ltr"
                           class="mt-2 block w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-start shadow-sm outline-none transition focus:border-neutral-900 dark:border-neutral-700 dark:bg-neutral-950 dark:focus:border-neutral-100">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-neutral-600 dark:text-neutral-400">
                    <input type="checkbox" name="remember" value="1" class="rounded border-neutral-300 dark:border-neutral-700">
                    {{ __('common.remember_me') }}
                </label>

                <button type="submit"
                        class="w-full rounded-lg bg-neutral-900 px-4 py-2.5 font-medium text-white transition hover:bg-neutral-700 dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-neutral-300">
                    {{ __('common.login') }}
                </button>
            </form>
        </div>
    </div>
@endsection
