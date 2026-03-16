@csrf
<div class="form-group">
    <label>@lang('Title')</label>
    <input type="text" name="title" class="form--control"
           value="{{ old('title', $paymentLink->title ?? '') }}" required>
</div>
<div class="form-group">
    <label>@lang('Currency')</label>
    <select name="currency" class="form--control form-select" required>
        @foreach($currencies as $currency)
            <option value="{{ $currency }}"
                @selected(old('currency', $paymentLink->currency ?? 'USD') === $currency)>
                {{ $currency }}
            </option>
        @endforeach
    </select>
</div>
<div class="form-group">
    <label>@lang('Amount')</label>
    <input type="number" step="0.01" name="amount" class="form--control"
           value="{{ old('amount', $paymentLink->amount ?? '') }}" required>
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
    @php
        $noExpiryChecked = old('no_expiry', isset($paymentLink) && !$paymentLink->expires_at ? 1 : 0);
    @endphp
    <input type="datetime-local" name="expires_at" id="expires_at_input" class="form--control"
           value="{{ old('expires_at', isset($paymentLink) && $paymentLink->expires_at ? $paymentLink->expires_at->format('Y-m-d\\TH:i') : '') }}">
    <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" id="no_expiry" name="no_expiry" value="1" {{ $noExpiryChecked ? 'checked' : '' }}>
        <label class="form-check-label" for="no_expiry">
            @lang('No expiry (never expires)')
        </label>
    </div>
</div>
<div class="form-group">
    <div class="form-check mt-2">
        <input class="form-check-input" type="checkbox" id="is_reusable" name="is_reusable" value="1" {{ old('is_reusable', isset($paymentLink) && $paymentLink->allowsMultiplePayments() ? 1 : 0) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_reusable">
            @lang('Allow multiple payments (reusable link)')
        </label>
    </div>
</div>
<button type="submit" class="btn btn--base w-100">{{ $buttonText }}</button>

@push('script')
<script>
    (function () {
        const noExpiry = document.getElementById('no_expiry');
        const expiresInput = document.getElementById('expires_at_input');

        if (!noExpiry || !expiresInput) {
            return;
        }

        const syncExpiryInputState = () => {
            const disabled = noExpiry.checked;
            expiresInput.disabled = disabled;
            if (disabled) {
                expiresInput.removeAttribute('required');
                expiresInput.value = '';
            } else {
                expiresInput.setAttribute('required', 'required');
            }
        };

        noExpiry.addEventListener('change', syncExpiryInputState);
        syncExpiryInputState();
    })();
</script>
@endpush
