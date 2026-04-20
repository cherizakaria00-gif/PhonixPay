@extends('admin.layouts.app')

@php
    $effective = $status['effective'] ?? [];
    $sources = $status['sources'] ?? [];

    $mask = function (?string $value) {
        $value = (string) $value;
        if ($value === '') return '';
        $len = strlen($value);
        if ($len <= 8) return str_repeat('*', $len);
        return str_repeat('*', $len - 4) . substr($value, -4);
    };
@endphp

@section('panel')
    <form method="POST" action="{{ route('admin.setting.didit.update') }}">
        @csrf

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">@lang('Didit Identity Verification')</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        @if(($status['configured'] ?? false) && empty($status['issues']))
                            <div class="alert alert-success mb-0">
                                @lang('Didit is configured. If verification still fails, confirm the workflow exists in your Didit dashboard and matches your API key.')
                            </div>
                        @else
                            <div class="alert alert-warning mb-0">
                                @lang('Didit is not fully configured. Fix the issues below to enable identity verification.')
                            </div>
                        @endif
                    </div>

                    @if(!empty($status['issues']))
                        <div class="col-12">
                            <div class="alert alert-danger mb-0">
                                <strong>@lang('Issues'):</strong>
                                <ul class="mb-0">
                                    @foreach($status['issues'] as $issue)
                                        <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    @if(!empty($status['warnings']))
                        <div class="col-12">
                            <div class="alert alert-info mb-0">
                                <strong>@lang('Warnings'):</strong>
                                <ul class="mb-0">
                                    @foreach($status['warnings'] as $warning)
                                        <li>{{ $warning }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">@lang('Credentials')</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-6">
                        <label class="form-label">@lang('API Key')</label>
                        <input type="text" name="api_key" class="form-control" value="{{ old('api_key', $dbConfig['api_key'] ?? '') }}" placeholder="@lang('Paste your Didit API key')">
                        <small class="text-muted">@lang('Effective'): {{ $mask($effective['api_key'] ?? '') }} ({{ $sources['api_key'] ?? 'n/a' }})</small>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">@lang('Workflow ID (UUID)')</label>
                        <input type="text" name="workflow_id" class="form-control" value="{{ old('workflow_id', $dbConfig['workflow_id'] ?? '') }}" placeholder="@lang('e.g. 7fffca92-046e-462c-b770-130409944938')">
                        <small class="text-muted">@lang('Effective'): {{ $effective['workflow_id'] ?? '' }} ({{ $sources['workflow_id'] ?? 'n/a' }})</small>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">@lang('Webhook Secret')</label>
                        <input type="text" name="webhook_secret" class="form-control" value="{{ old('webhook_secret', $dbConfig['webhook_secret'] ?? '') }}" placeholder="@lang('Optional but recommended')">
                        <small class="text-muted">@lang('Effective'): {{ $mask($effective['webhook_secret'] ?? '') }} ({{ $sources['webhook_secret'] ?? 'n/a' }})</small>
                    </div>
                    <div class="col-lg-6">
                        <label class="form-label">@lang('Base URL')</label>
                        <input type="text" name="base_url" class="form-control" value="{{ old('base_url', $dbConfig['base_url'] ?? '') }}" placeholder="https://verification.didit.me">
                        <small class="text-muted">@lang('Effective'): {{ $effective['base_url'] ?? '' }} ({{ $sources['base_url'] ?? 'n/a' }})</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">@lang('Callback URL (optional)')</label>
                        <input type="text" name="callback_url" class="form-control" value="{{ old('callback_url', $dbConfig['callback_url'] ?? '') }}" placeholder="@lang('Public https URL Didit can call back')">
                        <small class="text-muted">@lang('Configured'): {{ $effective['callback_url_configured'] ?? '' }} ({{ $sources['callback_url'] ?? 'n/a' }})</small><br>
                        <small class="text-muted">@lang('Effective (used by app)'): {{ $effective['callback_url_effective'] ?? '' }}</small>
                    </div>
                    <div class="col-12">
                        <label class="form-label">@lang('Webhook Endpoint')</label>
                        <div class="input-group">
                            <input type="text" class="form-control" value="{{ route('webhooks.didit') }}" readonly>
                            <button type="button" class="input-group-text copyInput" title="@lang('Copy')">
                                <i class="las la-clipboard"></i>
                            </button>
                        </div>
                        <small class="text-muted">@lang('Add this endpoint in Didit dashboard webhooks and set the same webhook secret above.')</small>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button class="btn btn--primary w-100" type="submit">@lang('Update')</button>
            </div>
        </div>
    </form>
@endsection

