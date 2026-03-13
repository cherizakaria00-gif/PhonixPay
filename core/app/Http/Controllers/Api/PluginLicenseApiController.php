<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PluginLicense;
use App\Services\PluginLicenseService;
use Illuminate\Http\Request;

class PluginLicenseApiController extends Controller
{
    public function __construct(private readonly PluginLicenseService $licenseService)
    {
    }

    public function generate(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:191',
            'domain' => 'required|string|max:255',
            'plugin_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'public_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
        ]);

        $merchant = $this->licenseService->authenticateMerchantByApiKeys($request->public_key, $request->secret_key);
        if (!$merchant) {
            return response()->json([
                'valid' => false,
                'message' => 'Unauthorized API keys',
                'plugin_access' => 'denied',
            ], 401);
        }

        $result = $this->licenseService->createLicense($merchant, $request->only(['email', 'domain', 'plugin_name', 'notes']), $request);
        if (!($result['ok'] ?? false)) {
            return response()->json([
                'valid' => false,
                'message' => $result['message'] ?? 'Unable to generate license',
                'plugin_access' => 'denied',
            ], 422);
        }

        $license = $result['license'];

        return response()->json([
            'valid' => true,
            'status' => $license->status,
            'message' => 'License generated successfully',
            'merchant_id' => $license->merchant_id,
            'normalized_domain' => $license->normalized_domain,
            'license_key' => $license->license_key,
            'plugin_access' => 'granted',
        ]);
    }

    public function validateLicense(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'domain' => 'nullable|string|max:255',
            'site_url' => 'nullable|string|max:255',
            'plugin_version' => 'nullable|string|max:50',
        ]);

        $result = $this->licenseService->validateLicense($request->only([
            'license_key',
            'email',
            'domain',
            'site_url',
            'plugin_version',
        ]), $request);

        return response()->json($result, ($result['valid'] ?? false) ? 200 : 422);
    }

    public function revoke(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|max:191',
            'public_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
        ]);

        $merchant = $this->licenseService->authenticateMerchantByApiKeys($request->public_key, $request->secret_key);
        if (!$merchant) {
            return response()->json([
                'valid' => false,
                'message' => 'Unauthorized API keys',
                'plugin_access' => 'denied',
            ], 401);
        }

        $license = PluginLicense::where('license_key', strtoupper(trim((string) $request->license_key)))
            ->where('merchant_id', $merchant->id)
            ->first();

        if (!$license) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid license key',
                'plugin_access' => 'denied',
            ], 404);
        }

        $result = $this->licenseService->revokeLicense($license, $merchant->id, $request, 'Revoked via API');
        if (!($result['ok'] ?? false)) {
            return response()->json([
                'valid' => false,
                'message' => $result['message'] ?? 'Unable to revoke license',
                'plugin_access' => 'denied',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'status' => PluginLicense::STATUS_REVOKED,
            'message' => 'License revoked',
            'merchant_id' => $merchant->id,
            'normalized_domain' => $license->normalized_domain,
            'plugin_access' => 'denied',
        ]);
    }

    public function regenerate(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|max:191',
            'public_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
        ]);

        $merchant = $this->licenseService->authenticateMerchantByApiKeys($request->public_key, $request->secret_key);
        if (!$merchant) {
            return response()->json([
                'valid' => false,
                'message' => 'Unauthorized API keys',
                'plugin_access' => 'denied',
            ], 401);
        }

        $license = PluginLicense::where('license_key', strtoupper(trim((string) $request->license_key)))
            ->where('merchant_id', $merchant->id)
            ->first();

        if (!$license) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid license key',
                'plugin_access' => 'denied',
            ], 404);
        }

        $result = $this->licenseService->regenerateLicense($license, $merchant->id, $request);
        if (!($result['ok'] ?? false)) {
            return response()->json([
                'valid' => false,
                'message' => $result['message'] ?? 'Unable to regenerate license',
                'plugin_access' => 'denied',
            ], 422);
        }

        /** @var PluginLicense $newLicense */
        $newLicense = $result['license'];

        return response()->json([
            'valid' => true,
            'status' => $newLicense->status,
            'message' => 'License regenerated successfully',
            'merchant_id' => $newLicense->merchant_id,
            'normalized_domain' => $newLicense->normalized_domain,
            'license_key' => $newLicense->license_key,
            'plugin_access' => 'granted',
        ]);
    }

    public function details(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|max:191',
            'public_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
        ]);

        $merchant = $this->licenseService->authenticateMerchantByApiKeys($request->public_key, $request->secret_key);
        if (!$merchant) {
            return response()->json([
                'valid' => false,
                'message' => 'Unauthorized API keys',
                'plugin_access' => 'denied',
            ], 401);
        }

        $license = PluginLicense::where('license_key', strtoupper(trim((string) $request->license_key)))
            ->where('merchant_id', $merchant->id)
            ->first();

        if (!$license) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid license key',
                'plugin_access' => 'denied',
            ], 404);
        }

        $this->licenseService->storeAudit($request, [
            'license' => $license,
            'merchant_id' => $merchant->id,
            'action' => 'details',
            'result' => 'success',
            'message' => 'License details fetched',
            'request_email' => $license->email,
            'request_domain' => $license->domain,
            'normalized_domain' => $license->normalized_domain,
        ]);

        return response()->json([
            'valid' => true,
            'status' => $license->status,
            'message' => 'License details loaded',
            'merchant_id' => $license->merchant_id,
            'normalized_domain' => $license->normalized_domain,
            'plugin_access' => $license->isActive() ? 'granted' : 'denied',
            'data' => [
                'email' => $license->email,
                'domain' => $license->domain,
                'plugin_name' => $license->plugin_name,
                'plugin_version_last_seen' => $license->plugin_version_last_seen,
                'last_validated_at' => optional($license->last_validated_at)->toIso8601String(),
                'activations_count' => (int) $license->activations_count,
            ],
        ]);
    }

    /**
     * Legacy endpoint used by WooCommerce plugin:
     * POST /api/licenses/validate
     */
    public function validateLegacy(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'domain' => 'nullable|string|max:255',
            'site_url' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:50',
        ]);

        $result = $this->licenseService->validateLicense([
            'license_key' => $request->input('license_key'),
            'email' => $request->input('email'),
            'domain' => $request->input('domain'),
            'site_url' => $request->input('site_url'),
            'plugin_version' => $request->input('version'),
        ], $request);

        $license = PluginLicense::query()
            ->where('license_key', strtoupper(trim((string) $request->input('license_key'))))
            ->first();

        $response = [
            'success' => (bool) ($result['valid'] ?? false),
            'status' => ($result['valid'] ?? false) ? 'active' : 'invalid',
            'code' => ($result['valid'] ?? false) ? 'valid' : $this->legacyCodeFromMessage((string) ($result['message'] ?? 'validation_failed')),
            'message' => (string) ($result['message'] ?? 'License validation failed'),
            'data' => [
                'email' => $license?->email ?: strtolower(trim((string) $request->input('email'))),
                'site_url' => (string) $request->input('site_url', ''),
                'domain' => $license?->normalized_domain ?: $this->licenseService->normalizeDomain((string) ($request->input('domain') ?: $request->input('site_url'))),
                'license_status' => (string) ($result['status'] ?? 'invalid'),
            ],
        ];

        return response()->json($response, ($result['valid'] ?? false) ? 200 : 422);
    }

    /**
     * Legacy endpoint used by WooCommerce plugin:
     * POST /api/licenses/deactivate
     */
    public function deactivateLegacy(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'domain' => 'nullable|string|max:255',
            'site_url' => 'nullable|string|max:255',
        ]);

        $license = PluginLicense::query()
            ->where('license_key', strtoupper(trim((string) $request->input('license_key'))))
            ->first();

        if (!$license) {
            return response()->json([
                'success' => false,
                'status' => 'invalid',
                'code' => 'invalid_license',
                'message' => 'Invalid license key',
                'data' => [],
            ], 404);
        }

        if (!hash_equals(strtolower((string) $license->email), strtolower(trim((string) $request->input('email'))))) {
            return response()->json([
                'success' => false,
                'status' => 'invalid',
                'code' => 'email_mismatch',
                'message' => 'Email mismatch',
                'data' => [
                    'email' => $license->email,
                    'domain' => $license->normalized_domain,
                    'license_status' => $license->status,
                ],
            ], 422);
        }

        if ($license->status !== PluginLicense::STATUS_REVOKED) {
            $license->status = PluginLicense::STATUS_INACTIVE;
            $license->save();
        }

        $this->licenseService->storeAudit($request, [
            'license' => $license,
            'merchant_id' => $license->merchant_id,
            'action' => 'deactivate',
            'result' => 'success',
            'message' => 'License deactivated via legacy endpoint',
            'request_email' => $request->input('email'),
            'request_domain' => $request->input('domain') ?: $request->input('site_url'),
            'normalized_domain' => $this->licenseService->normalizeDomain((string) ($request->input('domain') ?: $request->input('site_url'))),
            'site_url' => $request->input('site_url'),
        ]);

        return response()->json([
            'success' => true,
            'status' => 'inactive',
            'code' => 'deactivated',
            'message' => 'License deactivated successfully',
            'data' => [
                'email' => $license->email,
                'domain' => $license->normalized_domain,
                'license_status' => $license->status,
            ],
        ]);
    }

    /**
     * Legacy endpoint used by WooCommerce plugin:
     * POST /api/licenses/details
     */
    public function detailsLegacy(Request $request)
    {
        $request->validate([
            'license_key' => 'required|string|max:191',
            'email' => 'nullable|email|max:191',
            'domain' => 'nullable|string|max:255',
            'site_url' => 'nullable|string|max:255',
        ]);

        $license = PluginLicense::query()
            ->where('license_key', strtoupper(trim((string) $request->input('license_key'))))
            ->first();

        if (!$license) {
            return response()->json([
                'success' => false,
                'status' => 'invalid',
                'code' => 'invalid_license',
                'message' => 'Invalid license key',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'status' => $license->status,
            'code' => 'ok',
            'message' => 'License details loaded',
            'data' => [
                'email' => $license->email,
                'site_url' => $request->input('site_url', ''),
                'domain' => $license->normalized_domain,
                'license_status' => $license->status,
                'plugin_version_last_seen' => $license->plugin_version_last_seen,
                'last_validated_at' => optional($license->last_validated_at)->toIso8601String(),
            ],
        ]);
    }

    private function legacyCodeFromMessage(string $message): string
    {
        $message = strtolower(trim($message));

        if (str_contains($message, 'domain')) {
            return 'domain_mismatch';
        }
        if (str_contains($message, 'email')) {
            return 'email_mismatch';
        }
        if (str_contains($message, 'revoked')) {
            return 'revoked';
        }
        if (str_contains($message, 'expired')) {
            return 'expired';
        }

        return 'validation_failed';
    }
}
