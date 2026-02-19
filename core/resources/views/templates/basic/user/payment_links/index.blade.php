@extends($activeTemplate.'layouts.master')

@php
    $showHeaderBalance = true;
@endphp

@section('content')
    <div class="row">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center mb-3">
            <h4 class="mb-0">@lang('Payment Links')</h4>
            <a href="{{ route('user.payment.links.create') }}" class="btn btn--base btn-sm">
                <i class="las la-plus"></i> @lang('Create Payment Link')
            </a>
        </div>

        <div class="col-12">
            <div class="card custom--card border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table--light style--two">
                            <thead>
                                <tr>
                                    <th>@lang('Link')</th>
                                    <th>@lang('Amount')</th>
                                    <th>@lang('Description')</th>
                                    <th>@lang('Expires')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($paymentLinks as $link)
                                    @php
                                        $linkUrl = route('payment.link.show', $link->code);
                                    @endphp
                                    <tr>
                                        <td class="min-w-250">
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control" value="{{ $linkUrl }}" readonly>
                                                <button type="button" class="btn btn--light copy-link-btn" data-link="{{ $linkUrl }}">
                                                    <i class="las la-copy"></i>
                                                </button>
                                            </div>
                                        </td>
                                        <td>
                                            {{ showAmount($link->amount, currencyFormat:false) }} {{ __($link->currency) }}
                                        </td>
                                        <td>{{ $link->description ?: __('N/A') }}</td>
                                        <td>
                                            @if($link->expires_at)
                                                {{ showDateTime($link->expires_at) }}
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>@php echo $link->statusBadge; @endphp</td>
                                        <td>
                                            @if($link->status == \App\Models\PaymentLink::STATUS_ACTIVE)
                                                <a href="{{ route('user.payment.links.edit', $link->id) }}"
                                                   class="btn btn--light btn-sm">
                                                    <i class="las la-edit"></i> @lang('Edit')
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">@lang('No payment links found')</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($paymentLinks->hasPages())
                    <div class="card-footer py-4">
                        @php echo paginateLinks($paymentLinks) @endphp
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    (function() {
        document.querySelectorAll('.copy-link-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const link = this.getAttribute('data-link');
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(link);
                } else {
                    const temp = document.createElement('input');
                    temp.value = link;
                    document.body.appendChild(temp);
                    temp.select();
                    document.execCommand('copy');
                    document.body.removeChild(temp);
                }
            });
        });
    })();
</script>
@endpush
