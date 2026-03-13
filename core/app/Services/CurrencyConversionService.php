<?php

namespace App\Services;

use App\Models\CurrencyConversionRate;
use Illuminate\Support\Facades\Schema;

class CurrencyConversionService
{
    protected ?bool $ratesTableExists = null;

    public function baseCurrency(): string
    {
        $baseCurrency = strtoupper(trim((string) gs('cur_text')));
        return $baseCurrency !== '' ? $baseCurrency : 'USD';
    }

    public function getRate(string $baseCurrency, string $quoteCurrency): ?float
    {
        [$baseCurrency, $quoteCurrency] = $this->normalizePair($baseCurrency, $quoteCurrency);

        if ($baseCurrency === '' || $quoteCurrency === '') {
            return null;
        }

        if ($baseCurrency === $quoteCurrency) {
            return 1.0;
        }

        if (!$this->hasRatesTable()) {
            return null;
        }

        $rate = CurrencyConversionRate::query()
            ->active()
            ->where('base_currency', $baseCurrency)
            ->where('quote_currency', $quoteCurrency)
            ->value('rate');

        return $this->toPositiveFloat($rate);
    }

    public function getCrossRate(string $fromCurrency, string $toCurrency): ?float
    {
        [$fromCurrency, $toCurrency] = $this->normalizePair($fromCurrency, $toCurrency);

        if ($fromCurrency === '' || $toCurrency === '') {
            return null;
        }

        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $direct = $this->getRate($fromCurrency, $toCurrency);
        if ($direct !== null) {
            return $direct;
        }

        $inverse = $this->getRate($toCurrency, $fromCurrency);
        if ($inverse !== null && $inverse > 0) {
            return 1 / $inverse;
        }

        $anchor = $this->baseCurrency();
        if ($anchor === $fromCurrency || $anchor === $toCurrency) {
            return null;
        }

        $anchorToFrom = $this->getRate($anchor, $fromCurrency);
        $anchorToTo = $this->getRate($anchor, $toCurrency);
        if ($anchorToFrom === null || $anchorToTo === null || $anchorToFrom <= 0) {
            return null;
        }

        return $anchorToTo / $anchorToFrom;
    }

    protected function normalizePair(string $baseCurrency, string $quoteCurrency): array
    {
        return [
            strtoupper(trim($baseCurrency)),
            strtoupper(trim($quoteCurrency)),
        ];
    }

    protected function hasRatesTable(): bool
    {
        if ($this->ratesTableExists !== null) {
            return $this->ratesTableExists;
        }

        $this->ratesTableExists = Schema::hasTable('currency_conversion_rates');
        return $this->ratesTableExists;
    }

    protected function toPositiveFloat($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return null;
            }
            $value = str_replace(',', '.', $value);
        }

        if (!is_numeric($value)) {
            return null;
        }

        $normalized = (float) $value;
        return $normalized > 0 ? $normalized : null;
    }
}
