@extends('admin.layouts.app')

@section('panel')
<div class="row gy-4">
    @foreach($levels as $level)
        @php
            $benefits = $level->benefits ?? [];
        @endphp
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">@lang('Level') {{ $level->level_number }}</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.rewards.levels.update', $level->id) }}" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">@lang('Name')</label>
                            <input type="text" class="form-control" name="name" value="{{ old('name', $level->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">@lang('Required Qualified Referrals')</label>
                            <input type="number" class="form-control" name="required_qualified_referrals" min="1" value="{{ old('required_qualified_referrals', $level->required_qualified_referrals) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">@lang('Discount %')</label>
                            <input type="number" class="form-control" name="discount_percent" min="0" max="100" value="{{ old('discount_percent', data_get($benefits, 'discount_percent')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('Discount Months')</label>
                            <input type="number" class="form-control" name="discount_duration_months" min="0" max="24" value="{{ old('discount_duration_months', data_get($benefits, 'discount_duration_months')) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">@lang('Revenue Share (bps)')</label>
                            <input type="number" class="form-control" name="revenue_share_bps" min="0" max="10000" value="{{ old('revenue_share_bps', data_get($benefits, 'revenue_share_bps')) }}">
                        </div>

                        <div class="col-md-4">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="badge_{{ $level->id }}" name="badge" @checked(data_get($benefits, 'badge'))>
                                <label class="form-check-label" for="badge_{{ $level->id }}">@lang('Badge')</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="priority_{{ $level->id }}" name="priority_support" @checked(data_get($benefits, 'priority_support'))>
                                <label class="form-check-label" for="priority_{{ $level->id }}">@lang('Priority Support')</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" value="1" id="active_{{ $level->id }}" name="is_active" @checked($level->is_active)>
                                <label class="form-check-label" for="active_{{ $level->id }}">@lang('Active')</label>
                            </div>
                        </div>

                        <div class="col-12 d-grid">
                            <button type="submit" class="btn btn--base">@lang('Save Level')</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
