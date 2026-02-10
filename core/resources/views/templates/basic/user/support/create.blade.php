@extends($activeTemplate.'layouts.master')

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-12">
        <div class="card box-shadow">
            <div class="card-header d-flex justify-content-between flex-wrap gap-2 align-items-center">
                <h6>@lang('Open New Ticket')</h6>
                <a href="{{ route('ticket.index') }}" class="btn btn-sm btn--base text-end">
                    <i class="las la-backward"></i>
                    @lang('Support Tickets')
                </a>
            </div> 

            <div class="card-body"> 
                <form action="{{ route('ticket.store') }}" method="post" enctype="multipart/form-data"
                    onsubmit="return submitUserForm();">
                    @csrf
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label for="name">@lang('Name')</label>
                            <input type="text" name="name" value="{{ @$user->firstname . ' ' . @$user->lastname }}"
                                class="form--control " placeholder="@lang('Enter your name')" readonly>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="email">@lang('Email address')</label>
                            <input type="email" name="email" value="{{ @$user->email }}" class="form--control"
                                placeholder="@lang('Enter your email')" readonly>
                        </div>

                        <div class="form-group col-md-6">
                            <label>@lang('Subject')</label>
                            <input type="text" name="subject" value="{{ old('subject') }}" class="form--control"
                                placeholder="@lang('Subject')">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="priority">@lang('Priority')</label>
                            <select name="priority" class="form--control">
                                <option value="3">@lang('High')</option>
                                <option value="2">@lang('Medium')</option>
                                <option value="1">@lang('Low')</option>
                            </select>
                        </div>
                        <div class="col-12 form-group">
                            <label for="inputMessage">@lang('Message')</label>
                            <textarea name="message" id="inputMessage" rows="6" class="form--control" placeholder="@lang('Message')">{{ old('message') }}</textarea>
                        </div>
                    </div>

                    <div class="row form-group ">
                        <div class="col-md-12">
                            <div class="d-flex flex-wrap gap-4 justify-content-between align-items-center">
                                <p class="text--danger">
                                    @lang('Max 5 files can be uploaded | Maximum upload size is '.convertToReadableSize(ini_get('upload_max_filesize')) .' | Allowed File Extensions: .jpg, .jpeg, .png, .pdf, .doc, .docx')
                                </p>
                                <button type="button" class="btn btn--success btn-sm addAttachment"> 
                                    <i class="fas fa-plus"></i> @lang('Add New') 
                                </button>
                            </div>
                            <div class="row fileUploadsContainer mt-4">
                            </div>
                        </div>
                    </div>
                    <div class="row form-group justify-content-center">
                        <div class="col-md-12">
                            <button class="btn btn--base w-100" type="submit" id="recaptcha">@lang('Submit')</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('style')
    <style>
        .input-group-text:focus{
            box-shadow: none !important;
        }
    </style>
@endpush

@push('script')
    <script>
        (function ($) {
            "use strict";
            var fileAdded = 0;
            $('.addAttachment').on('click',function(){
                if (fileAdded >= 5) {
                    notify('error','You\'ve added maximum number of file');
                    return $(this).attr('disabled',true);
                }
                fileAdded++;  
                $(".fileUploadsContainer").append(`
                    <div class="col-lg-4 col-md-12 removeFileInput">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="file" name="attachments[]" class="form-control form-control-lg rounded" required />
                                <button class="input-group-text btn--danger removeFile text-white"><i class="las la-times"></i></button>
                            </div>
                        </div>
                    </div>
                `)
            }); 
            $(document).on('click','.removeFile',function(){
                $('.addAttachment').removeAttr('disabled',true)
                fileAdded--;
                $(this).closest('.removeFileInput').remove();
            });
        })(jQuery);

    </script>
@endpush

