@php
    use App\Constants\Status;
    $merchantUser = auth()->user();
    $isAccountRestricted = !$merchantUser->hasGatewayAccess();
    $showSetupFeeMenu = $merchantUser->requiresSetupFee() || in_array(($merchantUser->setup_fee_status ?? 'unpaid'), ['pending_review', 'rejected'], true);
@endphp

<aside class="sidebar d-sidebar">
    <button class="sidebar-close-btn" type="button"><i class="las la-times"></i></button>

    <div class="s-logo">
        @php
            $merchantLogoVersion = @filemtime(public_path(resolveLogoAssetPath('logo_dark.png'))) ?: time();
        @endphp
        <a href="{{ route('home') }}" class="brand-link">
            <img src="{{ siteLogo('dark') }}?v={{ $merchantLogoVersion }}" alt="@lang('Logo')" class="brand-logo-img">
        </a>
    </div>

    <nav class="s-nav sidebar-menu-wrapper" id="sidebar-menu-wrapper">
        <ul class="sidebar-menu">
            <li class="sidebar-menu__item {{ menuActive('user.home') }}">
                <a href="{{ route('user.home') }}" class="sidebar-menu__link nav-item">
                    <i class="las la-th-large n-ic"></i><span>@lang('Dashboard')</span>
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.deposit.history') }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('user.deposit.history') }}"
                   class="sidebar-menu__link nav-item {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-file-alt n-ic"></i><span>@lang('Payment History')</span>
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.payment.links*') }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('user.payment.links.index') }}"
                   class="sidebar-menu__link nav-item {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-credit-card n-ic"></i><span>@lang('Payment Links')</span>
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.rewards*') }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('user.rewards.index') }}"
                   class="sidebar-menu__link nav-item {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-gift n-ic"></i><span>@lang('Rewards')</span>
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive(['user.withdraws', 'user.withdraw.method']) }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('user.withdraws') }}"
                   class="sidebar-menu__link nav-item {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-arrow-down n-ic"></i><span>@lang('Withdraws')</span>
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.transactions') }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('user.transactions') }}"
                   class="sidebar-menu__link nav-item {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-wallet n-ic"></i><span>@lang('Transactions')</span>
                </a>
            </li>

            <li class="sidebar-menu__item is-disabled">
                <a href="javascript:void(0)"
                   class="sidebar-menu__link nav-item is-disabled-link"
                   aria-disabled="true" tabindex="-1">
                    <i class="las la-credit-card n-ic"></i><span>@lang('Virtual Card (Coming Soon)')</span>
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('ticket.*') }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('ticket.index') }}"
                   class="sidebar-menu__link nav-item {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-life-ring n-ic"></i><span>@lang('Get Support')</span>
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.api.key') }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('user.api.key') }}"
                   class="sidebar-menu__link nav-item {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-code n-ic"></i><span>@lang('Developers')</span>
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.plan.billing') }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('user.plan.billing') }}"
                   class="sidebar-menu__link nav-item {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-credit-card n-ic"></i><span>@lang('Plan & Billing')</span>
                </a>
            </li>

            @if($showSetupFeeMenu)
                <li class="sidebar-menu__item {{ menuActive(['user.gateway.setup.fee*']) }}">
                    <a href="{{ route('user.gateway.setup.fee') }}" class="sidebar-menu__link nav-item">
                        <i class="las la-wallet n-ic"></i><span>@lang('Setup Fee')</span>
                    </a>
                </li>
            @endif

            <li class="sidebar-menu__item {{ menuActive(['user.profile.setting', 'user.change.password', 'user.twofactor']) }}">
                <a href="{{ route('user.profile.setting') }}" class="sidebar-menu__link nav-item">
                    <i class="las la-cog n-ic"></i><span>@lang('Setting')</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="sidebar-footer-action s-logout">
        <a href="{{ route('user.logout') }}" class="sidebar-menu__link sidebar-menu__link--logout nav-item">
            <i class="las la-sign-out-alt n-ic"></i><span>@lang('Logout')</span>
        </a>
    </div>
</aside>

@push('script')
<script>
    (function($) {
        'use strict';

        const sidebar = document.querySelector('.d-sidebar');
        const openBtn = document.querySelector('.sidebar-open-btn');
        const closeBtn = document.querySelector('.sidebar-close-btn');

        if (openBtn && sidebar) {
            openBtn.addEventListener('click', function() {
                sidebar.classList.add('active');
            });
        }

        if (closeBtn && sidebar) {
            closeBtn.addEventListener('click', function() {
                sidebar.classList.remove('active');
            });
        }

        if ($('#sidebar-menu-wrapper').length) {
            $('#sidebar-menu-wrapper').slimScroll({
                height: 'calc(100vh - 156px)'
            });
        }
    })(jQuery);
</script>
@endpush
