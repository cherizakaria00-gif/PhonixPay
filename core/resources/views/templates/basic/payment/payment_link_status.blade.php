@extends($activeTemplate.'layouts.app')

@section('app')
    <div class="py-60">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-6">
                    <div class="card custom--card text-center">
                        <div class="card-body">
                            <h4 class="mb-2">{{ __($message) }}</h4>
                            <p class="text-muted mb-0">@lang('If you think this is a mistake, please contact the merchant.')</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
