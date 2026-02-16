<div class="d-sidebar h-100 rounded">
    <button class="sidebar-close-btn bg--base text-white"><i class="las la-times"></i></button>
    <div class="d-sidebar__thumb">
        <a href="{{route('home')}}"><img src="{{ siteLogo('dark') }}" alt=""></a>
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

            <li class="sidebar-menu__item {{ menuActive('user.calculate.charge') }}">
                <a href="{{ route('user.calculate.charge') }}" class="sidebar-menu__link">
                    <i class="las la-calculator"></i>
                    @lang('Calculate Charge')
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

            <li class="sidebar-menu__item {{ menuActive(['user.profile.setting', 'user.change.password', 'user.twofactor', 'user.api.key']) }}">
                <a href="{{ route('user.profile.setting') }}" class="sidebar-menu__link">
                    <i class="las la-cogs"></i>
                    @lang('Setting')
                </a>
            </li>

        </ul><!-- sidebar-menu end -->
        <div class="user-profile">
            <div class="user-profile-info">
                <div class="user-profile-info__icon">
                    <i class="las la-user"></i>
                </div>
                <div class="user-profile-info__content">
                    <h6 class="user-profile-info__name"><span>@</span>{{ strLimit(auth()->user()->username, 10) }}</h6>
                    <p class="user-profile-info__desc">{{ strLimit(auth()->user()->email, 18) }}</p>
                </div>
            </div>
            <button type="button" class="user-profile-dots dropdown-toggle" id="exportMenuButton" 
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="fas fa-ellipsis-h"></i>
            </button>
             <div class="dropdown-menu" aria-labelledby="exportMenuButton">
                <a class="dropdown-item" href="{{ route('user.profile.setting') }}">
                    <i class="las la-cogs"></i> @lang('Setting')
                </a>
                <a class="dropdown-item" href="{{ route('user.logout') }}">
                    <i class="las la-sign-out-alt"></i> @lang('Logout')
                </a>
            </div>
        </div>
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
