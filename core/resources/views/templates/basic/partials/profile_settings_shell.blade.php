@php
    $settingsUser = auth()->user();
    $settingsRoute = \Route::currentRouteName();
    $profileInitials = strtoupper(substr(trim((string) $settingsUser->firstname), 0, 1) . substr(trim((string) $settingsUser->lastname), 0, 1));
    if (trim($profileInitials) === '') {
        $profileInitials = strtoupper(substr((string) $settingsUser->username, 0, 2));
    }
@endphp

<div class="profile-modern-page">
    <div class="row g-4 profile-modern-grid">
        <div class="col-xl-3 col-lg-4">
            <div class="card profile-modern-side">
                <div class="profile-modern-identity">
                    <div class="profile-modern-avatar">{{ $profileInitials }}</div>
                    <h5 class="profile-modern-name">{{ @$settingsUser->fullname }}</h5>
                    <p class="profile-modern-email">{{ $settingsUser->email }}</p>
                </div>

                <ul class="profile-modern-stats">
                    <li>
                        <span>@lang('Username')</span>
                        <strong>{{ @$settingsUser->username }}</strong>
                    </li>
                    <li>
                        <span>@lang('Country')</span>
                        <strong>{{ @$settingsUser->country_name }}</strong>
                    </li>
                    <li>
                        <span>@lang('Merchant')</span>
                        <strong>{!! $settingsUser->kycBadge !!}</strong>
                    </li>
                    <li>
                        <span>@lang('Fixed Charge')</span>
                        <strong>{{ showAmount($settingsUser->payment_fixed_charge) }}</strong>
                    </li>
                    <li>
                        <span>@lang('Percent Charge')</span>
                        <strong>{{ showAmount($settingsUser->payment_percent_charge, currencyFormat:false) }} %</strong>
                    </li>
                </ul>
            </div>
        </div>

        <div class="col-xl-9 col-lg-8">
            <div class="card profile-modern-panel">
                <ul class="nav nav-tabs profile-modern-tabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $settingsRoute == 'user.profile.setting' ? 'active' : '' }}" href="{{ route('user.profile.setting') }}">
                            @lang('Account Settings')
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $settingsRoute == 'user.change.password' ? 'active' : '' }}" href="{{ route('user.change.password') }}">
                            @lang('Change Password')
                        </a>
                    </li>
                    <li class="nav-item" role="presentation">
                        <a class="nav-link {{ $settingsRoute == 'user.twofactor' ? 'active' : '' }}" href="{{ route('user.twofactor') }}">
                            @lang('2FA Security')
                        </a>
                    </li>
                </ul>

                <div class="card-body profile-modern-form-wrap">
                    @yield('settings_panel_content')
                </div>
            </div>
        </div>
    </div>
</div>

@push('style')
    <style>
        .profile-modern-page {
            position: relative;
        }

        .profile-modern-grid {
            margin-top: 0;
            position: relative;
            z-index: 3;
        }

        .profile-modern-side,
        .profile-modern-panel {
            border: 1px solid #dbe2ee;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            background: #ffffff;
        }

        .profile-modern-identity {
            padding: 30px 20px 24px;
            text-align: center;
            border-bottom: 1px solid #edf2f7;
        }

        .profile-modern-avatar {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            margin: 0 auto 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #eef2ff 0%, #dbe7ff 100%);
            border: 4px solid #ffffff;
            box-shadow: 0 8px 25px rgba(45, 91, 255, 0.2);
            color: #2743bf;
            font-size: 30px;
            font-weight: 700;
        }

        .profile-modern-name {
            margin-bottom: 4px;
            color: #1e293b;
            font-size: 22px;
            font-weight: 700;
        }

        .profile-modern-email {
            margin-bottom: 0;
            color: #64748b;
            font-size: 13px;
            word-break: break-all;
        }

        .profile-modern-stats {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .profile-modern-stats li {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            padding: 13px 16px;
            border-bottom: 1px solid #edf2f7;
            font-size: 13px;
        }

        .profile-modern-stats li:last-child {
            border-bottom: 0;
        }

        .profile-modern-stats li span {
            color: #64748b;
            font-weight: 500;
        }

        .profile-modern-stats li strong {
            color: #0f172a;
            font-weight: 700;
            text-align: right;
        }

        .profile-modern-tabs {
            border-bottom: 1px solid #edf2f7;
            padding: 0 22px;
            display: flex;
            gap: 16px;
            flex-wrap: nowrap;
            overflow-x: auto;
        }

        .profile-modern-tabs .nav-link {
            border: 0 !important;
            background: transparent !important;
            color: #6b7280 !important;
            font-size: 15px;
            font-weight: 600;
            padding: 16px 2px 14px;
            white-space: nowrap;
            border-bottom: 3px solid transparent !important;
        }

        .profile-modern-tabs .nav-link.active {
            color: #2d5bff !important;
            border-bottom-color: #2d5bff !important;
        }

        .profile-modern-form-wrap {
            padding: 24px 22px 26px;
        }

        .profile-modern-form .col-form-label,
        .profile-modern-form-wrap .form-label {
            color: #334155;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .profile-modern-form .form--control,
        .profile-modern-form-wrap .form--control {
            background: #f8fafc;
            border: 1px solid #dbe5f1;
            border-radius: 10px;
            height: 50px;
            padding: 0 14px;
            color: #0f172a;
            font-size: 15px;
        }

        .profile-modern-form .form--control:focus,
        .profile-modern-form-wrap .form--control:focus {
            border-color: #2d5bff;
            box-shadow: 0 0 0 3px rgba(45, 91, 255, 0.12);
        }

        .profile-modern-form .form--control[readonly],
        .profile-modern-form .form--control[disabled] {
            background: #eef2f7;
            color: #64748b;
        }

        .profile-modern-form-wrap .card.style--two,
        .profile-modern-form-wrap .custom--card.style--two {
            border: 1px solid #dbe5f1;
            border-radius: 12px;
            box-shadow: none;
        }

        .profile-modern-form-wrap .card-header {
            background: #ffffff;
            border-bottom: 1px solid #e9edf5;
        }

        .profile-modern-form-wrap .btn.btn--base,
        .profile-modern-submit-btn {
            border: 0;
            background: linear-gradient(135deg, #2d5bff 0%, #2c4fd6 100%);
            color: #ffffff;
            border-radius: 10px;
            height: 48px;
            min-width: 170px;
            padding: 0 28px;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 12px 26px rgba(45, 91, 255, 0.25);
        }

        .profile-modern-form-wrap .btn.btn--base:hover,
        .profile-modern-submit-btn:hover {
            color: #ffffff;
            transform: translateY(-1px);
        }

        .profile-modern-section-head {
            margin-bottom: 16px;
        }

        .profile-modern-section-head h5 {
            margin-bottom: 8px;
            color: #1f2937;
            font-size: 28px;
            font-weight: 700;
        }

        .profile-modern-section-head p {
            margin-bottom: 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 991px) {
            .profile-modern-grid {
                margin-top: 0;
            }
        }

        @media (max-width: 767px) {
            .profile-modern-tabs {
                padding: 0 12px;
                gap: 12px;
            }

            .profile-modern-form-wrap {
                padding: 16px 14px 20px;
            }

            .profile-modern-form-wrap .btn.btn--base,
            .profile-modern-submit-btn {
                width: 100%;
            }

            .profile-modern-section-head h5 {
                font-size: 24px;
            }
        }
    </style>
@endpush
