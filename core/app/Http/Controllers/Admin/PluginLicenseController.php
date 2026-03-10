<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PluginLicense;
use App\Models\PluginLicenseValidation;
use App\Models\User;
use App\Services\PluginLicenseService;
use Illuminate\Http\Request;

class PluginLicenseController extends Controller
{
    public function __construct(private readonly PluginLicenseService $licenseService)
    {
    }

    public function index(Request $request)
    {
        $pageTitle = 'Plugin Licenses';
        $search = trim((string) $request->search);

        $licenses = PluginLicense::query()
            ->with(['merchant', 'latestValidation'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('license_key', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('domain', 'like', '%' . $search . '%')
                        ->orWhere('normalized_domain', 'like', '%' . $search . '%')
                        ->orWhereHas('merchant', function ($merchant) use ($search) {
                            $merchant->where('username', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest('id')
            ->paginate(getPaginate());

        return view('admin.plugin_licenses.index', compact('pageTitle', 'licenses'));
    }

    public function show($id)
    {
        $license = PluginLicense::with('merchant')->findOrFail($id);
        $pageTitle = 'License Details';
        $history = PluginLicenseValidation::where('plugin_license_id', $license->id)
            ->latest('id')
            ->paginate(getPaginate());

        return view('admin.plugin_licenses.show', compact('pageTitle', 'license', 'history'));
    }

    public function revoke($id, Request $request)
    {
        $license = PluginLicense::findOrFail($id);
        $result = $this->licenseService->revokeLicense($license, auth('admin')->id(), $request, 'Revoked by admin');

        if (!($result['ok'] ?? false)) {
            $notify[] = ['error', $result['message'] ?? 'Unable to revoke license'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'License revoked successfully'];
        return back()->withNotify($notify);
    }

    public function store(Request $request)
    {
        $request->validate([
            'merchant' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'domain' => 'required|string|max:255',
            'plugin_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $merchantRaw = trim((string) $request->merchant);
        $merchant = User::query()
            ->where('id', $merchantRaw)
            ->orWhere('username', $merchantRaw)
            ->orWhere('email', $merchantRaw)
            ->first();

        if (!$merchant) {
            $notify[] = ['error', 'Merchant not found'];
            return back()->withNotify($notify);
        }

        $result = $this->licenseService->createLicense($merchant, $request->only(['email', 'domain', 'plugin_name', 'notes']), $request);
        if (!($result['ok'] ?? false)) {
            $notify[] = ['error', $result['message'] ?? 'Unable to create license'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'License created successfully'];
        return back()->withNotify($notify);
    }

    public function updateDomain($id, Request $request)
    {
        $request->validate([
            'domain' => 'required|string|max:255',
        ]);

        $license = PluginLicense::findOrFail($id);
        $result = $this->licenseService->updateDomain($license, $request->domain, auth('admin')->id(), $request);
        if (!($result['ok'] ?? false)) {
            $notify[] = ['error', $result['message'] ?? 'Unable to update domain'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'License domain updated successfully'];
        return back()->withNotify($notify);
    }

    public function delete($id, Request $request)
    {
        $license = PluginLicense::findOrFail($id);
        $this->licenseService->deleteLicense($license, auth('admin')->id(), $request);

        $notify[] = ['success', 'License deleted successfully'];
        return to_route('admin.plugin.licenses.index')->withNotify($notify);
    }
}
