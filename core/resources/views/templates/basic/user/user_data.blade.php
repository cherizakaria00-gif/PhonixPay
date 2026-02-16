@extends($activeTemplate.'layouts.app')

@php
    $login = @getContent('login_register.content', true)->data_values;
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

        .new-auth {
            font-family: 'Inter', sans-serif;
            --input-bg: #0b1220;
            --input-fg: #ffffff;
            --input-border: #1f2a44;
            --input-placeholder: #94a3b8;
        }
        .new-auth.light-inputs {
            --input-bg: #ffffff;
            --input-fg: #0b1220;
            --input-border: #e2e8f0;
            --input-placeholder: #64748b;
        }
        .new-auth .form--control,
        .new-auth .select2-container--default .select2-selection--single {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--input-fg);
            padding: 12px 14px;
            border-radius: 12px;
            width: 100%;
            height: auto;
        }
        .new-auth .form--control::placeholder {
            color: var(--input-placeholder);
        }
        .new-auth .form--control:-webkit-autofill,
        .new-auth .form--control:-webkit-autofill:hover,
        .new-auth .form--control:-webkit-autofill:focus {
            -webkit-text-fill-color: var(--input-fg);
            transition: background-color 9999s ease-in-out 0s;
            box-shadow: 0 0 0 1000px var(--input-bg) inset;
        }
        .new-auth .form--control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            outline: none;
        }
        .new-auth .select2-container--default .select2-selection--single {
            display: flex;
            align-items: center;
        }
        .new-auth .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: var(--input-fg);
            padding-left: 0;
        }
        .new-auth .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
        }
        .new-auth .input-group-text {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--input-fg);
        }
        .new-auth .text--danger,
        .new-auth .text-danger {
            color: #fca5a5 !important;
        }
    </style>
@endpush

@section('app')
<div class="new-auth min-h-screen bg-slate-950 text-white relative overflow-hidden">
    <div class="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
        <div class="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]" style="clip-path: polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%);"></div>
    </div>

    <a href="{{ route('home') }}" class="absolute top-6 right-6 z-20 h-11 w-11 rounded-full bg-white/10 hover:bg-white/20 border border-white/10 flex items-center justify-center">
        <i class="fas fa-times"></i>
    </a>

    <div class="relative z-10 min-h-screen flex flex-col lg:flex-row">
        <div class="hidden lg:flex lg:w-1/2 items-center justify-center px-12 py-12">
            <div class="max-w-md">
                <div class="flex items-center gap-3 mb-6">
                    <img src="{{ siteLogo() }}" alt="@lang('Logo')" class="h-10">
                </div>
                <h1 class="text-4xl font-bold tracking-tight text-white mb-4">
                    @lang('Complete Your Profile')
                </h1>
                <p class="text-slate-300 text-lg">
                    @lang('Finalize your details to unlock your merchant dashboard and start accepting payments.')
                </p>
                <div class="mt-10">
                    <div class="relative bg-slate-900/60 border border-slate-800 rounded-2xl p-4 shadow-2xl">
                        <img src="{{ getImage('assets/images/frontend/login_register/' .@$login->image, '615x620') }}" alt="" class="rounded-xl w-full h-auto">
                    </div>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-xl bg-slate-900/70 border border-slate-800 rounded-2xl p-8 shadow-2xl">
                <h2 class="text-2xl font-bold text-white mb-2">{{ __($pageTitle) }}</h2>
                <p class="text-slate-400 mb-6">@lang('Fill in the required details to complete your profile.')</p>

                <form method="POST" action="{{ route('user.data.submit') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Username')</label>
                        <div class="input--group">
                            <input type="text" class="form--control checkUser" name="username" value="{{ old('username') }}" required>
                            <small class="text--danger usernameExist"></small>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Country')</label>
                            <select name="country" class="form--control select2" required>
                                @foreach ($countries as $key => $country)
                                    <option data-mobile_code="{{ $country->dial_code }}" value="{{ $country->country }}" data-code="{{ $key }}">{{ __($country->country) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Mobile')</label>
                            <div class="input-group">
                                <span class="input-group-text mobile-code border-end-0"></span>
                                <input type="hidden" name="mobile_code">
                                <input type="hidden" name="country_code">
                                <input type="number" name="mobile" value="{{ old('mobile') }}" class="form--control form-control checkUser" required>
                            </div>
                            <small class="text-danger mobileExist"></small>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Address')</label>
                            <input type="text" class="form-control form--control" name="address" value="{{ old('address') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('State')</label>
                            <input type="text" class="form-control form--control" name="state" value="{{ old('state') }}">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('Zip Code')</label>
                            <input type="text" class="form-control form--control" name="zip" value="{{ old('zip') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">@lang('City')</label>
                            <input type="text" class="form-control form--control" name="city" value="{{ old('city') }}">
                        </div>
                    </div>

                    <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-500 transition-colors">
                        @lang('Submit')
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style-lib')
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}">
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {

            @if($mobileCode)
                $(`option[data-code={{ $mobileCode }}]`).attr('selected','');
            @endif

            $('.select2').select2();

            $('select[name=country]').on('change',function() {
                $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
                $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
                $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));
                var value = $('[name=mobile]').val();
                var name = 'mobile';
                checkUser(value,name);
            });

            $('input[name=mobile_code]').val($('select[name=country] :selected').data('mobile_code'));
            $('input[name=country_code]').val($('select[name=country] :selected').data('code'));
            $('.mobile-code').text('+' + $('select[name=country] :selected').data('mobile_code'));


            $('.checkUser').on('focusout', function(e) {
                var value = $(this).val();
                var name = $(this).attr('name')
                checkUser(value,name);
            });

            function checkUser(value,name){
                var url = '{{ route('user.checkUser') }}';
                var token = '{{ csrf_token() }}';

                if (name == 'mobile') {
                    var mobile = `${value}`;
                    var data = {
                        mobile: mobile,
                        mobile_code:$('.mobile-code').text().substr(1),
                        _token: token
                    }
                }
                if (name == 'username') {
                    var data = {
                        username: value,
                        _token: token
                    }
                }
                $.post(url, data, function(response) {
                     if (response.data != false) {
                        $(`.${response.type}Exist`).text(`${response.field} already exist`);
                    } else {
                        $(`.${response.type}Exist`).text('');
                    }
                });
            }
        })(jQuery);
    </script>
@endpush
