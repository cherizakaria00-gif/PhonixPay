@php
    $footer = @getContent('footer.content', true)->data_values;
    $policyPages = @getContent('policy_pages.element', orderById:true);
@endphp

<!-- ==================== Footer Start Here ==================== -->
<footer class="footer">
    <div class="footer-shapes">
        <div class="footer-shape base"></div>
        <div class="footer-shape base-two"></div>
    </div> 
    <div class="footer__inner section-bg">
        <div class="pb-60 pt-120">
            <div class="container">
                <div class="row gy-5">
                    <div class="col-lg-3 col-sm-4 col-xsm-6">
                        <div class="footer-item">
                            <div class="footer-item__logo">
                                <a href="{{ route('home') }}"> <img src="{{ siteLogo('dark') }}" alt="@lang('Logo')"></a>
                            </div>
                            <p class="footer-item__desc">{{ __(@$footer->description) }}</p>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-4 col-xsm-6">
                        <div class="footer-item">
                            <h5 class="footer-item__title">@lang('Useful Links')</h5>
                            <ul class="footer-menu">
                                <li class="footer-menu__item">
                                    <a href="{{ route('home') }}" class="footer-menu__link">@lang('Home')</a>
                                </li> 
                                <li class="footer-menu__item">
                                    <a href="{{ route('user.login') }}" class="footer-menu__link">@lang('Login')</a>
                                </li>
                                <li class="footer-menu__item">
                                    <a href="{{ route('user.register') }}" class="footer-menu__link">@lang('Become Merchant')</a>
                                </li> 
                            </ul> 
                        </div> 
                    </div> 
                    <div class="col-lg-2 col-sm-4 col-xsm-6">
                        <div class="footer-item">
                            <h5 class="footer-item__title">@lang('Resources')</h5>
                            <ul class="footer-menu">
                                <li class="footer-menu__item">
                                    <a href="{{ route('blogs') }}" class="footer-menu__link">@lang('Blogs')</a>
                                </li>
                                <li class="footer-menu__item">
                                    <a href="{{ route('cookie.policy') }}" class="footer-menu__link">@lang('Cookie Policy')</a>
                                </li> 
                                <li class="footer-menu__item">
                                    <a href="{{ route('contact') }}" class="footer-menu__link">@lang('Contact Us')</a>
                                </li> 
                            </ul>
                        </div>
                    </div> 
                    <div class="col-lg-2 col-sm-4 col-xsm-6">
                        <div class="footer-item">
                            <h5 class="footer-item__title">@lang('Developers')</h5>
                            <ul class="footer-menu">
                                <li class="footer-menu__item">
                                    <a href="{{ route('api.documentation') }}" class="footer-menu__link">@lang('Documentation')</a>
                                </li>
                                <li class="footer-menu__item">
                                    <a href="{{ route('api.documentation') }}#initiate" class="footer-menu__link">@lang('API Reference')</a>
                                </li>
                                <li class="footer-menu__item">
                                    <a href="{{ route('api.documentation') }}#currency" class="footer-menu__link">@lang('Supported Currencies')</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-2 col-sm-4 col-xsm-6">
                        <div class="footer-item">
                            <h5 class="footer-item__title">@lang('Other Links')</h5>
                            <ul class="footer-menu">
                                @foreach($policyPages as $page)
                                    <li class="footer-menu__item">
                                        <a href="{{ route('policy.pages', ['slug'=>slug($page->data_values->title)]) }}" class="footer-menu__link">
                                            {{ __($page->data_values->title) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <!-- Footer Top End-->
    
        <!-- bottom Footer -->
        <div class="bottom-footer section-bg py-4">
            <div class="container">
                <div class="row gy-3">
                    <div class="col-md-12 text-center">
                        <div class="bottom-footer-text"> 
                            @lang('Copyright') &copy; {{ date('Y') }} <a href="{{ route('home') }}" class="anchor-color">{{ __(gs('site_name')) }}</a> 
                            @lang('All Right Reserved').
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- ==================== Footer End Here ==================== -->