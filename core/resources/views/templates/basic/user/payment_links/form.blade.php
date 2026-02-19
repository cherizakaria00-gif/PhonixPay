@csrf
<div class="form-group">
    <label>@lang('Amount')</label>
    <div class="input-group">
        <span class="input-group-text">USD</span>
        <input type="number" step="0.01" name="amount" class="form--control"
               value="{{ old('amount', $paymentLink->amount ?? '') }}" required>
    </div>
</div>
<div class="form-group">
    <label>@lang('Description')</label>
    <input type="text" name="description" class="form--control"
           value="{{ old('description', $paymentLink->description ?? '') }}" required>
</div>
<div class="form-group">
    <label>@lang('Redirect URL')</label>
    <input type="url" name="redirect_url" class="form--control"
           value="{{ old('redirect_url', $paymentLink->redirect_url ?? '') }}" required>
</div>
<div class="form-group">
    <label>@lang('Expiration')</label>
    <input type="datetime-local" name="expires_at" class="form--control"
           value="{{ old('expires_at', isset($paymentLink) && $paymentLink->expires_at ? $paymentLink->expires_at->format('Y-m-d\\TH:i') : '') }}" required>
</div>
<button type="submit" class="btn btn--base w-100">{{ $buttonText }}</button>
