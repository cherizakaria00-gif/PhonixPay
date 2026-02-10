@extends($activeTemplate.'layouts.frontend')

@section('content')
<div class="pt-120 pb-60">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="cookie-content">
                    @php
                        echo $cookie->data_values->description
                    @endphp
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
