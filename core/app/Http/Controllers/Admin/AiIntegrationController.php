<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiIntegration;
use Illuminate\Http\Request;

class AiIntegrationController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'AI Integrations';
        $search = trim((string) $request->search);
        $option = trim((string) $request->option);
        $status = trim((string) $request->status);

        $integrations = AiIntegration::query()
            ->with(['merchant', 'paymentLink'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('merchant_email', 'like', '%' . $search . '%')
                        ->orWhere('website_url', 'like', '%' . $search . '%')
                        ->orWhere('license_key', 'like', '%' . $search . '%')
                        ->orWhere('payment_link_url', 'like', '%' . $search . '%')
                        ->orWhereHas('merchant', function ($merchant) use ($search) {
                            $merchant->where('username', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($option !== '', fn ($query) => $query->where('selected_option', $option))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(getPaginate());

        return view('admin.ai_integrations.index', compact('pageTitle', 'integrations', 'option', 'status'));
    }
}
