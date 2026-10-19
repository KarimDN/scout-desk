@extends('layouts.app')

@section('title', __('ui.demo.title'))

@section('content')
    <h1 class="h3 mb-3">{{ __('ui.demo.title') }}</h1>
    <p class="text-body-secondary">{{ __('ui.demo.intro') }}</p>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <label class="form-label" for="patrol">{{ __('ui.demo.typed_text') }}</label>
            <input id="patrol" type="text" class="form-control" dir="auto" value="دورية النسور - Eagles Patrol">
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email">{{ __('ui.demo.email') }}</label>
            <input id="email" type="email" class="form-control" dir="ltr" value="scout.secretary@example.org">
        </div>
    </div>

    <p>{{ __('ui.demo.sent_to') }} <bdi dir="ltr">scout.secretary@example.org</bdi></p>
    <p dir="auto">فوج بيروت الأول: Summer Camp 2026 - المخيم الصيفي</p>

    <dl class="row">
        <dt class="col-sm-4">{{ __('ui.demo.today') }}</dt>
        <dd class="col-sm-8">{{ \App\Shared\Support\LocalizedDate::date(now()) }}</dd>

        <dt class="col-sm-4">{{ __('ui.demo.now') }}</dt>
        <dd class="col-sm-8">{{ \App\Shared\Support\LocalizedDate::dateTime(now()) }}</dd>
    </dl>
@endsection