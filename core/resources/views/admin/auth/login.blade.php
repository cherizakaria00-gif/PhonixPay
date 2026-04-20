@extends('admin.layouts.master')
@push('style')
<style>
    .admin-login-shell {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 16px;
        background:
            linear-gradient(145deg, rgba(14, 18, 38, .62), rgba(43, 17, 57, .52)),
            url("{{ asset('assets/admin/images/login.jpg') }}") center center / cover no-repeat fixed;
        position: relative;
        overflow: hidden;
    }

    .admin-login-shell::before {
        content: "";
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 18% 20%, rgba(255, 255, 255, .18), transparent 45%),
            radial-gradient(circle at 80% 82%, rgba(160, 214, 255, .18), transparent 42%);
        pointer-events: none;
        animation: loginGlowMove 16s ease-in-out infinite alternate;
    }

    .admin-login-shell::after {
        content: "";
        position: absolute;
        width: 44vmax;
        height: 44vmax;
        border-radius: 50%;
        left: -12vmax;
        bottom: -14vmax;
        background: radial-gradient(circle, rgba(228, 97, 171, .16), rgba(66, 20, 70, 0));
        filter: blur(12px);
        pointer-events: none;
        animation: loginBlobMove 20s ease-in-out infinite;
    }

    .glass-login-card {
        position: relative;
        width: 100%;
        max-width: 520px;
        padding: 30px;
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, .22);
        background: linear-gradient(140deg, rgba(255, 255, 255, .16), rgba(255, 255, 255, .06));
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        box-shadow: 0 16px 44px rgba(8, 10, 26, .45);
    }

    .glass-login-card .login-wrapper,
    .glass-login-card .login-wrapper__top,
    .glass-login-card .login-wrapper__body {
        background: transparent !important;
        box-shadow: none !important;
        border: 0 !important;
        padding: 0 !important;
    }

    .glass-login-card .title {
        font-size: 2.05rem;
        line-height: 1.2;
        margin-bottom: 8px;
    }

    /* Remove old blue decorative wedge from legacy login style */
    .glass-login-card .login-wrapper__top::before,
    .glass-login-card .login-wrapper__top::after {
        display: none !important;
        content: none !important;
    }

    .glass-login-card .subtitle {
        color: rgba(255, 255, 255, .86);
        margin-bottom: 22px;
        font-size: 1rem;
    }

    .glass-login-card .form-group label {
        color: rgba(255, 255, 255, .92);
        margin-bottom: 8px;
        font-weight: 600;
    }

    .glass-login-card .form-control {
        height: 52px;
        border-radius: 999px;
        padding: 0 18px;
        border: 1px solid rgba(255, 255, 255, .25);
        background: rgba(255, 255, 255, .16);
        color: #fff;
    }

    .glass-login-card .form-control::placeholder {
        color: rgba(255, 255, 255, .70);
    }

    .glass-login-card .form-control:focus {
        border-color: rgba(136, 191, 255, .86);
        box-shadow: 0 0 0 4px rgba(104, 142, 255, .20);
        background: rgba(255, 255, 255, .20);
    }

    .glass-login-card .forget-text {
        color: rgba(255, 255, 255, .88);
    }

    .glass-login-card .forget-text:hover {
        color: #fff;
    }

    .glass-login-card .cmn-btn {
        margin-top: 8px;
        height: 52px;
        border-radius: 999px;
        border: 0;
        background: linear-gradient(90deg, #7f2f59, #8f335f);
        color: #fff;
        letter-spacing: .5px;
        font-weight: 600;
    }

    .glass-login-card .cmn-btn:hover {
        background: linear-gradient(90deg, #8a365f, #9c3f68);
    }

    @media (max-width: 575px) {
        .glass-login-card {
            max-width: 100%;
            padding: 22px 18px;
            border-radius: 20px;
        }

        .glass-login-card .title {
            font-size: 1.6rem;
        }
    }

    @keyframes loginGlowMove {
        0% {
            transform: translate3d(0, 0, 0) scale(1);
            opacity: .85;
        }
        100% {
            transform: translate3d(0, -12px, 0) scale(1.03);
            opacity: 1;
        }
    }

    @keyframes loginBlobMove {
        0% {
            transform: translate3d(0, 0, 0);
        }
        50% {
            transform: translate3d(18vw, -8vh, 0);
        }
        100% {
            transform: translate3d(4vw, 6vh, 0);
        }
    }
</style>
@endpush
@section('content')
<div class="admin-login-shell">
    <div class="glass-login-card">
        <div class="login-wrapper">
            <div class="login-wrapper__top">
                <h3 class="title text-white">@lang('Welcome')</h3>
                <p class="subtitle mb-0">{{ __($pageTitle) }} @lang('to') {{ __(gs('site_name')) }} @lang('Dashboard')</p>
            </div>
            <div class="login-wrapper__body">
                <form action="{{ route('admin.login') }}" method="POST" class="cmn-form mt-25 verify-gcaptcha login-form">
                    @csrf
                    <div class="form-group">
                        <label>@lang('Username')</label>
                        <input type="text" class="form-control" value="{{ old('username') }}" name="username" required>
                    </div>
                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <label>@lang('Password')</label>
                            <a href="{{ route('admin.password.reset') }}" class="forget-text">@lang('Forgot Password?')</a>
                        </div>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <x-captcha />
                    <button type="submit" class="btn cmn-btn w-100">@lang('LOGIN')</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
