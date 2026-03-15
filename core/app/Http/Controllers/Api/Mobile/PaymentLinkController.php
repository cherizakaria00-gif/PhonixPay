<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Resources\Mobile\PaymentLinkResource;
use App\Models\PaymentLink;
use App\Services\PlanService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentLinkController extends ApiMobileController
{
    public function __construct(private readonly PlanService $planService)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = PaymentLink::query()
            ->where('user_id', $request->user()->id)
            ->withCount('deposits')
            ->latest('id');

        if (Schema::hasColumn('payment_links', 'link_type')) {
            $query->where('link_type', PaymentLink::TYPE_STANDARD);
        }

        /** @var LengthAwarePaginator $links */
        $links = $query->paginate((int) $request->input('per_page', 10));
        $links->getCollection()->each->markExpiredIfNeeded();

        return $this->ok([
            'data' => PaymentLinkResource::collection($links->getCollection())->resolve(),
            'meta' => $this->meta($links),
        ]);
    }

    public function show(Request $request, int $id)
    {
        $paymentLink = $this->merchantLink($request->user()->id, $id)->withCount('deposits')->firstOrFail();
        $paymentLink->markExpiredIfNeeded();

        return $this->ok([
            'payment_link' => (new PaymentLinkResource($paymentLink))->resolve(),
        ]);
    }

    public function store(Request $request)
    {
        if ($response = $this->ensureFeatureAccess($request->user())) {
            return $response;
        }

        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required|string|max:10',
            'description' => 'required|string|max:255',
            'redirect_url' => 'nullable|url|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $link = new PaymentLink();
        $link->user_id = $request->user()->id;
        $link->code = $this->generateCode();
        $link->amount = (float) $request->amount;
        $link->currency = strtoupper((string) $request->currency);
        $link->description = (string) $request->description;
        $link->redirect_url = $request->redirect_url;
        $link->expires_at = $request->expires_at;
        $link->status = PaymentLink::STATUS_ACTIVE;
        if (Schema::hasColumn('payment_links', 'is_reusable')) {
            $link->is_reusable = false;
        }
        if (Schema::hasColumn('payment_links', 'link_type')) {
            $link->link_type = PaymentLink::TYPE_STANDARD;
        }
        $link->save();

        return $this->ok([
            'success' => true,
            'message' => 'Payment link created successfully',
            'payment_link' => (new PaymentLinkResource($link->loadCount('deposits')))->resolve(),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        if ($response = $this->ensureFeatureAccess($request->user())) {
            return $response;
        }

        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required|string|max:10',
            'description' => 'required|string|max:255',
            'redirect_url' => 'nullable|url|max:255',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $link = $this->merchantLink($request->user()->id, $id)->firstOrFail();
        $link->markExpiredIfNeeded();

        if ((int) $link->status !== PaymentLink::STATUS_ACTIVE) {
            return $this->ok([
                'success' => false,
                'message' => 'Only active payment links can be updated',
            ], 422);
        }

        $link->amount = (float) $request->amount;
        $link->currency = strtoupper((string) $request->currency);
        $link->description = (string) $request->description;
        $link->redirect_url = $request->redirect_url;
        $link->expires_at = $request->expires_at;
        $link->save();

        return $this->ok([
            'success' => true,
            'message' => 'Payment link updated successfully',
            'payment_link' => (new PaymentLinkResource($link->loadCount('deposits')))->resolve(),
        ]);
    }

    public function toggle(Request $request, int $id)
    {
        $link = $this->merchantLink($request->user()->id, $id)->firstOrFail();
        $link->markExpiredIfNeeded();

        if ((int) $link->status === PaymentLink::STATUS_EXPIRED || (int) $link->status === PaymentLink::STATUS_PAID) {
            return $this->ok([
                'success' => false,
                'message' => 'Only active or disabled links can be toggled.',
            ], 422);
        }

        $link->status = (int) $link->status === PaymentLink::STATUS_ACTIVE
            ? PaymentLink::STATUS_DISABLED
            : PaymentLink::STATUS_ACTIVE;
        $link->save();

        return $this->ok([
            'success' => true,
            'message' => 'Payment link status updated successfully',
            'payment_link' => (new PaymentLinkResource($link->loadCount('deposits')))->resolve(),
        ]);
    }

    private function merchantLink(int $userId, int $id)
    {
        $query = PaymentLink::query()->where('user_id', $userId)->where('id', $id);
        if (Schema::hasColumn('payment_links', 'link_type')) {
            $query->where('link_type', PaymentLink::TYPE_STANDARD);
        }

        return $query;
    }

    private function generateCode(): string
    {
        do {
            $code = Str::upper(Str::random(32));
        } while (PaymentLink::query()->where('code', $code)->exists());

        return $code;
    }

    private function ensureFeatureAccess($user)
    {
        if (!Schema::hasTable('plans')) {
            return null;
        }

        if ($this->planService->isFeatureEnabled($user, 'payment_links')) {
            return null;
        }

        return $this->ok([
            'success' => false,
            'message' => 'Payment Links are not available on your current plan. Please upgrade to continue.',
        ], 422);
    }
}
