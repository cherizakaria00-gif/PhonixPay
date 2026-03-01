@php
    use App\Constants\Status;
    $isAccountRestricted = auth()->user()->kv != Status::KYC_VERIFIED;
@endphp

<div class="d-sidebar h-100 rounded">
    <button class="sidebar-close-btn bg--base text-white"><i class="las la-times"></i></button>
    <div class="d-sidebar__thumb">
        <a href="{{route('home')}}"><img src="{{ siteLogo() }}" alt=""></a>
    </div>
    <div class="sidebar-menu-wrapper" id="sidebar-menu-wrapper">
        <ul class="sidebar-menu">

            <li class="sidebar-menu__item {{ menuActive('user.home') }}">
                <a href="{{ route('user.home') }}" class="sidebar-menu__link">
                    <i class="las la-home"></i>
                    @lang('Dashboard')
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.deposit.history') }}">
                <a href="{{ route('user.deposit.history') }}" class="sidebar-menu__link">
                    <i class="las la-history"></i>
                    @lang('Payment History')
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.payment.links*') }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('user.payment.links.index') }}"
                   class="sidebar-menu__link {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-link"></i>
                    @lang('Payment Links')
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.rewards*') }}">
                <a href="{{ route('user.rewards.index') }}" class="sidebar-menu__link">
                    <i class="las la-gift"></i>
                    @lang('Rewards')
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive(['user.withdraws', 'user.withdraw.method']) }}">
                <a href="{{ route('user.withdraws') }}" class="sidebar-menu__link">
                    <i class="las la-money-bill-wave-alt"></i>
                    @lang('Withdraws')
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.transactions') }}">
                <a href="{{ route('user.transactions') }}" class="sidebar-menu__link">
                    <i class="las la-exchange-alt"></i>
                    @lang('Transactions')
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('ticket.*') }}">
                <a href="{{ route('ticket.index') }}" class="sidebar-menu__link">
                    <i class="las la-headset"></i>
                    @lang('Get Support')
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.api.key') }} {{ $isAccountRestricted ? 'is-disabled' : '' }}">
                <a href="{{ $isAccountRestricted ? 'javascript:void(0)' : route('user.api.key') }}"
                   class="sidebar-menu__link {{ $isAccountRestricted ? 'is-disabled-link' : '' }}"
                   @if($isAccountRestricted) aria-disabled="true" tabindex="-1" @endif>
                    <i class="las la-code"></i>
                    @lang('Developers')
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive('user.plan.billing') }}">
                <a href="{{ route('user.plan.billing') }}" class="sidebar-menu__link">
                    <i class="las la-crown"></i>
                    @lang('Plan & Billing')
                </a>
            </li>

            <li class="sidebar-menu__item {{ menuActive(['user.profile.setting', 'user.change.password', 'user.twofactor']) }}">
                <a href="{{ route('user.profile.setting') }}" class="sidebar-menu__link">
                    <i class="las la-cogs"></i>
                    @lang('Setting')
                </a>
            </li>

        </ul><!-- sidebar-menu end -->
    </div>
</div>

@push('script')
    <script>
        'use strict';
        (function($) {
            const sidebar = document.querySelector('.d-sidebar');
            const sidebarOpenBtn = document.querySelector('.sidebar-open-btn');
            const sidebarCloseBtn = document.querySelector('.sidebar-close-btn');

            sidebarOpenBtn.addEventListener('click', function() {
                sidebar.classList.add('active');
            });
            sidebarCloseBtn.addEventListener('click', function() {
                sidebar.classList.remove('active');
            });


            $(function() {
                $('#sidebar-menu-wrapper').slimScroll({
                    height: '93vh'
                });
            });

            $('.sidebar-dropdown > a').on('click', function() {
                if ($(this).parent().find('.sidebar-submenu').length) {
                    if ($(this).parent().find('.sidebar-submenu').first().is(':visible')) {
                        $(this).find('.side-menu__sub-icon').removeClass('transform rotate-180');
                        $(this).removeClass('side-menu--open');
                        $(this).parent().find('.sidebar-submenu').first().slideUp({
                            done: function done() {
                                $(this).removeClass('sidebar-submenu__open');
                            }
                        });
                    } else {
                        $(this).find('.side-menu__sub-icon').addClass('transform rotate-180');
                        $(this).addClass('side-menu--open');
                        $(this).parent().find('.sidebar-submenu').first().slideDown({
                            done: function done() {
                                $(this).addClass('sidebar-submenu__open');
                            }
                        });
                    }
                }
            });
        })(jQuery);
    </script>
@endpush
