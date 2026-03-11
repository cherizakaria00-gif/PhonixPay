<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PluginLicense;
use App\Services\PluginLicenseService;
use Illuminate\Http\Request;

class PluginLicenseController extends Controller
{
    public function __construct(private readonly PluginLicenseService $licenseService)
    {
        parent::__construct();
    }

    public function index()
    {
        $pageTitle = 'Plugin Licenses';
        $this->licenseService->enforceSingleCurrentLicense(auth()->user());

        $currentLicense = $this->licenseService->merchantCurrentLicense(auth()->user());
        $licenses = PluginLicense::query()
            ->where('merchant_id', auth()->id())
            ->with('latestValidation')
            ->latest('id')
            ->paginate(getPaginate());

        return view('Template::user.developer.plugin_licenses.index', compact('pageTitle', 'licenses', 'currentLicense'));
    }

    public function store(Request $request)
    {
        if ($this->licenseService->merchantCurrentLicense(auth()->user())) {
            $notify[] = ['error', 'You can only have one active license. Contact admin to change the URL.'];
            return back()->withNotify($notify);
        }

        $request->validate([
            'email' => 'required|email|max:191',
            'domain' => 'required|string|max:255',
        ]);

        $result = $this->licenseService->createLicense(auth()->user(), $request->only(['email', 'domain']), $request);

        if (!($result['ok'] ?? false)) {
            $notify[] = ['error', $result['message'] ?? 'Unable to generate license'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'License generated successfully'];
        return back()->withNotify($notify);
    }

    public function revoke($id, Request $request)
    {
        $license = PluginLicense::where('merchant_id', auth()->id())->findOrFail($id);
        $result = $this->licenseService->revokeLicense($license, auth()->id(), $request, 'Revoked by merchant');

        if (!($result['ok'] ?? false)) {
            $notify[] = ['error', $result['message'] ?? 'Unable to revoke license'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'License revoked successfully'];
        return back()->withNotify($notify);
    }

    public function regenerate($id, Request $request)
    {
        $license = PluginLicense::where('merchant_id', auth()->id())->findOrFail($id);
        $result = $this->licenseService->regenerateLicense($license, auth()->id(), $request);

        if (!($result['ok'] ?? false)) {
            $notify[] = ['error', $result['message'] ?? 'Unable to regenerate license'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'License regenerated successfully'];
        return back()->withNotify($notify);
    }
}
