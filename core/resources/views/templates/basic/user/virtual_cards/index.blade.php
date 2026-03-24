@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="vc-page">
    <div class="vc-hero card custom--card border-0">
        <div class="vc-hero__head d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                @if($cards->count())
                    <span class="fw-semibold">@lang('Card Balance'): <span id="vcActiveCardBalance">{{ showAmount($cards->first()->balance) }} {{ $cards->first()->currency }}</span></span>
                @else
                    <span class="fw-semibold">@lang('Card Balance'): 0.00 USD</span>
                @endif
            </div>
            <a href="{{ route('user.virtual.cards.quick.create') }}" class="btn btn--primary btn-sm">
                <i class="las la-plus"></i> @lang('Create A New Card')
            </a>
        </div>

        @if($cards->count())
            <div class="vc-card-stage" id="vcCardStage">
                @foreach($cards as $index => $card)
                    @php
                        $displayPan = $card->masked_pan ?: ('**** **** **** ' . ($card->last4 ?: '0000'));
                        $rawPan = (string) (
                            data_get($card->meta, 'create_response.data.card_number')
                            ?? data_get($card->meta, 'create_response.data.pan')
                            ?? data_get($card->meta, 'create_response.response.card_number')
                            ?? data_get($card->meta, 'create_response.response.pan')
                            ?? $displayPan
                        );
                        $hiddenPan = '**** **** **** ' . ($card->last4 ?: '****');
                        $exp = data_get($card->meta, 'create_response.data.expiry', '12/30');
                        $hiddenExp = '**/**';
                        $cardName = strtoupper($user->firstname . ' ' . $user->lastname);
                        $hiddenName = '**** ****';
                        $cardLabel = data_get($card->meta, 'label') ?: ('Card ' . ($card->last4 ?: str_pad((string)($index + 1), 4, '0', STR_PAD_LEFT)));
                        $cardColor = data_get($card->meta, 'color') ?: ['#4f46e5','#2563eb','#14b8a6','#22c55e','#f59e0b','#ef4444'][($card->id ?? $index) % 6];
                        $cardStatusKey = strtolower((string) $card->status);
                        $cardStatusText = ucfirst($cardStatusKey ?: 'unknown');
                    @endphp
                    <div class="vc-card-wrap {{ $index === 0 ? 'is-active' : '' }}" data-index="{{ $index }}" data-card-id="{{ $card->id }}" data-balance="{{ showAmount($card->balance) }} {{ $card->currency }}" data-pan="{{ $displayPan }}" data-last4="{{ $card->last4 }}" data-status="{{ ucfirst($card->status) }}" data-provider-id="{{ $card->provider_card_id }}" data-pan-full="{{ $rawPan }}" data-pan-hidden="{{ $hiddenPan }}" data-exp-full="{{ $exp }}" data-exp-hidden="{{ $hiddenExp }}" data-name-full="{{ $cardName }}" data-name-hidden="{{ $hiddenName }}">
                        <div class="flip-card">
                            <div class="flip-card-inner">
                                <div class="flip-card-front">
                                    <div class="card-tag">
                                        <span class="card-tag__dot" style="background: {{ $cardColor }};"></span>
                                        <span class="card-tag__text">{{ $cardLabel }}</span>
                                    </div>
                                    <div class="card-status card-status--{{ $cardStatusKey }}">{{ $cardStatusText }}</div>
                                    <img src="{{ siteLogo() }}" alt="FlujiPay" class="flujipay-logo">
                                    <svg version="1.1" class="chip" xmlns="http://www.w3.org/2000/svg" width="30px" height="30px" viewBox="0 0 50 50">
                                        <rect x="1" y="1" width="48" height="48" rx="8" fill="#e6cf8c"></rect>
                                        <rect x="11" y="9" width="28" height="32" rx="4" fill="#d3b16a"></rect>
                                        <line x1="11" y1="20" x2="39" y2="20" stroke="#b8954f" stroke-width="2"></line>
                                        <line x1="11" y1="30" x2="39" y2="30" stroke="#b8954f" stroke-width="2"></line>
                                    </svg>
                                    <svg version="1.1" class="contactless" xmlns="http://www.w3.org/2000/svg" width="20px" height="20px" viewBox="0 0 50 50">
                                        <path d="M18 17c5 3 5 13 0 16" stroke="#f3f3f3" stroke-width="3" fill="none" stroke-linecap="round"></path>
                                        <path d="M26 14c7 5 7 18 0 23" stroke="#f3f3f3" stroke-width="3" fill="none" stroke-linecap="round"></path>
                                        <path d="M34 11c9 7 9 22 0 29" stroke="#f3f3f3" stroke-width="3" fill="none" stroke-linecap="round"></path>
                                    </svg>
                                    <p class="number js-card-pan">{{ $hiddenPan }}</p>
                                    <p class="valid_thru d-none"></p>
                                    <p class="date_8264 js-card-exp">{{ $hiddenExp }}</p>
                                    <p class="name js-card-name">{{ $hiddenName }}</p>
                                </div>
                                <div class="flip-card-back">
                                    <div class="strip"></div>
                                    <div class="mstrip"></div>
                                    <div class="sstrip">
                                        <p class="code">***</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="vc-nav d-flex justify-content-center gap-2">
                <button type="button" class="vc-nav-btn" id="vcPrev"><i class="las la-angle-left"></i></button>
                <button type="button" class="vc-nav-btn" id="vcReveal"><i class="las la-eye"></i></button>
                <button type="button" class="vc-nav-btn" id="vcAddBalance"><i class="las la-plus"></i></button>
                <button type="button" class="vc-nav-btn" id="vcFlip"><i class="las la-sync"></i></button>
                <button type="button" class="vc-nav-btn" id="vcNext"><i class="las la-angle-right"></i></button>
            </div>

            @php $initialCardId = $cards->first()->id; @endphp
        @else
            <div class="text-center py-5 text-muted">@lang('No virtual card yet, create your first card')</div>
        @endif
    </div>

    <div class="row g-3 mt-1">
        <div class="col-xl-3 col-md-6">
            <div class="vc-stat">
                <p>@lang('Current Balance')</p>
                <h4>{{ showAmount($totalCardBalance) }}</h4>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="vc-stat">
                <p>@lang('Total Add Money')</p>
                <h4>{{ showAmount($totalAddMoney) }}</h4>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="vc-stat">
                <p>@lang('Total Transactions')</p>
                <h4>{{ showAmount($totalTransactionsAmount) }}</h4>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="vc-stat">
                <p>@lang('Active Tickets')</p>
                <h4>{{ $activeTickets }}</h4>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="vc-stat">
                <p>@lang('Active Card')</p>
                <h4>{{ $activeCards }}</h4>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="vc-stat">
                <p>@lang('Total Cards')</p>
                <h4>{{ $cards->count() }} / {{ $maxCards }}</h4>
            </div>
        </div>
    </div>

    <div class="card custom--card border-0 mt-4">
        <div class="card-header bg-transparent border-0"><h6 class="mb-0">@lang('Card Transactions')</h6></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--light style--two mb-0">
                    <thead>
                        <tr>
                            <th>@lang('Date')</th>
                            <th>@lang('Card')</th>
                            <th>@lang('Type')</th>
                            <th>@lang('Amount')</th>
                            <th>@lang('Status')</th>
                            <th>@lang('Description')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $allTransactions = $cards->flatMap(fn($card) => $card->transactions)->sortByDesc(fn($t) => $t->transacted_at ?: $t->created_at)->take(100);
                        @endphp
                        @forelse($allTransactions as $txn)
                            <tr>
                                <td>{{ showDateTime($txn->transacted_at ?: $txn->created_at, 'd M Y H:i') }}</td>
                                <td>{{ $txn->card->masked_pan ?: ('**** ' . $txn->card->last4) }}</td>
                                <td>{{ strtoupper($txn->type) }}</td>
                                <td>{{ showAmount($txn->amount) }} {{ $txn->currency }}</td>
                                <td><span class="badge badge--info">{{ ucfirst($txn->status) }}</span></td>
                                <td>{{ $txn->description ?: 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted">@lang('No transactions found')</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@if($cards->count())
<div class="modal fade" id="addBalanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">@lang('Add Balance')</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('user.virtual.cards.fund', $initialCardId) }}" method="POST" id="vcAddFundForm">
                @csrf
                <input type="hidden" name="card_id" id="vcFundCardId" value="{{ $initialCardId }}">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="vc-modal-card">
                                <h4 class="vc-modal-title">@lang('Add Money')</h4>
                                <div class="mb-3">
                                    <label class="form-label">@lang('Enter Amount')</label>
                                    <div class="input-group">
                                        <input type="number" name="amount" id="vcAddAmount" step="0.01" min="0.01" class="form-control" placeholder="0.00" required>
                                        <span class="input-group-text">{{ $addMoneyMeta['currency'] }}</span>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted">
                                    <span>@lang('Available Balance') <strong id="vcAvailText">{{ number_format((float) $addMoneyMeta['available_balance'], 4) }} {{ $addMoneyMeta['currency'] }}</strong></span>
                                    <span>@lang('Charge'): <strong id="vcChargeHint">{{ number_format((float) $addMoneyMeta['fixed_charge'], 4) }} {{ $addMoneyMeta['currency'] }} + {{ number_format((float) $addMoneyMeta['percent_charge'], 4) }}%</strong></span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="vc-modal-card">
                                <h4 class="vc-modal-title">@lang('Add Money Preview')</h4>
                                <ul class="vc-preview-list">
                                    <li><span>@lang('Enter Amount')</span><strong id="vcPreviewAmount">0.0000 {{ $addMoneyMeta['currency'] }}</strong></li>
                                    <li><span>@lang('Exchange Rate')</span><strong>1 {{ $addMoneyMeta['currency'] }} = 1.0000 {{ $addMoneyMeta['currency'] }}</strong></li>
                                    <li><span>@lang('Fees & Charges')</span><strong id="vcPreviewFees">{{ number_format((float) $addMoneyMeta['fixed_charge'], 4) }} {{ $addMoneyMeta['currency'] }}</strong></li>
                                    <li><span>@lang('Conversion Amount')</span><strong id="vcPreviewConv">0.0000 {{ $addMoneyMeta['currency'] }}</strong></li>
                                    <li><span>@lang('Will Get')</span><strong id="vcPreviewWillGet">0.0000 {{ $addMoneyMeta['currency'] }}</strong></li>
                                    <li><span>@lang('Total Payable Amount')</span><strong id="vcPreviewPayable">{{ number_format((float) $addMoneyMeta['fixed_charge'], 4) }} {{ $addMoneyMeta['currency'] }}</strong></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">@lang('Cancel')</button>
                    <button type="submit" class="btn btn--success">@lang('Add Balance')</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif

<div class="modal fade" id="createCardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">@lang('Create New Virtual Card')</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('user.virtual.cards.store') }}" method="POST" id="vcCreateForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">@lang('Cardholder Name')</label>
                        <input type="text" name="name_on_card" class="form-control" placeholder="{{ $user->fullname }}">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">@lang('Currency')</label>
                        <input type="text" name="currency" class="form-control" value="USD">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">@lang('Cancel')</button>
                    <button type="submit" class="btn btn--primary">@lang('Create Card')</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
.vc-page{margin:0 auto}
.vc-hero{padding:18px;background:#e9e9ef;border:1px solid #e0e2ea;border-radius:14px;max-width:640px;margin:0 auto}
.vc-alert-icon{width:30px;height:30px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#377dff;color:#fff}
.vc-card-stage{margin:12px auto 8px;max-width:420px;position:relative}
.vc-card-wrap{display:none}
.vc-card-wrap.is-active{display:block}
.flip-card{background-color:transparent;width:360px;height:224px;margin:0 auto;perspective:1000px;color:#fff}
.flip-card-inner{position:relative;width:100%;height:100%;text-align:center;transition:transform .8s;transform-style:preserve-3d}
.vc-card-wrap.is-flipped .flip-card-inner{transform:rotateY(180deg)}
.flip-card-front,.flip-card-back{position:absolute;width:100%;height:100%;backface-visibility:hidden;border-radius:18px;box-shadow:0 14px 28px rgba(18,20,44,.28);border:1px solid rgba(255,255,255,.18);background:#1f2377}
.flip-card-back{transform:rotateY(180deg)}
.flip-card-front{background:radial-gradient(circle at 52% 70%,rgba(122,120,255,.44),transparent 28%),radial-gradient(circle at 85% 10%,rgba(153,152,255,.25),transparent 22%),linear-gradient(120deg,#0f1260 0%,#23258a 56%,#3d3f8f 100%)}
.card-tag{position:absolute;top:1.1em;left:1.1em;display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);padding:3px 10px;line-height:1.1;min-height:30px;border-radius:999px;max-width:160px}
.card-tag__dot{width:8px;height:8px;border-radius:50%;display:inline-block;flex:0 0 8px}
.card-tag__text{font-size:.78rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.card-status{position:absolute;top:1.1em;right:1.1em;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;line-height:1.1;min-height:30px;border-radius:999px;padding:4px 11px;letter-spacing:.04em;text-transform:uppercase}
.card-status--active,.card-status--success,.card-status--succeed,.card-status--live,.card-status--posted{background:rgba(34,197,94,.2);color:#bbf7d0;border:1px solid rgba(34,197,94,.35)}
.card-status--pending,.card-status--initiated,.card-status--processing,.card-status--queued{background:rgba(245,158,11,.2);color:#fde68a;border:1px solid rgba(245,158,11,.35)}
.card-status--frozen,.card-status--freeze{background:rgba(59,130,246,.2);color:#bfdbfe;border:1px solid rgba(59,130,246,.35)}
.card-status--failed,.card-status--rejected,.card-status--error,.card-status--cancelled{background:rgba(239,68,68,.2);color:#fecaca;border:1px solid rgba(239,68,68,.35)}
.card-status:not([class*="card-status--active"]):not([class*="card-status--success"]):not([class*="card-status--succeed"]):not([class*="card-status--live"]):not([class*="card-status--posted"]):not([class*="card-status--pending"]):not([class*="card-status--initiated"]):not([class*="card-status--processing"]):not([class*="card-status--queued"]):not([class*="card-status--frozen"]):not([class*="card-status--freeze"]):not([class*="card-status--failed"]):not([class*="card-status--rejected"]):not([class*="card-status--error"]):not([class*="card-status--cancelled"]){background:rgba(148,163,184,.2);color:#e2e8f0;border:1px solid rgba(148,163,184,.35)}
.flujipay-logo{position:absolute;top:2.5em;right:1.1em;max-width:84px;height:auto;filter:brightness(0) invert(1);opacity:.94}
.chip{position:absolute;top:3.7em;left:1.5em;transform:scale(1.08)}
.contactless{position:absolute;top:5.5em;left:4.8em;transform:scale(1.05)}
.number{position:absolute;font-weight:700;font-size:1.2rem;top:9.35em;left:1.45em;letter-spacing:.12em}
.valid_thru{position:absolute;font-weight:700;font-size:.72rem;top:12.9em;left:2.45em;opacity:.78;letter-spacing:.08em}
.date_8264{position:absolute;font-weight:700;font-size:1.08rem;bottom:1.15em;right:1.3em;min-width:84px;text-align:right}
.name{position:absolute;font-weight:700;font-size:.92rem;bottom:1.15em;left:1.35em;right:7.1em;letter-spacing:.06em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;opacity:.92}
.strip{position:absolute;background:repeating-linear-gradient(45deg,#303030,#303030 10px,#202020 10px,#202020 20px);width:100%;height:1.7em;top:2.3em}
.mstrip{position:absolute;background:#fff;width:58%;height:.85em;top:5.4em;left:.8em;border-radius:2.5px}
.sstrip{position:absolute;background:#fff;width:26%;height:.85em;top:5.4em;right:.8em;border-radius:2.5px}
.code{font-weight:700;text-align:center;margin:.16em 0 0;color:#000;font-size:.9em}
.vc-nav-btn{width:40px;height:40px;border-radius:50%;border:1px solid #a8c3ff;background:transparent;color:#6b788e;font-size:22px;line-height:1}
.vc-stat{background:#bdd6f1;border-radius:18px;padding:14px 16px;min-height:100px}
.vc-stat p{margin:0;color:#1f2a44;font-size:16px;font-weight:600}
.vc-stat h4{margin:8px 0 0;font-size:28px;line-height:1.1;font-weight:700;color:#0b1535}
.vc-tools .form-label{font-size:14px;color:#6b7280}
.vc-tools .form-control-sm{height:34px}
.modal-backdrop.show{backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);background:rgba(15,23,42,.35)}
#addBalanceModal .modal-content{background:rgba(255,255,255,.84);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);border:1px solid rgba(255,255,255,.55);box-shadow:0 14px 32px rgba(15,23,42,.16)}
.vc-modal-card{background:#eef5ff;border:1px solid #d8e4f5;border-radius:10px;padding:12px}
.vc-modal-title{font-size:19px;margin:0 0 10px;font-weight:600;color:#1f2a44;text-align:left}
.vc-modal-card .form-label{font-size:13px;color:#4b5563;margin-bottom:6px}
.vc-modal-card .form-control,.vc-modal-card .input-group-text{font-size:14px;height:40px}
.vc-preview-list{list-style:none;padding:0;margin:0}
.vc-preview-list li{display:flex;justify-content:space-between;gap:12px;padding:6px 0;border-bottom:1px solid rgba(17,24,39,.10)}
.vc-preview-list li span{color:#334155;font-size:14px}
.vc-preview-list li strong{color:#0f172a;font-size:14px;font-weight:600}
@media (min-width:768px){
    .vc-card-wrap .number{top:146px !important;left:38px !important;font-size:1.02rem !important;line-height:1 !important;letter-spacing:.11em !important}
    .vc-card-wrap .valid_thru{top:170px !important;left:40px !important;font-size:.6rem !important;line-height:1 !important}
    .vc-card-wrap .name{bottom:18px !important;left:38px !important;right:122px !important;font-size:.82rem !important;line-height:1 !important}
    .vc-card-wrap .date_8264{bottom:16px !important;right:22px !important;font-size:1rem !important;line-height:1 !important}
}
@media (max-width:767px){
    .vc-hero{padding:14px;max-width:100%}
    .flip-card{width:286px;height:178px}
    .flujipay-logo{max-width:66px;top:2.55em;right:1.1em}
    .chip{top:2.4em;left:1.5em;transform:none}
    .contactless{top:3.7em;left:4.2em;transform:none}
    .card-tag{max-width:150px;left:1.1em;top:.95em;padding:2px 8px;min-height:28px}
    .card-tag__text{font-size:.58em}
    .card-status{top:.95em;right:1.1em;font-size:.52em;min-height:28px;padding:2px 8px}
    .number{font-size:.92em;top:8.9em;left:1.6em}
    .valid_thru{font-size:.5em;top:12.7em;left:2.8em}
    .date_8264{font-size:.76em;bottom:1.25em;right:1.8em;min-width:72px}
    .name{font-size:.62em;bottom:1.25em;left:1.2em;right:7.2em}
    .vc-nav-btn{width:32px;height:32px;font-size:18px}
    .vc-stat{min-height:86px;border-radius:12px;padding:12px 14px}
    .vc-stat p{font-size:13px}
    .vc-stat h4{font-size:24px}
    .vc-modal-title{font-size:18px}
    .vc-modal-card .form-control,.vc-modal-card .input-group-text{font-size:13px;height:36px}
    .vc-preview-list li span,.vc-preview-list li strong{font-size:12px}
}
</style>
@endpush

@push('script')
<script>
(function(){
    const wraps = Array.from(document.querySelectorAll('.vc-card-wrap'));
    if (!wraps.length) return;

    let index = 0;
    let revealed = false;
    const fundCardId = document.getElementById('vcFundCardId');
    const fundForm = document.getElementById('vcAddFundForm');
    const activeCardBalance = document.getElementById('vcActiveCardBalance');
    const createForm = document.getElementById('vcCreateForm');
    const revealBtn = document.getElementById('vcReveal');
    const addBalanceBtn = document.getElementById('vcAddBalance');
    const addBalanceModalEl = document.getElementById('addBalanceModal');
    const addBalanceModal = addBalanceModalEl && window.bootstrap ? new bootstrap.Modal(addBalanceModalEl) : null;
    const amountInput = document.getElementById('vcAddAmount');
    const previewAmount = document.getElementById('vcPreviewAmount');
    const previewFees = document.getElementById('vcPreviewFees');
    const previewConv = document.getElementById('vcPreviewConv');
    const previewWillGet = document.getElementById('vcPreviewWillGet');
    const previewPayable = document.getElementById('vcPreviewPayable');
    const meta = {
        currency: '{{ $addMoneyMeta['currency'] }}',
        fixedCharge: {{ (float) $addMoneyMeta['fixed_charge'] }},
        percentCharge: {{ (float) $addMoneyMeta['percent_charge'] }},
    };

    const fmt = (v) => Number(v || 0).toFixed(4) + ' ' + meta.currency;
    const recalcPreview = () => {
        const amount = Number(amountInput?.value || 0);
        const fee = meta.fixedCharge + ((amount * meta.percentCharge) / 100);
        const payable = amount + fee;
        const willGet = amount;

        if (previewAmount) previewAmount.textContent = fmt(amount);
        if (previewFees) previewFees.textContent = fmt(fee);
        if (previewConv) previewConv.textContent = fmt(amount);
        if (previewWillGet) previewWillGet.textContent = fmt(willGet);
        if (previewPayable) previewPayable.textContent = fmt(payable);
    };

    if (createForm) {
        createForm.setAttribute('action', '{{ route("user.virtual.cards.store") }}');
        createForm.setAttribute('method', 'POST');
    }

    const applySensitiveView = () => {
        const active = wraps[index];
        if (!active) return;

        const panEl = active.querySelector('.js-card-pan');
        const expEl = active.querySelector('.js-card-exp');
        const nameEl = active.querySelector('.js-card-name');

        if (panEl) panEl.textContent = active.getAttribute(revealed ? 'data-pan-full' : 'data-pan-hidden') || '';
        if (expEl) expEl.textContent = active.getAttribute(revealed ? 'data-exp-full' : 'data-exp-hidden') || '';
        if (nameEl) nameEl.textContent = active.getAttribute(revealed ? 'data-name-full' : 'data-name-hidden') || '';

        if (revealBtn) {
            revealBtn.innerHTML = revealed ? '<i class="las la-eye-slash"></i>' : '<i class="las la-eye"></i>';
            revealBtn.setAttribute('title', revealed ? 'Hide card info' : 'Show card info');
        }
    };

    const showIndex = (nextIndex) => {
        wraps[index].classList.remove('is-active', 'is-flipped');
        index = (nextIndex + wraps.length) % wraps.length;
        wraps[index].classList.add('is-active');
        revealed = false;

        const cardId = wraps[index].getAttribute('data-card-id');
        const cardBalance = wraps[index].getAttribute('data-balance') || '';
        if (fundCardId) fundCardId.value = cardId;
        if (fundForm) fundForm.action = '{{ url("merchant/virtual-cards") }}/' + cardId + '/fund';
        if (activeCardBalance) activeCardBalance.textContent = cardBalance;
        applySensitiveView();
    };

    document.getElementById('vcPrev')?.addEventListener('click', () => showIndex(index - 1));
    document.getElementById('vcNext')?.addEventListener('click', () => showIndex(index + 1));
    document.getElementById('vcFlip')?.addEventListener('click', () => wraps[index].classList.toggle('is-flipped'));
    addBalanceBtn?.addEventListener('click', () => addBalanceModal?.show());
    amountInput?.addEventListener('input', recalcPreview);
    revealBtn?.addEventListener('click', () => {
        revealed = !revealed;
        applySensitiveView();
    });
    applySensitiveView();
    recalcPreview();
})();
</script>
@endpush
