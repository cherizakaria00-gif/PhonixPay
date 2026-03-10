<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PluginLicense;
use App\Models\PluginLicenseValidation;
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
}
