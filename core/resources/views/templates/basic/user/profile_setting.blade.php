@extends($activeTemplate.'layouts.master')

@php
    $showHeaderBalance = true;
@endphp

@section('settings_panel_content')
    <form class="register profile-modern-form" action="" method="post" enctype="multipart/form-data">
        @csrf
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">@lang('First Name')</label>
                    <input type="text" class="form--control" name="firstname" value="{{ $user->firstname }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">@lang('Last Name')</label>
                    <input type="text" class="form--control" name="lastname" value="{{ $user->lastname }}" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">@lang('Phone Number')</label>
                    <input type="text" class="form--control" name="mobile" value="{{ $user->mobile }}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">@lang('Email Address')</label>
                    <input type="email" class="form--control" name="email" value="{{ $user->email }}" required>
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">@lang('City')</label>
                    <input type="text" class="form--control" name="city" value="{{ @$user->city }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">@lang('State')</label>
                    <input type="text" class="form--control" name="state" value="{{ @$user->state }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">@lang('Zip Code')</label>
                    <input type="text" class="form--control" name="zip" value="{{ @$user->zip }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">@lang('Address')</label>
                    <input type="text" class="form--control" name="address" value="{{ @$user->address }}">
                </div>
            </div>

            <div class="col-md-6">
                <div class="form-group">
                    <label class="col-form-label">@lang('Username')</label>
                    <input type="text" class="form--control" value="{{ $user->username }}" readonly disabled>
                </div>
            </div>

            <div class="col-12 mt-2">
                <div class="form-group mb-0">
                    <button type="submit" class="btn profile-modern-submit-btn">@lang('Update')</button>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('content')
    @include($activeTemplate.'partials.profile_settings_shell')
@endsection
