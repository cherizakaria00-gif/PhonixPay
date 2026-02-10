@php
    $route = \Route::currentRouteName();
@endphp

<div class="row justify-content-center mb-4">
    <div class="col-md-12">
        <ul class="nav nav-tabs" id="settingTab" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $route == 'user.profile.setting' ? 'active' : null }}" 
                    href="{{ route('user.profile.setting') }}" role="tab" 
                    aria-selected="{{ $route == 'user.profile.setting' ? 'true' : 'false' }}"
                >
                    @lang('Profile')
                </a>
            </li> 
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $route == 'user.change.password' ? 'active' : null }}" 
                    href="{{ route('user.change.password') }}" 
                    aria-selected="{{ $route == 'user.change.password' ? 'true' : 'false' }}" 
                >
                    @lang('Change Password')
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $route == 'user.twofactor' ? 'active' : null }}" 
                    href="{{ route('user.twofactor') }}" 
                    aria-selected="{{ $route == 'user.twofactor' ? 'true' : 'false' }}"
                >
                    @lang('2FA Security')
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $route == 'user.api.key' ? 'active' : null }}" 
                    href="{{ route('user.api.key') }}" 
                    aria-selected="{{ $route == 'user.api.key' ? 'true' : 'false' }}"
                >
                    @lang('Api Key') 
                </a>
            </li>
        </ul>
    </div>
</div>

<style>
    .nav-tabs .nav-link{
        border: none !important;
        font-weight: 700;
        color: #7e7e7e;
    }
    .nav-tabs .nav-link.active{
        color: #002046;
    }
    .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active{
        background: transparent;
    }
</style> 

