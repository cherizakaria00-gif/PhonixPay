@php
    $pages = App\Models\Page::where('tempname', $activeTemplate)->where('is_default', \App\Constants\Status::NO)->get();
@endphp

<nav class="absolute w-full z-50 top-3 md:top-4 left-0">
    <div class="max-w-7xl mx-auto px-6">
        <div class="bg-black/60 backdrop-blur-md rounded-full shadow-lg border border-white/10 px-4 md:px-6">
            <div class="flex items-center gap-4 py-1.5">
                <a href="{{ route('home') }}" class="flex items-center">
                    <img src="{{ siteLogo() }}" alt="@lang('Logo')" class="h-10 sm:h-12 w-auto">
                </a>

                <div class="hidden md:flex flex-1 justify-center">
                    <div class="flex items-center gap-5 text-[12px] font-medium text-slate-200">
                        <a href="{{ route('home') }}" class="hover:text-white transition-colors">@lang('Home')</a>
                        @foreach($pages as $data)
                            <a href="{{ route('pages',[$data->slug]) }}" class="hover:text-white transition-colors">{{ __($data->name) }}</a>
                        @endforeach
                        <a href="{{ route('blogs') }}" class="hover:text-white transition-colors">@lang('Blogs')</a>
                        <a href="{{ route('api.documentation') }}" class="hover:text-white transition-colors">@lang('Developer')</a>
                    </div>
                </div>

                <div class="hidden md:flex items-center gap-3">
                    @auth
                        <a href="{{ route('user.home') }}" class="bg-[#87c5a6] hover:bg-[#9ad8bf] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">@lang('Dashboard')</a>
                    @else
                        <a href="{{ route('user.login') }}" class="text-[#87c5a6] hover:text-[#a7d9c2] text-[12px] font-semibold transition-colors">@lang('Login')</a>
                        <a href="{{ route('user.register') }}" class="bg-[#87c5a6] hover:bg-[#9ad8bf] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">@lang('Create an account')</a>
                    @endauth
                </div>

                <button id="nav-toggle" class="md:hidden ml-auto inline-flex items-center justify-center h-8 w-8 rounded-full border border-white/20 text-slate-200 hover:text-white hover:border-white/30" type="button">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <div class="md:hidden pb-3">
                <div id="nav-menu-mobile" class="hidden bg-black/80 rounded-2xl shadow-lg border border-white/10 p-4">
                    <div class="flex flex-col gap-3 text-[12px] font-medium text-slate-200">
                        <a href="{{ route('home') }}" class="hover:text-white transition-colors">@lang('Home')</a>
                        @foreach($pages as $data)
                            <a href="{{ route('pages',[$data->slug]) }}" class="hover:text-white transition-colors">{{ __($data->name) }}</a>
                        @endforeach
                        <a href="{{ route('blogs') }}" class="hover:text-white transition-colors">@lang('Blogs')</a>
                        <a href="{{ route('api.documentation') }}" class="hover:text-white transition-colors">@lang('Developer')</a>
                    </div>
                    <div class="mt-4 flex items-center gap-3">
                        @auth
                            <a href="{{ route('user.home') }}" class="bg-[#87c5a6] hover:bg-[#9ad8bf] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">@lang('Dashboard')</a>
                        @else
                            <a href="{{ route('user.login') }}" class="text-[#87c5a6] hover:text-[#a7d9c2] text-[12px] font-semibold transition-colors">@lang('Login')</a>
                            <a href="{{ route('user.register') }}" class="bg-[#87c5a6] hover:bg-[#9ad8bf] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">@lang('Create an account')</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
