<!-- ==================== breadcrumb Start Here ==================== -->
<section class="breadcrumb py-120 bg-img bg-overlay-one" style="background-image: url({{ asset($activeTemplateTrue.'frontend/images/shapes/breadcrumb-bg.png') }});">
    <div class="container"> 
        <div class="row">
            <div class="col-lg-12">
                <div class="breadcrumb__wrapper">
                    <h1 class="breadcrumb__title">

                        @if(request()->routeIs('api.documentation'))
                            @lang('Find Comprehensive Information on Our API')

                        @elseif(request()->routeIs('cookie.policy'))
                            @lang('Knowing About the Cookie Policy')

                        @elseif(request()->routeIs('policy.pages'))
                            @lang('Getting Acquainted with Rules and Regulations')
                        @else 
                            @if(@$page)
                                {{ __(@$page->breadcrumb_title) }}
                            @elseif(@$sections)
                                {{ __(@$sections->breadcrumb_title) }}

                            @else 
                                {{ __($pageTitle) }}
                            @endif
                        @endif

                    </h1> 
                    <ul class="breadcrumb__list">
                        <li class="breadcrumb__item"><a href="{{ route('home') }}" class="breadcrumb__link"> <i class="las la-home"></i> @lang('Home')</a> </li>
                        <li class="breadcrumb__item">/</li>
                        <li class="breadcrumb__item"> <span class="breadcrumb__item-text"> {{ __($pageTitle) }} </span> </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ==================== breadcrumb End Here ==================== -->