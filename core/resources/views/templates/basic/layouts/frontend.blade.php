@extends($activeTemplate.'layouts.app')

@section('app')
    @include($activeTemplate . 'partials.header')

    @if(request()->routeIs('home'))
        @include($activeTemplate . 'partials.banner')
    @else 
        @include($activeTemplate . 'partials.breadcrumb')
    @endif

    @stack('fbComment')

    @yield('content')

    @include($activeTemplate . 'partials.subscribe')
    @include($activeTemplate . 'partials.footer')

    @include($activeTemplate . 'partials.cookie_policy')
@endsection