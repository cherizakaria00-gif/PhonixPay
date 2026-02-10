@php
    $text = isset($register) ? 'Register' : 'Login';
@endphp

@if (@gs('socialite_credentials')->google->status == Status::ENABLE)
<div class="mb-3 continue-google">
    <a href="{{ route('user.social.login', 'google') }}" class="btn w-100 social-login-btn">
        <span class="google-icon">
        <img src="{{ asset($activeTemplateTrue."images/google.svg") }}" alt="Google">
        </span> @lang("$text with Google")
    </a>
</div>
@endif
@if (@gs('socialite_credentials')->facebook->status == Status::ENABLE)
<div class="mb-3 continue-facebook">
    <a href="{{ route('user.social.login', 'facebook') }}" class="btn w-100 social-login-btn">
        <span class="facebook-icon">
        <img src="{{ asset($activeTemplateTrue."images/facebook.svg") }}" alt="Facebook">
        </span> @lang("$text with Facebook")
    </a>
</div>
@endif
@if (@gs('socialite_credentials')->linkedin->status == Status::ENABLE)
<div class="continue-facebook mb-3">
    <a href="{{ route('user.social.login', 'linkedin') }}" class="btn w-100 social-login-btn">
        <span class="facebook-icon">
        <img src="{{ asset($activeTemplateTrue."images/linkdin.svg") }}" alt="Linkedin">
        </span> @lang("$text with Linkedin")
    </a>
</div>
@endif

@if (@gs('socialite_credentials')->linkedin->status || @gs('socialite_credentials')->facebook->status == Status::ENABLE || @gs('socialite_credentials')->google->status == Status::ENABLE)
<div class="text-center login-or">
    <span>@lang('OR')</span>
</div>
@endif

@push('style')
<style>
    .social-login-btn{
        border: 1px solid #cbc4c4;
        color: hsl(var(--dark));
    }
    .btn.social-login-btn:hover {
        border: 1px solid hsl(var(--base));
        color: hsl(var(--dark));
    }

    .login-or{
        position: relative;
        padding: 12px 0px;
        margin-bottom: 12px;
    }
    .login-or span{
        padding: 4px 10px;
        background-color: hsl(var(--white));
        z-index: 1;
        font-weight: 500;
    }
    .login-or:before{
        position: absolute;
        content: '';
        width: 100%;
        height: 1px;
        left: 0px;
        background-color: hsl(var(--dark) / 0.2);
        top: 50%;
        transform: translateY(-50%);
        z-index: -1;
    }
</style>
@endpush
