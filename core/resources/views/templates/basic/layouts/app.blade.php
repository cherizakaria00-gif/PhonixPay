<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" itemscope itemtype="http://schema.org/WebPage">

<head> 
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title> {{ gs()->siteName(__($pageTitle)) }}</title>
    @include('partials.seo')
  
    <link rel="stylesheet" href="{{ asset('assets/global/css/bootstrap.min.css') }}">
    <!-- fontawesome 5  -->
    <link rel="stylesheet" href="{{ asset('assets/global/css/all.min.css') }}">
    <!-- lineawesome font -->
    <link rel="stylesheet" href="{{ asset('assets/global/css/line-awesome.min.css') }}">

    @stack('style-lib')

    <!-- Main css -->
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue. 'frontend/css/main.css') }}">
    <link rel="stylesheet" href="{{ asset($activeTemplateTrue . 'css/custom.css') }}">

    @stack('style')

    <link rel="stylesheet" href="{{ asset($activeTemplateTrue.'css/color.php') }}?color={{ gs('base_color') }}&secondColor={{ gs('secondary_color') }}">

    @stack('header-script-lib')

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
      
    <!--==================== Preloader Start ====================-->
    <div class="preloader">
        <div class="loader-p"></div>
      </div>
    <!--==================== Preloader End ====================-->

    <!--==================== Overlay Start ====================-->
    <div class="body-overlay"></div>
    <!--==================== Overlay End ====================-->

    <!--==================== Sidebar Overlay End ====================-->
    <div class="sidebar-overlay"></div>
    <!--==================== Sidebar Overlay End ====================-->

    <!-- ==================== Scroll to Top End Here ==================== -->
    <a class="scroll-top"><i class="fas fa-angle-double-up"></i></a>
    <!-- ==================== Scroll to Top End Here ==================== -->

    @yield('app')

    @include($activeTemplate.'partials.whatsapp_chat')
  
    <!-- jQuery library -->
    <script src="{{ asset('assets/global/js/jquery-3.7.1.min.js') }}"></script>
    <!-- Bootstrap js -->
    <script src="{{ asset('assets/global/js/bootstrap.bundle.min.js') }}"></script>

    @stack('script-lib')

    <!-- Popper js -->
    <script src="{{ asset($activeTemplateTrue. 'js/popper.min.js') }}"></script>
    <!-- main js -->
    <script src="{{ asset($activeTemplateTrue. 'frontend/js/main.js') }}"></script>

    @php echo loadExtension('tawk-chat') @endphp

    @include('partials.notify')

    @if(gs('pn'))
      @include('partials.push_script')
    @endif

    @stack('script')

  </body>
</html>
