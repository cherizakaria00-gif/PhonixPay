@extends($activeTemplate.'layouts.app')

@php
    $pages = App\Models\Page::where('tempname', $activeTemplate)->where('is_default', \App\Constants\Status::NO)->get();
    $blogs = App\Models\Frontend::where('data_keys', 'blog.element')->orderBy('id', 'DESC')->paginate(getPaginate());
@endphp

@push('style-lib')
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@endpush

@push('header-script-lib')
    <script src="https://cdn.tailwindcss.com"></script>
@endpush

@push('style')
    <style>
        html { scroll-behavior: smooth; }
        .preloader,
        .body-overlay,
        .sidebar-overlay,
        .scroll-top { display: none !important; }
    </style>
@endpush

@section('app')
<div class="min-h-screen bg-slate-950 text-white font-sans selection:bg-[#87c5a6]/30">
    <nav class="absolute w-full z-50 top-3 md:top-4 left-0">
        <div class="max-w-7xl mx-auto px-6">
            <div class="bg-black/60 backdrop-blur-md rounded-full shadow-lg border border-white/10 px-4 md:px-6">
                <div class="flex items-center gap-4 py-1.5">
                    <a href="{{ route('home') }}" class="flex items-center gap-3">
                        <img src="{{ siteLogo() }}" alt="@lang('Logo')" class="h-7 w-auto">
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
                            <a href="{{ route('user.register') }}" class="bg-[#87c5a6] hover:bg-[#9ad8bf] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">Créer un compte</a>
                        @endauth
                    </div>

                    <button id="nav-toggle" class="md:hidden ml-auto inline-flex items-center justify-center h-8 w-8 rounded-full border border-white/20 text-slate-200 hover:text-white hover:border-white/30">
                        <i data-lucide="menu" class="w-5 h-5"></i>
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
                                <a href="{{ route('user.register') }}" class="bg-[#87c5a6] hover:bg-[#9ad8bf] text-white px-3 py-1.5 rounded-full text-[12px] font-semibold shadow-md transition-colors">Créer un compte</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <main>
        <section class="pt-28 sm:pt-32 pb-10">
            <div class="mx-auto max-w-5xl px-6 text-center">
                <p class="text-xs uppercase tracking-[0.2em] text-[#a7d9c2]">@lang('Blogs')</p>
                <h1 class="mt-4 text-4xl sm:text-5xl font-bold tracking-tight text-white">
                    {{ __($pageTitle ?? 'Blogs') }}
                </h1>
                <p class="mt-4 text-slate-300 text-lg">@lang('Explore our latest insights and product updates.')</p>
            </div>
        </section>

        <section class="pb-20">
            <div class="mx-auto max-w-6xl px-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($blogs as $blog)
                        <article class="group bg-slate-900/60 border border-slate-800 rounded-2xl overflow-hidden">
                            <a href="{{ route('blog.details', [slug(@$blog->data_values->title)]) }}" class="block">
                                <div class="relative overflow-hidden">
                                    <img src="{{ getImage('assets/images/frontend/blog/' .@$blog->data_values->image, '820x450') }}" alt="@lang('Blog')" class="h-48 w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent"></div>
                                </div>
                            </a>
                            <div class="p-5">
                                <div class="text-xs text-slate-400">{{ showDateTime($blog->created_at, 'M d, Y') }}</div>
                                <h3 class="mt-2 text-lg font-semibold text-white">
                                    <a href="{{ route('blog.details', [slug(@$blog->data_values->title)]) }}" class="hover:text-[#a7d9c2] transition-colors">
                                        {{ strLimit(__($blog->data_values->title), 60) }}
                                    </a>
                                </h3>
                                <a href="{{ route('blog.details', [slug(@$blog->data_values->title)]) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-[#87c5a6] hover:text-[#a7d9c2]">
                                    @lang('Read More')
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full">
                            <x-empty-message h4="{{ true }}" />
                        </div>
                    @endforelse
                </div>

                <div class="mt-10">
                    {{ paginateLinks($blogs) }}
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-slate-950 border-t border-slate-800">
        <div class="mx-auto max-w-7xl px-6 py-12 md:flex md:items-center md:justify-between lg:px-8">
            <div class="mt-8 md:order-1 md:mt-0">
                <p class="text-center text-xs leading-5 text-slate-500">
                    &copy; {{ date('Y') }} {{ __(gs('site_name')) }}. Tous droits réservés.
                </p>
            </div>
            <div class="flex justify-center space-x-6 md:order-2">
                <a href="{{ route('policy.pages', 'terms-of-service') }}" class="text-slate-400 hover:text-slate-300">Conditions</a>
                <a href="{{ route('policy.pages', 'privacy-policy') }}" class="text-slate-400 hover:text-slate-300">Confidentialité</a>
                <a href="{{ route('api.documentation') }}" class="text-slate-400 hover:text-slate-300">API Docs</a>
            </div>
        </div>
    </footer>
</div>
@endsection

@push('script')
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) window.lucide.createIcons();
            const navToggle = document.getElementById('nav-toggle');
            const navMenuMobile = document.getElementById('nav-menu-mobile');
            if (navToggle && navMenuMobile) {
                navToggle.addEventListener('click', () => {
                    navMenuMobile.classList.toggle('hidden');
                });
            }
        });
    </script>
@endpush
