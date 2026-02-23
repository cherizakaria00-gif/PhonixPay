@php
    $user = auth()->user();
    $showHeaderBalance = $showHeaderBalance ?? true;
    if ($showHeaderBalance) {
        $headerBalance = $user?->balance ?? 0;
        $headerPayoutAvailable = $headerBalance * 0.7;
        $headerPayoutAvailable = $headerPayoutAvailable < 0 ? 0 : $headerPayoutAvailable;
    }
    $profileSource = trim($user?->fullname ?? $user?->username ?? '');
    $profileInitials = 'U';
    if ($profileSource !== '') {
        $parts = preg_split('/\s+/', $profileSource);
        $profileInitials = strtoupper(substr($parts[0], 0, 1));
        if (isset($parts[1])) {
            $profileInitials .= strtoupper(substr($parts[1], 0, 1));
        } else {
            $profileInitials .= strtoupper(substr($parts[0], 1, 1) ?: '');
        }
        $profileInitials = substr($profileInitials, 0, 2);
    }
@endphp

<div class="dashboard-top-nav">
    <div class="row align-items-center">
        <div class="col-3 d-lg-block d-none">
            <h5 class="page-title">{{ __($pageTitle) }}</h5>
        </div>
        <div class="col-3 d-lg-none d-block">
            <button class="sidebar-open-btn"><i class="las la-bars"></i></button>
        </div>
        <div class="col-9">
            <div class="d-flex flex-wrap justify-content-end align-items-center header-toolbar">
                @if($showHeaderBalance)
                    <div class="header-balance header-balance--cards">
                        <div class="header-balance__card">
                            <span class="header-balance__icon">
                                <i class="las la-wallet"></i>
                            </span>
                            <div class="header-balance__content">
                                <span class="header-balance__label">@lang('Balance')</span>
                                <span class="header-balance__value">{{ showAmount($headerBalance) }}</span>
                            </div>
                        </div>
                        <div class="header-balance__card">
                            <span class="header-balance__icon">
                                <i class="las la-chart-line"></i>
                            </span>
                            <div class="header-balance__content">
                                <span class="header-balance__label">@lang('Payout Available')</span>
                                <span class="header-balance__value">{{ showAmount($headerPayoutAvailable) }}</span>
                            </div>
                        </div>
                    </div>
                @endif
                <div class="header-actions">
                    <button class="header-action-btn" type="button" aria-label="@lang('Notifications')">
                        <i class="las la-bell"></i>
                    </button>
                    <a href="{{ route('user.profile.setting') }}" class="header-profile" aria-label="@lang('Profile')">
                        {{ $profileInitials }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
