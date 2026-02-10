@extends($activeTemplate.'layouts.master', ['setting'=>true])

@section('content')
<div class="row justify-content-center gy-4">

    <div class="col-12">
        <div class="page-heading mb-4">
            <h3 class="mb-2">{{ __($pageTitle) }}</h3>
            <p>
                @lang('Protect your account from unauthorized access and enhance your security by changing your password on our user-friendly password change page. Keep your information safe and secure with ease.')
            </p>
        </div>
        <hr>
    </div>

    <div class="col-lg-12">
        <div class="card style--two">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-center">
                <h5 class="card-title">@lang('Change Password')</h5>
            </div>
            <div class="card-body p-4">
                <form class="register" action="" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="col-form-label">@lang('Current Password')</label>
                                <input type="password" class="form--control" name="current_password" required autocomplete="current-password">
                            </div>
                        </div>
                            <div class="form-group">
                                <label class="col-form-label">@lang('Password')</label>
                                <div class="input-group">
                                    <input
                                        type="password" 
                                        class="form--control @if(gs('secure_password')) secure-password @endif" 
                                        name="password" 
                                        required 
                                        autocomplete="current-password" 
                                    >
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="col-form-label">@lang('Confirm Password')</label>
                                <input type="password" class="form--control" name="password_confirmation" required autocomplete="current-password">
                            </div>
                        </div>
                        <div class="col-md-12 mt-2">
                            <div class="form-group">
                                <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@if(gs('secure_password'))
    @push('script-lib')
        <script src="{{ asset('assets/global/js/secure_password.js') }}"></script>
    @endpush
@endif