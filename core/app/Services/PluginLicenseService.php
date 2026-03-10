<?php

namespace App\Services;

use App\Models\PluginLicense;
use App\Models\PluginLicenseValidation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PluginLicenseService
{
    public function normalizeDomain(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Ensure parse_url can always resolve host.
        if (!preg_match('#^https?://#i', $value)) {
            $value = 'https://' . ltrim($value, '/');
        }

        $host = parse_url($value, PHP_URL_HOST);
        if (!is_string($host) || trim($host) === '') {
            return null;
        }

        $host = strtolower(trim($host));
        $host = trim($host, '.');
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        if (function_exists('idn_to_ascii')) {
            $idnaDefault = defined('IDNA_DEFAULT') ? IDNA_DEFAULT : 0;
            $idnaVariant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : (defined('INTL_IDNA_VARIANT_2003') ? INTL_IDNA_VARIANT_2003 : 0);
            try {
                $ascii = idn_to_ascii($host, $idnaDefault, $idnaVariant);
                if (is_string($ascii) && $ascii !== '') {
                    $host = strtolower($ascii);
                }
            } catch (\Throwable) {
                // Keep original host when IDN conversion fails.
            }
        }

        if ($host === '' || str_contains($host, '/')) {
            return null;
        }

        return $host;
    }

    public function generateLicenseKey(): string
    {
        for ($i = 0; $i < 20; $i++) {
            $raw = strtoupper(Str::random(16));
            $chunks = str_split($raw, 4);
            $key = 'FLP-' . implode('-', $chunks);

            if (!PluginLicense::where('license_key', $key)->exists()) {
                return $key;
            }
        }

        return 'FLP-' . strtoupper(Str::uuid()->toString());
    }

    public function createLicense(User $merchant, array $payload, ?Request $request = null): array
    {
        $email = strtolower(trim((string) ($payload['email'] ?? $merchant->email ?? '')));
        $domainInput = (string) ($payload['domain'] ?? '');
        $normalizedDomain = $this->normalizeDomain($domainInput);
        $pluginName = trim((string) ($payload['plugin_name'] ?? 'flujipay-woocommerce'));
        if ($pluginName === '') {
            $pluginName = 'flujipay-woocommerce';
        }

        if (!$normalizedDomain) {
            return ['ok' => false, 'message' => 'Invalid domain format'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'message' => 'Invalid email'];
        }

        $existing = PluginLicense::query()
            ->where('merchant_id', $merchant->id)
            ->where('normalized_domain', $normalizedDomain)
            ->where('plugin_name', $pluginName)
            ->whereIn('status', [PluginLicense::STATUS_ACTIVE, PluginLicense::STATUS_INACTIVE])
            ->first();

        if ($existing) {
            return ['ok' => false, 'message' => 'A license already exists for this domain and plugin'];
        }

        $license = PluginLicense::create([
            'merchant_id' => $merchant->id,
            'email' => $email,
            'domain' => $domainInput,
            'normalized_domain' => $normalizedDomain,
            'license_key' => $this->generateLicenseKey(),
            'status' => PluginLicense::STATUS_ACTIVE,
            'plugin_name' => $pluginName,
            'notes' => $payload['notes'] ?? null,
        ]);

        $this->storeAudit($request, [
            'license' => $license,
            'merchant_id' => $merchant->id,
            'action' => 'generate',
            'result' => 'success',
            'message' => 'License generated successfully',
            'request_email' => $email,
            'request_domain' => $domainInput,
            'normalized_domain' => $normalizedDomain,
            'context' => ['source' => 'merchant_dashboard'],
        ]);

        return ['ok' => true, 'license' => $license];
    }

    public function revokeLicense(PluginLicense $license, ?int $actorId = null, ?Request $request = null, string $reason = 'Revoked'): array
    {
        if ($license->status === PluginLicense::STATUS_REVOKED) {
            return ['ok' => false, 'message' => 'License already revoked'];
        }

        $license->status = PluginLicense::STATUS_REVOKED;
        $license->revoked_at = now();
        $license->revoked_by = $actorId;
        $license->save();

        $this->storeAudit($request, [
            'license' => $license,
            'merchant_id' => $license->merchant_id,
            'action' => 'revoke',
            'result' => 'success',
            'message' => $reason,
            'request_email' => $license->email,
            'request_domain' => $license->domain,
            'normalized_domain' => $license->normalized_domain,
            'context' => ['actor_id' => $actorId],
        ]);

        return ['ok' => true, 'license' => $license];
    }

    public function regenerateLicense(PluginLicense $license, ?int $actorId = null, ?Request $request = null): array
    {
        if ($license->status === PluginLicense::STATUS_REVOKED) {
            return ['ok' => false, 'message' => 'Cannot regenerate a revoked license'];
        }

        $this->revokeLicense($license, $actorId, $request, 'Regenerated');

        $newLicense = PluginLicense::create([
            'merchant_id' => $license->merchant_id,
            'email' => $license->email,
            'domain' => $license->domain,
            'normalized_domain' => $license->normalized_domain,
            'license_key' => $this->generateLicenseKey(),
            'status' => PluginLicense::STATUS_ACTIVE,
            'plugin_name' => $license->plugin_name,
            'notes' => $license->notes,
            'regenerated_from_id' => $license->id,
        ]);

        $this->storeAudit($request, [
            'license' => $newLicense,
            'merchant_id' => $newLicense->merchant_id,
            'action' => 'regenerate',
            'result' => 'success',
            'message' => 'License regenerated successfully',
            'request_email' => $newLicense->email,
            'request_domain' => $newLicense->domain,
            'normalized_domain' => $newLicense->normalized_domain,
            'context' => ['previous_license_id' => $license->id, 'actor_id' => $actorId],
        ]);

        return ['ok' => true, 'license' => $newLicense];
    }

    public function validateLicense(array $payload, ?Request $request = null): array
    {
        $licenseKey = strtoupper(trim((string) ($payload['license_key'] ?? '')));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $domainInput = trim((string) ($payload['domain'] ?? $payload['site_url'] ?? ''));
        $siteUrl = trim((string) ($payload['site_url'] ?? ''));
        $pluginVersion = trim((string) ($payload['plugin_version'] ?? ''));
        $normalizedDomain = $this->normalizeDomain($domainInput);

        $responseBase = [
            'status' => 'invalid',
            'merchant_id' => null,
            'normalized_domain' => $normalizedDomain,
            'plugin_access' => 'denied',
        ];

        $license = PluginLicense::where('license_key', $licenseKey)->first();

        if (!$license) {
            $this->storeAudit($request, [
                'license' => null,
                'merchant_id' => null,
                'action' => 'validate',
                'result' => 'failed',
                'message' => 'Invalid license key',
                'request_email' => $email,
                'request_domain' => $domainInput,
                'normalized_domain' => $normalizedDomain,
                'plugin_version' => $pluginVersion,
                'site_url' => $siteUrl,
            ]);

            return array_merge($responseBase, [
                'valid' => false,
                'message' => 'Invalid license key',
            ]);
        }

        $responseBase['merchant_id'] = $license->merchant_id;
        $responseBase['normalized_domain'] = $license->normalized_domain;

        if ($license->status !== PluginLicense::STATUS_ACTIVE) {
            $this->storeAudit($request, [
                'license' => $license,
                'merchant_id' => $license->merchant_id,
                'action' => 'validate',
                'result' => 'failed',
                'message' => 'License is not active',
                'request_email' => $email,
                'request_domain' => $domainInput,
                'normalized_domain' => $normalizedDomain,
                'plugin_version' => $pluginVersion,
                'site_url' => $siteUrl,
            ]);

            return array_merge($responseBase, [
                'valid' => false,
                'status' => $license->status,
                'message' => 'License is not active',
            ]);
        }

        if ($license->expires_at && $license->expires_at->isPast()) {
            $this->storeAudit($request, [
                'license' => $license,
                'merchant_id' => $license->merchant_id,
                'action' => 'validate',
                'result' => 'failed',
                'message' => 'License expired',
                'request_email' => $email,
                'request_domain' => $domainInput,
                'normalized_domain' => $normalizedDomain,
                'plugin_version' => $pluginVersion,
                'site_url' => $siteUrl,
            ]);

            return array_merge($responseBase, [
                'valid' => false,
                'status' => 'expired',
                'message' => 'License expired',
            ]);
        }

        if (!hash_equals(strtolower($license->email), $email)) {
            $this->storeAudit($request, [
                'license' => $license,
                'merchant_id' => $license->merchant_id,
                'action' => 'validate',
                'result' => 'failed',
                'message' => 'Email mismatch',
                'request_email' => $email,
                'request_domain' => $domainInput,
                'normalized_domain' => $normalizedDomain,
                'plugin_version' => $pluginVersion,
                'site_url' => $siteUrl,
            ]);

            return array_merge($responseBase, [
                'valid' => false,
                'message' => 'Email mismatch',
            ]);
        }

        if (!$normalizedDomain || !hash_equals($license->normalized_domain, $normalizedDomain)) {
            $this->storeAudit($request, [
                'license' => $license,
                'merchant_id' => $license->merchant_id,
                'action' => 'validate',
                'result' => 'failed',
                'message' => 'Domain mismatch',
                'request_email' => $email,
                'request_domain' => $domainInput,
                'normalized_domain' => $normalizedDomain,
                'plugin_version' => $pluginVersion,
                'site_url' => $siteUrl,
            ]);

            return array_merge($responseBase, [
                'valid' => false,
                'message' => 'Domain mismatch',
            ]);
        }

        $license->plugin_version_last_seen = $pluginVersion ?: $license->plugin_version_last_seen;
        $license->last_validated_at = now();
        $license->last_used_ip = $request?->ip();
        $license->activations_count = (int) $license->activations_count + 1;
        $license->save();

        $this->storeAudit($request, [
            'license' => $license,
            'merchant_id' => $license->merchant_id,
            'action' => 'validate',
            'result' => 'success',
            'message' => 'License activated successfully',
            'request_email' => $email,
            'request_domain' => $domainInput,
            'normalized_domain' => $normalizedDomain,
            'plugin_version' => $pluginVersion,
            'site_url' => $siteUrl,
            'context' => ['license_id' => $license->id],
        ]);

        return [
            'valid' => true,
            'status' => $license->status,
            'message' => 'License activated successfully',
            'merchant_id' => $license->merchant_id,
            'normalized_domain' => $license->normalized_domain,
            'plugin_access' => 'granted',
        ];
    }

    public function authenticateMerchantByApiKeys(?string $publicKey, ?string $secretKey): ?User
    {
        $publicKey = trim((string) $publicKey);
        $secretKey = trim((string) $secretKey);

        if ($publicKey === '' || $secretKey === '') {
            return null;
        }

        return User::query()
            ->where(function ($query) use ($publicKey) {
                $query->where('public_api_key', $publicKey)
                    ->orWhere('test_public_api_key', $publicKey);
            })
            ->where(function ($query) use ($secretKey) {
                $query->where('secret_api_key', $secretKey)
                    ->orWhere('test_secret_api_key', $secretKey);
            })
            ->first();
    }

    public function storeAudit(?Request $request, array $data): void
    {
        $license = $data['license'] ?? null;

        PluginLicenseValidation::create([
            'plugin_license_id' => $license?->id,
            'merchant_id' => $data['merchant_id'] ?? $license?->merchant_id,
            'action' => $data['action'] ?? 'validate',
            'result' => $data['result'] ?? 'failed',
            'message' => $data['message'] ?? null,
            'request_email' => $data['request_email'] ?? null,
            'request_domain' => $data['request_domain'] ?? null,
            'normalized_domain' => $data['normalized_domain'] ?? null,
            'plugin_version' => $data['plugin_version'] ?? null,
            'site_url' => $data['site_url'] ?? null,
            'ip' => $request?->ip(),
            'user_agent' => Str::limit((string) $request?->userAgent(), 255, ''),
            'context' => $data['context'] ?? null,
        ]);

        Log::info('Plugin license audit', [
            'action' => $data['action'] ?? 'validate',
            'result' => $data['result'] ?? 'failed',
            'message' => $data['message'] ?? null,
            'license_id' => $license?->id,
            'merchant_id' => $data['merchant_id'] ?? $license?->merchant_id,
            'request_email' => $data['request_email'] ?? null,
            'normalized_domain' => $data['normalized_domain'] ?? null,
            'ip' => $request?->ip(),
        ]);
    }
}
