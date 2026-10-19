<!DOCTYPE html>
@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, config('scoutdesk.rtl_locales'), true);
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('ui.app_name')) | {{ __('ui.app_name') }}</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/'.($isRtl ? 'bootstrap.rtl.min.css' : 'bootstrap.min.css')) }}">
</head>
<body>
    <nav class="navbar bg-body-tertiary border-bottom">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">{{ __('ui.app_name') }}</a>

            <div class="ms-auto d-flex gap-2" role="group" aria-label="{{ __('ui.nav.language') }}">
                @foreach (config('scoutdesk.locales') as $code)
                    <form method="POST" action="{{ route('locale.update') }}">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $code }}">
                        <button type="submit" lang="{{ $code }}"
                                class="btn btn-sm {{ $code === $locale ? 'btn-primary' : 'btn-outline-secondary' }}"
                                @if ($code === $locale) aria-current="true" @endif>
                            {{ __('ui.languages.'.$code) }}
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </nav>

    <main class="container py-4">
        @yield('content')
    </main>
</body>
</html>