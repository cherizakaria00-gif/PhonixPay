<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" itemscope itemtype="http://schema.org/WebPage">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title> {{ gs()->siteName(__($pageTitle)) }}</title> 
    @include('partials.seo')

    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
    <!-- fontawesome 5  -->
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <!-- lineawesome font -->
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">

    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/lightcase.css') }}">
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/slick.css') }}">
    
    @stack('style-lib')

    <!-- main css -->
    <link rel="stylesheet" href="{{asset($activeTemplateTrue.'user/css/main.css') }}"> 

    @stack('style')

    @php $gaScript = loadExtension('google-analytics'); @endphp
    @if($gaScript)
        {!! $gaScript !!}
    @else
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-SLJ9E1HYHJ"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-SLJ9E1HYHJ');
        </script>
    @endif
</head>
<body>

    <div class="merchant-dashboard">
        @include($activeTemplate.'partials.auth_sidenav')
        @include($activeTemplate.'partials.auth_topbar')

        <div class="merchant-dashboard__body mt-5">
            <div class="dashboard-container">
                @if(@$setting)
                    @include($activeTemplate.'partials.setting_tab')
                @endif
                @yield('content')
            </div>
        </div>
    </div>

    <!-- jQuery library -->
    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <!-- bootstrap js -->
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>

    @stack('script-lib')

    <script src="{{ asset($activeTemplateTrue.'js/slick.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue.'js/lightcase.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue.'js/jquery.validate.js') }}"></script>

    <script src="{{ asset($activeTemplateTrue.'user/js/wow.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue.'user/js/jquery.slimscroll.min.js') }}"></script>
    <script src="{{ asset($activeTemplateTrue.'user/js/jquery.paroller.min.js') }}"></script>

    <!-- main css -->
    <script src="{{ asset($activeTemplateTrue.'user/js/app.js') }}"></script>

    @php echo loadExtension('tawk-chat') @endphp

    @include('partials.notify')
 
    @if(gs('pn'))
      @include('partials.push_script')
    @endif

    @stack('script')

    <script>
        (function($) {
            "use strict";
    
            $('form').on('submit', function () {
                if(!$(this).hasClass('exclude')){
                    if ($(this).valid()) {
                        $(':submit', this).attr('disabled', 'disabled');
                    }
                }
            });

            function formatState(state) {
                if (!state.id) return state.text;
                let gatewayData = $(state.element).data();
                return $(`<div class="d-flex gap-2">${gatewayData.imageSrc ? `<div class="select2-image-wrapper"><img class="select2-image" src="${gatewayData.imageSrc}"></div>` : '' }<div class="select2-content"> <p class="select2-title">${gatewayData.title}</p><p class="select2-subtitle">${gatewayData.subtitle}</p></div></div>`);
            }

            $('.select2').each(function(index,element){
                $(element).select2();
            });

            $('.select2-basic').each(function(index,element){
                $(element).select2({
                    dropdownParent: $(element).closest('.select2-parent')
                });
            });
    
            let disableSubmission = false;
            $('.disableSubmission').on('submit',function(e){
                if (disableSubmission) {
                e.preventDefault()
                }else{
                disableSubmission = true;
                }
            });

            var tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            var tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

        })(jQuery)
    </script>
</body>
</html>
