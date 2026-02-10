@extends($activeTemplate . 'layouts.' . $layout)

@section('content')
<div class="container @guest pt-120 pb-60 @endguest">
    <div class="row justify-content-center">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header card-header-bg d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="button-title-badge d-flex flex-wrap align-items-center gap-2"> 
                        <div class="button-badge d-flex flex-wrap align-items-center justify-content-between">
                            @php echo $myTicket->statusBadge; @endphp
                            <div class="d-block d-sm-none">
                                @if($myTicket->status != Status::TICKET_CLOSE && $myTicket->user) 
                                    <button class="btn btn-danger close-button btn-sm confirmationBtn" type="button" data-question="@lang('Are you sure to close this ticket?')" data-action="{{ route('ticket.close', $myTicket->id) }}"><i class="las la-times"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                        <h5 class="card-title mt-0">
                            [@lang('Ticket')#{{ $myTicket->ticket }}] {{ $myTicket->subject }}
                        </h5>
                    </div>
                    <div class="d-sm-block d-none">
                        @if($myTicket->status != Status::TICKET_CLOSE && $myTicket->user) 
                            <button class="btn btn-danger close-button btn-sm confirmationBtn" type="button" data-question="@lang('Are you sure to close this ticket?')" data-action="{{ route('ticket.close', $myTicket->id) }}"><i class="las la-times"></i>
                            </button>
                        @endif
                    </div>
                </div>

                <div class="card-body">
                    @if ($myTicket->status != 4)
                        <form method="post" action="{{ route('ticket.reply', $myTicket->id) }}" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="replayTicket" value="1">
                            <div class="row justify-content-between">
                                <div class="col-md-12">
                                    <div class="form-group">
                                    <textarea name="message" class="form-control form-control-lg" id="inputMessage" placeholder="@lang('Your Reply')"rows="4" cols="10"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row justify-content-between">
                                <div class="col-md-12">
                                    <div class="d-flex flex-wrap gap-4 justify-content-between align-items-center">
                                        <p class="text--danger">
                                            @lang('Max 5 files can be uploaded | Maximum upload size is '.convertToReadableSize(ini_get('upload_max_filesize')) .' | Allowed File Extensions: .jpg, .jpeg, .png, .pdf, .doc, .docx')
                                        </p>
                                        <button type="button" class="btn btn--success btn--sm btn-sm addAttachment"> 
                                            <i class="fas fa-plus"></i> @lang('Add New') 
                                        </button>
                                    </div>
                                    <div class="row fileUploadsContainer mt-4">
                                    </div>
                                </div>
                                <div class="col-md-12 mb-4">
                                    <button type="submit" class="btn btn--base custom-success w-100">@lang('Submit')</button>
                                </div>
                            </div>
                        </form>
                    @endif
                    <div class="row">
                        <div class="col-md-12 mt-4 mt-lg-0 mt-md-0">
                            <div class="card">
                                <div class="card-body">
                                    @foreach ($messages as $message)
                                        @if ($message->admin_id == 0)
                                            <div class="row border border-primary border-radius-3 my-3 py-3 mx-2">
                                                <div class="col-md-3 border-right text-right">
                                                    <h5 class="my-3">{{ $message->ticket->name }}</h5>
                                                </div>
                                                <div class="col-md-9">
                                                    <p class="text-muted font-weight-bold my-3">
                                                        @lang('Posted on')
                                                        {{ $message->created_at->format('l, dS F Y @ H:i') }}
                                                    </p>
                                                    <p>{{ $message->message }}</p>
                                                    @if ($message->attachments()->count() > 0)
                                                        <div class="mt-2">
                                                            @foreach ($message->attachments as $k => $image)
                                                                <a href="{{ route('ticket.download', encrypt($image->id)) }}" lass="mr-3"><i class="fa fa-file"></i>
                                                                    @lang('Attachment') {{ ++$k }} 
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <div class="row border border-warning border-radius-3 my-3 py-3 mx-2" style="background-color: #ffd96729">
                                                <div class="col-md-3 border-right text-right">
                                                    <h5 class="my-3">{{ $message->admin->name }}</h5>
                                                    <p class="lead text-muted">@lang('Staff')</p>
                                                </div>
                                                <div class="col-md-9">
                                                    <p class="text-muted font-weight-bold my-3">
                                                        @lang('Posted on')
                                                        {{ $message->created_at->format('l, dS F Y @ H:i') }}
                                                    </p>
                                                    <p>{{ $message->message }}</p>
                                                    @if ($message->attachments()->count() > 0)
                                                        <div class="mt-2">
                                                            @foreach ($message->attachments as $k => $image)
                                                                <a href="{{ route('ticket.download', encrypt($image->id)) }}" class="mr-3"><i class="fa fa-file"></i>
                                                                    @lang('Attachment') {{ ++$k }} 
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<x-user-confirmation-modal />
@endsection

@push('style')
    <style>
        .input-group-text:focus {
            box-shadow: none !important;
        }
        @media (max-width: 575px) {
            .button-badge {
                   width: 100%;  
            }
        }
        @media (max-width: 575px) {
            .button-title-badge {
                   width: 100%;  
            }
        }
        .btn--success.addFile {   
            border-color: hsl(var(--success)) !important; 
        }
        .btn--success.addFile:hover {   
            background-color: hsl(var(--success-d-200)) !important; 
            border-color: hsl(var(--success-d-200)) !important; 
            color: hsl(var(--white)) !important; 
        }
        .btn--danger.remove-btn {   
            border-color: hsl(var(--danger)) !important; 
        }
        .btn--danger.remove-btn:hover {   
            background-color: hsl(var(--danger-d-200)) !important; 
            border-color: hsl(var(--danger-d-200)) !important; 
            color: hsl(var(--white)) !important; 
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
