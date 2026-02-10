@extends($activeTemplate.'layouts.master', ['setting'=>true])

@section('content')
<div class="row justify-content-center gy-4">
    
    <div class="col-12">
        <div class="page-heading mb-4">
            <h3 class="mb-2">{{ __($pageTitle) }}</h3>
            <p>
                @lang('Personalize and keep your account up-to-date with our user-friendly profile page, allowing you to easily view and update your profile information. Manage your preferences and ensure that your account reflects your current details.')
            </p>
        </div>
        <hr>
    </div>

    <div class="col-xl-5 col-lg-6">
        <div class="card">
            <div class="card-body">
                <h4 class="mb-2 text-center">{{ @$user->fullname }}</h4>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span class="fw--bold"><i class="las la-user base--color"></i> @lang('Username')</span> 
                        <span>{{ @$user->username }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span class="fw--bold"><i class="las la-envelope base--color"></i> @lang('Email')</span> 
                        <span>[{{ $user->email }}]</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span class="fw--bold"><i class="las la-phone base--color"></i> @lang('Mobile')</span>
                        <span>[{{ $user->mobile }}]</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span class="fw--bold"><i class="las la-globe base--color"></i> @lang('Country')</span>
                        <span>{{ @$user->country_name }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span class="fw--bold"><i class="las la-money-bill-wave-alt base--color"></i> @lang('Payment Fixed Charge')</span>
                        <span>{{ showAmount($user->payment_fixed_charge) }}</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span class="fw--bold"><i class="las la-percent base--color"></i> @lang('Payment Percent Charge')</span>
                        <span>{{ showAmount($user->payment_percent_charge, currencyFormat:false) }} %</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-1">
                        <span class="fw--bold"><i class="las la-user-tie base--color"></i> @lang('Merchant')</span>
                        <span>@php echo $user->kycBadge; @endphp</span>
                    </li>
                </ul>
            </div>
        </div> 
    </div>
    <div class="col-xl-7 col-lg-6">
        <div class="card style--two">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-center">
                <h5 class="card-title">@lang('Profile Setting')</h5>
            </div>
            <div class="card-body p-4">
                <form class="register" action="" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label">@lang('First Name')</label>
                                <input type="text" class="form--control" name="firstname"
                                value="{{ $user->firstname }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label">@lang('Last Name')</label>
                                <input type="text" class="form--control" name="lastname"
                                value="{{ $user->lastname }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                            <label class="col-form-label">@lang('Address')</label>
                            <input type="text" class="form--control" name="address"
                            value="{{ @$user->address }}">
                        </div>
                        </div>
                        <div class="col-md-6">
                        <div class="form-group">
                            <label class="col-form-label">@lang('State')</label>
                            <input type="text" class="form--control" name="state"
                            value="{{ @$user->state }}">
                        </div>
                        </div>
                        <div class="col-md-6">
                        <div class="form-group">
                            <label class="col-form-label">@lang('Zip Code')</label>
                            <input type="text" class="form--control" name="zip"
                            value="{{ @$user->zip }}">
                        </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label">@lang('City')</label>
                                <input type="text" class="form--control" name="city"
                                value="{{ @$user->city }}">
                            </div>
                        </div>
                        <div class="col-md-12 mt-2">
                            <div class="form-group">
                                <button type="submit" class="btn btn--base w-100">@lang('Submit')</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
