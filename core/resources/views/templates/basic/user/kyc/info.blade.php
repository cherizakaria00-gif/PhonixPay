@extends($activeTemplate.'layouts.master')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card custom--card style--two">
                    <div class="card-header">
                        <h5 class="card-title text-center">@lang('Information')</h5>
                    </div>
                    <div class="card-body">
                        @if($user->kyc_data)
                        <ul class="list-group list-group-flush">
                          @foreach($user->kyc_data as $val)
                          @continue(!$val->value)
                          <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{__($val->name)}}
                            <span>
                                @if($val->type == 'checkbox')
                                    {{ implode(',',$val->value) }}
                                @elseif($val->type == 'file')
                                    <a href="{{ route('user.download.attachment',encrypt(getFilePath('verify').'/'.$val->value)) }}" class="me-3"><i class="fa fa-file"></i>  @lang('Attachment') </a>
                                @else
                                <p>{{__($val->value)}}</p>
                                @endif
                            </span>
                          </li>
                          @endforeach
                        </ul>
                        @else
                            <p class="text-center">@lang('Data not found')</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
