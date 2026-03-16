<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\FormProcessor;
use App\Lib\GoogleAuthenticator;
use App\Models\Deposit;
use App\Models\DeviceToken;
use App\Models\Form;
use App\Models\Gateway;
use App\Models\GatewayCurrency;
use App\Models\NotificationLog;
use App\Models\PaymentLink;
use App\Models\Plan;
use App\Models\PlanChangeRequest;
use App\Models\PluginLicense;
use App\Models\Transaction;
use App\Models\Withdrawal;
use App\Services\PluginLicenseService;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function home(Request $request)
    {
        $pageTitle = 'Dashboard'; 
        $user = auth()->user();
        $selectedRangeDays = (int) $request->input('range', 7);
        if (!in_array($selectedRangeDays, [7, 14, 30], true)) {
            $selectedRangeDays = 7;
        }

        $compareEnabled = (bool) $request->boolean('compare', true);
        $granularity = 'daily';

        $planSummary = null;
        $availablePlans = collect();
        $pendingPlanRequest = null;

        if (Schema::hasTable('plans') && Schema::hasColumn('users', 'plan_id')) {
            /** @var PlanService $planService */
            $planService = app(PlanService::class);
            $planSummary = [
                'current' => $planService->getEffectivePlan($user),
                'usage' => $planService->usageSummary($user),
            ];

            $availablePlans = Plan::active()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if (Schema::hasTable('plan_change_requests')) {
                $pendingPlanRequest = PlanChangeRequest::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'pending')
                    ->with('toPlan')
                    ->latest('id')
                    ->first();
            }
        }

        $latestDeposits = Deposit::where('user_id', $user->id)
            ->where('status', Status::PAYMENT_SUCCESS)
            ->with('apiPayment')
            ->orderBy('id', 'desc')
            ->take(10);
        $latestTrx = Transaction::where('user_id', $user->id)->orderBy('id','desc')->take(10);

        $todayRevenue = (float) Deposit::where('user_id', $user->id)
            ->where('status', Status::PAYMENT_SUCCESS)
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');

        $yesterdayRevenue = (float) Deposit::where('user_id', $user->id)
            ->where('status', Status::PAYMENT_SUCCESS)
            ->whereDate('created_at', Carbon::yesterday())
            ->sum('amount');

        $rangeStart = Carbon::now()->subDays($selectedRangeDays - 1)->startOfDay();
        $rangeEnd = Carbon::now()->endOfDay();
        $previousRangeStart = (clone $rangeStart)->subDays($selectedRangeDays);
        $previousRangeEnd = (clone $rangeEnd)->subDays($selectedRangeDays);

        $monthGross = (float) Deposit::where('user_id', $user->id)
            ->where('status', Status::PAYMENT_SUCCESS)
            ->whereBetween('created_at', [$rangeStart, $rangeEnd])
            ->sum('amount');

        $previousMonthGross = (float) Deposit::where('user_id', $user->id)
            ->where('status', Status::PAYMENT_SUCCESS)
            ->whereBetween('created_at', [$previousRangeStart, $previousRangeEnd])
            ->sum('amount');

        $dailyChart = collect(range($selectedRangeDays - 1, 0))->map(function ($offset) use ($user) {
            $date = Carbon::today()->subDays($offset);
            return [
                'label' => $date->format('M d'),
                'amount' => (float) Deposit::where('user_id', $user->id)
                    ->where('status', Status::PAYMENT_SUCCESS)
                    ->whereDate('created_at', $date)
                    ->sum('amount'),
            ];
        })->values();

        $paymentLinkSeries = collect();
        $pluginDirectSeries = collect();
        $paymentLinkTotal = 0.0;
        $pluginDirectTotal = 0.0;

        if (Schema::hasColumn('deposits', 'integration_source_type')) {
            $paymentLinkSeries = collect(range($selectedRangeDays - 1, 0))->map(function ($offset) use ($user) {
                $date = Carbon::today()->subDays($offset);
                return [
                    'label' => $date->format('M d'),
                    'amount' => (float) Deposit::where('user_id', $user->id)
                        ->where('status', Status::PAYMENT_SUCCESS)
                        ->where('integration_source_type', 'payment_link')
                        ->whereDate('created_at', $date)
                        ->sum('amount'),
                ];
            })->values();

            $pluginDirectSeries = collect(range($selectedRangeDays - 1, 0))->map(function ($offset) use ($user) {
                $date = Carbon::today()->subDays($offset);
                return [
                    'label' => $date->format('M d'),
                    'amount' => (float) Deposit::where('user_id', $user->id)
                        ->where('status', Status::PAYMENT_SUCCESS)
                        ->whereIn('integration_source_type', ['plugin_sdk', 'plugin', 'woocommerce'])
                        ->whereDate('created_at', $date)
                        ->sum('amount'),
                ];
            })->values();

            $paymentLinkTotal = (float) Deposit::where('user_id', $user->id)
                ->where('status', Status::PAYMENT_SUCCESS)
                ->where('integration_source_type', 'payment_link')
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->sum('amount');

            $pluginDirectTotal = (float) Deposit::where('user_id', $user->id)
                ->where('status', Status::PAYMENT_SUCCESS)
                ->whereIn('integration_source_type', ['plugin_sdk', 'plugin', 'woocommerce'])
                ->whereBetween('created_at', [$rangeStart, $rangeEnd])
                ->sum('amount');
        } else {
            $paymentLinkSeries = collect(range($selectedRangeDays - 1, 0))->map(function ($offset) {
                return ['label' => Carbon::today()->subDays($offset)->format('M d'), 'amount' => 0.0];
            })->values();

            $pluginDirectSeries = collect(range($selectedRangeDays - 1, 0))->map(function ($offset) {
                return ['label' => Carbon::today()->subDays($offset)->format('M d'), 'amount' => 0.0];
            })->values();
        }

        $payoutAvailable = max(0, (float) ($user->balance ?? 0) * 0.7);
        $monthNet = max(0, $monthGross * 0.95);
        $previousMonthNet = max(0, $previousMonthGross * 0.95);
     
        if($request->export_type){
            return $latestDeposits->export();
        }
        $latestDeposits = $latestDeposits->get();
        $latestTrx = $latestTrx->get();
        return view('Template::user.dashboard', compact(
            'pageTitle',
            'user',
            'latestTrx',
            'latestDeposits',
            'todayRevenue',
            'yesterdayRevenue',
            'monthGross',
            'previousMonthGross',
            'monthNet',
            'previousMonthNet',
            'dailyChart',
            'paymentLinkSeries',
            'pluginDirectSeries',
            'paymentLinkTotal',
            'pluginDirectTotal',
            'selectedRangeDays',
            'compareEnabled',
            'granularity',
            'payoutAvailable',
            'planSummary',
            'availablePlans',
            'pendingPlanRequest'
        ));
    }

    public function notifications()
    {
        $pageTitle = 'Notifications';
        $notifications = NotificationLog::where('user_id', auth()->id())
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        return view('Template::user.notifications', compact('pageTitle', 'notifications'));
    }

    public function notificationRead($id)
    {
        $notification = NotificationLog::where('user_id', auth()->id())->findOrFail($id);

        if ((int)$notification->user_read === Status::NO) {
            $notification->user_read = Status::YES;
            $notification->save();
        }

        return back();
    }

    public function notificationReadAll()
    {
        NotificationLog::where('user_id', auth()->id())
            ->where('user_read', Status::NO)
            ->update(['user_read' => Status::YES]);

        $notify[] = ['success', 'All notifications marked as read'];
        return back()->withNotify($notify);
    }

    public function notificationPoll()
    {
        $userId = auth()->id();

        $notifications = NotificationLog::where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->take(8)
            ->get()
            ->map(function ($notification) {
                return [
                    'id'      => $notification->id,
                    'subject' => __($notification->subject ?: 'Notification'),
                    'message' => $this->notificationPreview($notification->message),
                    'time'    => diffForHumans($notification->created_at),
                    'unread'  => (int) $notification->user_read === Status::NO,
                    'url'     => route('user.notification.read', $notification->id),
                ];
            });

        $unreadCount = NotificationLog::where('user_id', $userId)
            ->where('user_read', Status::NO)
            ->count();

        return response()->json([
            'status'       => 'success',
            'unread_count' => $unreadCount,
            'notifications'=> $notifications,
        ]);
    }

    public function gatewaySetupFee()
    {
        $user = auth()->user();

        if ((int) $user->kv !== Status::KYC_VERIFIED) {
            $notify[] = ['warning', 'Please complete and verify your KYC before the setup fee step.'];
            return to_route('user.kyc.form')->withNotify($notify);
        }

        if (in_array(($user->setup_fee_status ?? 'unpaid'), ['pending_review', 'approved'], true)) {
            return to_route('user.gateway.setup.fee.status');
        }

        $paymentLink = $this->getOrCreateGatewaySetupFeeLink($user);

        $walletAddress = trim((string) env('GATEWAY_SETUP_FEE_USDT_TRC20_WALLET', env('GATEWAY_SETUP_FEE_BINANCE_WALLET', '')));
        $walletNetwork = strtoupper(trim((string) env('GATEWAY_SETUP_FEE_USDT_NETWORK', env('GATEWAY_SETUP_FEE_BINANCE_NETWORK', 'TRC20'))));
        if ($walletNetwork === '') {
            $walletNetwork = 'TRC20';
        }
        $pageTitle = 'Gateway Setup Fee';
        $countdownSeconds = max(0, now()->diffInSeconds($paymentLink->expires_at, false));
        $reviewWindowHours = 1;
        $reviewCountdownSeconds = null;

        if (($user->setup_fee_status ?? 'unpaid') === 'pending_review' && $user->setup_fee_submitted_at) {
            $reviewCountdownSeconds = max(
                0,
                now()->diffInSeconds($user->setup_fee_submitted_at->copy()->addHours($reviewWindowHours), false)
            );
        }

        $qrCodeUrl = $walletAddress ? cryptoQR($walletAddress) : null;

        return view('Template::user.gateway_setup_fee', compact(
            'pageTitle',
            'paymentLink',
            'walletAddress',
            'walletNetwork',
            'countdownSeconds',
            'reviewCountdownSeconds',
            'reviewWindowHours',
            'qrCodeUrl',
            'user'
        ));
    }

    public function confirmGatewaySetupFee(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = auth()->user();

        if ((int) $user->kv !== Status::KYC_VERIFIED) {
            $notify[] = ['warning', 'Please complete and verify your KYC before the setup fee step.'];
            return to_route('user.kyc.form')->withNotify($notify);
        }

        if (($user->setup_fee_status ?? 'unpaid') === 'approved') {
            return to_route('user.gateway.setup.fee.status');
        }

        if (($user->setup_fee_status ?? 'unpaid') === 'pending_review') {
            $notify[] = ['info', 'Your setup fee transaction is still processing.'];
            return to_route('user.gateway.setup.fee.status')->withNotify($notify);
        }

        $paymentLink = PaymentLink::query()
            ->where('user_id', $user->id)
            ->where('code', $request->code)
            ->where('description', 'Gateway setup fee')
            ->firstOrFail();

        if ($paymentLink->isExpired() || (int) $paymentLink->status !== PaymentLink::STATUS_ACTIVE) {
            $notify[] = ['error', 'This setup fee payment session has expired. Please generate a new one.'];
            return to_route('user.gateway.setup.fee')->withNotify($notify);
        }

        $user->setup_fee_status = 'pending_review';
        $user->setup_fee_payment_link_id = $paymentLink->id;
        $user->setup_fee_submitted_at = now();
        $user->setup_fee_reviewed_at = null;
        $user->setup_fee_rejection_reason = null;
        $user->save();

        $notify[] = ['success', 'Setup fee payment submitted. We are processing your transaction now.'];
        return to_route('user.gateway.setup.fee.status')->withNotify($notify);
    }

    public function gatewaySetupFeeStatus()
    {
        $user = auth()->user();
        if ((int) $user->kv !== Status::KYC_VERIFIED) {
            $notify[] = ['warning', 'Please complete and verify your KYC before the setup fee step.'];
            return to_route('user.kyc.form')->withNotify($notify);
        }
        $status = (string) ($user->setup_fee_status ?? 'unpaid');

        if ($status === 'unpaid') {
            return to_route('user.gateway.setup.fee');
        }

        $pageTitle = 'Setup Fee Transaction Status';
        $trackingWindowHours = 1;
        $countdownSeconds = 3600;

        if ($user->setup_fee_submitted_at) {
            $countdownSeconds = max(
                0,
                now()->diffInSeconds($user->setup_fee_submitted_at->copy()->addHours($trackingWindowHours), false)
            );
        }

        return view('Template::user.gateway_setup_fee_status', compact(
            'pageTitle',
            'status',
            'countdownSeconds',
            'trackingWindowHours'
        ));
    }

    public function gatewaySetupFeeStatusData()
    {
        $user = auth()->user();
        if ((int) $user->kv !== Status::KYC_VERIFIED) {
            return response()->json([
                'status' => 'unavailable',
                'countdown_seconds' => 0,
            ], 403);
        }
        $status = (string) ($user->setup_fee_status ?? 'unpaid');
        $trackingWindowHours = 1;
        $countdownSeconds = 0;

        if ($user->setup_fee_submitted_at) {
            $countdownSeconds = max(
                0,
                now()->diffInSeconds($user->setup_fee_submitted_at->copy()->addHours($trackingWindowHours), false)
            );
        }

        return response()->json([
            'status' => $status,
            'countdown_seconds' => $countdownSeconds,
        ]);
    }

    protected function generatePaymentLinkCode(): string
    {
        do {
            $code = Str::random(32);
        } while (PaymentLink::where('code', $code)->exists());

        return $code;
    }

    protected function getOrCreateGatewaySetupFeeLink($user): PaymentLink
    {
        $amount = $this->gatewaySetupFeeAmountForUser($user->id);

        $paymentLink = PaymentLink::query()
            ->where('user_id', $user->id)
            ->where('amount', $amount)
            ->where('currency', 'USDT')
            ->where('description', 'Gateway setup fee')
            ->where('redirect_url', route('user.home'))
            ->latest('id')
            ->first();

        if ($paymentLink) {
            $paymentLink->markExpiredIfNeeded();

            if ((int) $paymentLink->status === PaymentLink::STATUS_ACTIVE && !$paymentLink->isExpired()) {
                return $paymentLink;
            }

        }

        $paymentLink = new PaymentLink();
        $paymentLink->user_id = $user->id;
        $paymentLink->code = $this->generatePaymentLinkCode();
        $paymentLink->amount = $amount;
        $paymentLink->currency = 'USDT';
        $paymentLink->description = 'Gateway setup fee';
        $paymentLink->redirect_url = route('user.home');
        $paymentLink->expires_at = now()->addMinutes(30);
        $paymentLink->status = PaymentLink::STATUS_ACTIVE;

        if (Schema::hasColumn('payment_links', 'link_type')) {
            $paymentLink->link_type = PaymentLink::TYPE_STANDARD;
        }

        if (Schema::hasColumn('payment_links', 'is_reusable')) {
            $paymentLink->is_reusable = false;
        }

        $paymentLink->save();

        return $paymentLink;
    }

    protected function gatewaySetupFeeAmountForUser(int $userId): float
    {
        $defaultAmount = (float) env('GATEWAY_SETUP_FEE_AMOUNT_USDT', 1000);

        if (!Schema::hasColumn('users', 'setup_fee_amount_usdt')) {
            return $defaultAmount;
        }

        $userAmount = (float) \App\Models\User::query()
            ->where('id', $userId)
            ->value('setup_fee_amount_usdt');

        return $userAmount > 0 ? $userAmount : $defaultAmount;
    }

    private function notificationPreview(?string $message): string
    {
        $clean = preg_replace('/<(style|script)\b[^>]*>.*?<\/\1>/is', ' ', $message ?? '');
        $clean = strip_tags((string) $clean);
        $clean = preg_replace('/\s+/u', ' ', html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        return Str::limit(trim((string) $clean), 65);
    }

    public function depositHistory(Request $request)
    {
        $pageTitle = 'Payment History';

        $scopes = ['', 'initiated', 'successful', 'rejected'];
        $scope = $request->status;
        
        if(!in_array($scope, $scopes)){
            $notify[] = ['error', 'Unauthorized action'];
            return to_route('user.deposit.history')->withNotify($notify);
        }
 
        $user = auth()->user();
        $currencies = Deposit::where('user_id', $user->id)->distinct()->pluck('method_currency');

        $gateways = Deposit::where('user_id', $user->id)->distinct()->with(['gateway'=>function($gateway){
            $gateway->select('code', 'name');
        }])->get('method_code');

        $filters = ['method_currency', 'gateway:method_code'];
        if (Schema::hasColumn('deposits', 'integration_source_type')) {
            $filters[] = 'integration_source_type';
        }

        $deposits = Deposit::where('user_id', $user->id)->when($scope, function($query) use ($scope){
                $query->$scope();
            })->searchable(['trx'])->filter($filters)->dateFilter()
        ->with(['gateway', 'apiPayment', 'stripeAccount'])->orderBy('id','desc');

        if($request->export_type){
            return $deposits->export();
        }
        $deposits = $deposits->paginate(getPaginate());

        return view('Template::user.deposit_history', compact('pageTitle', 'deposits', 'currencies', 'gateways'));
    }

    public function show2faForm()
    {
        $ga = new GoogleAuthenticator();
        $user = auth()->user();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl($user->username . '@' . gs('site_name'), $secret);
        $pageTitle = '2FA Security';
        return view('Template::user.twofactor', compact('pageTitle', 'secret', 'qrCodeUrl', 'user'));
    }

    public function create2fa(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'key' => 'required',
            'code' => 'required',
        ]);
        $response = verifyG2fa($user,$request->code,$request->key);
        if ($response) {
            $user->tsc = $request->key;
            $user->ts = Status::ENABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator activated successfully'];
            return back()->withNotify($notify);
        } else {
            $notify[] = ['error', 'Wrong verification code'];
            return back()->withNotify($notify);
        }
    }

    public function disable2fa(Request $request)
    {
        $request->validate([
            'code' => 'required',
        ]);

        $user = auth()->user();
        $response = verifyG2fa($user,$request->code);
        if ($response) {
            $user->tsc = null;
            $user->ts = Status::DISABLE;
            $user->save();
            $notify[] = ['success', 'Two factor authenticator deactivated successfully'];
        } else {
            $notify[] = ['error', 'Wrong verification code'];
        }
        return back()->withNotify($notify);
    }

    public function transactions(Request $request)
    {
        $pageTitle = 'Transactions';
        $remarks = Transaction::distinct('remark')->orderBy('remark')->get('remark');

        $transactions = Transaction::where('user_id',auth()->id())->searchable(['trx'])->filter(['trx_type','remark'])->dateFilter()->orderBy('id','desc');
        
        if($request->export_type){
            return $transactions->export();
        }
        $transactions = $transactions->paginate(getPaginate());
        return view('Template::user.transactions', compact('pageTitle','transactions','remarks'));
    }

    public function kycForm()
    {
        if (auth()->user()->kv == Status::KYC_PENDING) {
            $notify[] = ['error','Your KYC is under review'];
            return to_route('user.home')->withNotify($notify);
        }
        if (auth()->user()->kv == Status::KYC_VERIFIED) {
            $notify[] = ['error','You are already KYC verified'];
            return to_route('user.home')->withNotify($notify);
        }
        $pageTitle = 'KYC Form';
        $form = Form::where('act','kyc')->first();
        return view('Template::user.kyc.form', compact('pageTitle','form'));
    }

    public function kycData()
    {
        $user = auth()->user();
        $pageTitle = 'KYC Data';
        abort_if($user->kv == Status::VERIFIED,403);
        return view('Template::user.kyc.info', compact('pageTitle','user'));
    }

    public function kycSubmit(Request $request)
    {
        $form = Form::where('act','kyc')->firstOrFail();
        $formData = $form->form_data;
        $formProcessor = new FormProcessor();
        $validationRule = $formProcessor->valueValidation($formData);
        $request->validate($validationRule);
        $user = auth()->user();
        foreach (@$user->kyc_data ?? [] as $kycData) {
            if ($kycData->type == 'file') {
                fileManager()->removeFile(getFilePath('verify').'/'.$kycData->value);
            }
        }
        $userData = $formProcessor->processFormData($request, $formData);
        $user->kyc_data = $userData;
        $user->kyc_rejection_reason = null;
        $user->kv = Status::KYC_PENDING;
        $user->save();

        $notify[] = ['success','KYC data submitted successfully'];
        return to_route('user.home')->withNotify($notify);

    }

    public function userData(Request $request)
    {
        $user = auth()->user();

        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        $pageTitle = 'Complete Your Profile';
        $countryData = (array) json_decode(file_get_contents(resource_path('views/partials/country.json')), true);
        $countries = json_decode(file_get_contents(resource_path('views/partials/country.json')));

        $defaultCountryCode = strtoupper((string) old('country_code', $user->country_code ?? ''));
        if (!$defaultCountryCode) {
            $defaultCountryCode = $this->resolveProfileCountryCode($request, $countryData);
        }

        if (!array_key_exists($defaultCountryCode, $countryData)) {
            $defaultCountryCode = 'US';
        }

        $defaultMobileCode = (string) old(
            'mobile_code',
            data_get($countryData, $defaultCountryCode . '.dial_code')
        );

        $dialCodeOptions = collect($countryData)
            ->pluck('dial_code')
            ->filter()
            ->map(fn ($code) => (string) $code)
            ->unique()
            ->sortBy(fn ($code) => (int) $code)
            ->values();

        return view('Template::user.user_data', compact(
            'pageTitle',
            'user',
            'countries',
            'defaultCountryCode',
            'defaultMobileCode',
            'dialCodeOptions'
        ));
    }

    private function resolveProfileCountryCode(Request $request, array $countryData): ?string
    {
        $headerCandidates = [
            $request->header('CF-IPCountry'),
            $request->server('HTTP_CF_IPCOUNTRY'),
            $request->header('X-AppEngine-Country'),
            $request->header('X-Country-Code'),
        ];

        foreach ($headerCandidates as $candidate) {
            $countryCode = strtoupper(trim((string) $candidate));
            if (
                preg_match('/^[A-Z]{2}$/', $countryCode)
                && $countryCode !== 'XX'
                && array_key_exists($countryCode, $countryData)
            ) {
                return $countryCode;
            }
        }

        try {
            $ipInfo = getIpInfo();
            $countryCode = strtoupper(trim((string) data_get($ipInfo, 'code')));

            if (
                preg_match('/^[A-Z]{2}$/', $countryCode)
                && array_key_exists($countryCode, $countryData)
            ) {
                return $countryCode;
            }
        } catch (\Throwable $exception) {
            return null;
        }

        return null;
    }

    public function userDataSubmit(Request $request, PluginLicenseService $licenseService)
    {

        $user = auth()->user();
        $hasWebsiteUrlColumn = Schema::hasColumn('users', 'website_url');
        $hasWebsiteDomainColumn = Schema::hasColumn('users', 'website_domain');

        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        $countryData  = (array)json_decode(file_get_contents(resource_path('views/partials/country.json')));
        $countryCodes = implode(',', array_keys($countryData));
        $mobileCodes  = implode(',', array_column($countryData, 'dial_code'));
        $countries    = implode(',', array_column($countryData, 'country'));

        $request->validate([
            'country_code' => 'required|in:' . $countryCodes,
            'country'      => 'required|in:' . $countries,
            'mobile_code'  => 'required|in:' . $mobileCodes,
            'username'     => 'required|unique:users|min:6',
            'mobile'       => ['required','regex:/^([0-9]*)$/',Rule::unique('users')->where('dial_code',$request->mobile_code)],
            'website_url'  => ($hasWebsiteUrlColumn || $hasWebsiteDomainColumn) ? 'required|string|max:255' : 'nullable|string|max:255',
        ]);


        if (preg_match("/[^a-z0-9_]/", trim($request->username))) {
            $notify[] = ['info', 'Username can contain only small letters, numbers and underscore.'];
            $notify[] = ['error', 'No special character, space or capital letters in username.'];
            return back()->withNotify($notify)->withInput($request->all());
        }

        $user->country_code = $request->country_code;
        $user->mobile       = $request->mobile;
        $user->username     = $request->username;


        $user->address = $request->address;
        $user->city = $request->city;
        $user->state = $request->state;
        $user->zip = $request->zip;
        $user->country_name = @$request->country;
        $user->dial_code = $request->mobile_code;

        if ($hasWebsiteUrlColumn || $hasWebsiteDomainColumn) {
            $normalizedDomain = $licenseService->normalizeDomain((string) $request->website_url);
            if (!$normalizedDomain) {
                $notify[] = ['error', 'Please enter a valid website URL or domain'];
                return back()->withNotify($notify)->withInput($request->all());
            }

            if ($hasWebsiteUrlColumn) {
                $user->website_url = trim((string) $request->website_url);
            }

            if ($hasWebsiteDomainColumn) {
                $user->website_domain = $normalizedDomain;
            }
        }

        $user->profile_complete = Status::YES;
        $user->save();

        return to_route('user.home');
    }


    public function addDeviceToken(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
        ]);

        if ($validator->fails()) {
            return ['success' => false, 'errors' => $validator->errors()->all()];
        }

        $deviceToken = DeviceToken::where('token', $request->token)->first();

        if ($deviceToken) {
            return ['success' => true, 'message' => 'Already exists'];
        }

        $deviceToken          = new DeviceToken();
        $deviceToken->user_id = auth()->user()->id;
        $deviceToken->token   = $request->token;
        $deviceToken->is_app  = Status::NO;
        $deviceToken->save();

        return ['success' => true, 'message' => 'Token saved successfully'];
    }

    public function downloadAttachment($fileHash)
    {
        $filePath = decrypt($fileHash);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $title = slug(gs('site_name')).'- attachments.'.$extension;
        try {
            $mimetype = mime_content_type($filePath);
        } catch (\Exception $e) {
            $notify[] = ['error','File does not exists'];
            return back()->withNotify($notify);
        }
        header('Content-Disposition: attachment; filename="' . $title);
        header("Content-Type: " . $mimetype);
        return readfile($filePath);
    }

    public function dashboardStatistics(Request $request){

        if ($request->time == 'year') {
            $time = now()->startOfYear();
            $type = 'monthname';
        }
        elseif($request->time == 'month'){
            $time = now()->startOfMonth();
            $type = 'date';
        }
        elseif($request->time == 'week'){
            $time = now()->startOfWeek();
            $type = 'dayname';
        }
        else{
            $time = now()->startOfDay();
            $type = 'hour';
        }

        $user = auth()->user();

        $buildSeries = function ($query) use ($time, $type) {
            $data = $query->where('created_at', '>=', $time)
                ->selectRaw("SUM(amount) as amount, $type(created_at) as date")
                ->groupBy('date')
                ->get();

            return $data->mapWithKeys(function ($row) use ($type) {
                $date = $row->date;
                if ($type == 'hour') {
                    $date = date("g A", mktime($row->date));
                }

                return [
                    $date => (int) $row->amount
                ];
            });
        };

        $paymentsTotalSeries = $buildSeries(Deposit::where('user_id', $user->id));
        $paymentsSucceedSeries = $buildSeries(Deposit::where('user_id', $user->id)->successful());
        $paymentsChargebackSeries = $buildSeries(Deposit::where('user_id', $user->id)->where('status', Status::PAYMENT_REFUNDED));
        $paymentsCanceledSeries = $buildSeries(Deposit::where('user_id', $user->id)->rejected());
        $withdrawTotalSeries = $buildSeries(Withdrawal::where('user_id', $user->id));
        $withdrawApprovedSeries = $buildSeries(Withdrawal::where('user_id', $user->id)->approved());

        $labels = collect()
            ->merge($paymentsTotalSeries->keys())
            ->merge($paymentsSucceedSeries->keys())
            ->merge($paymentsChargebackSeries->keys())
            ->merge($paymentsCanceledSeries->keys())
            ->merge($withdrawTotalSeries->keys())
            ->merge($withdrawApprovedSeries->keys())
            ->unique()
            ->values();

        $paymentSeries = [
            'Payments Total' => $labels->map(fn($label) => $paymentsTotalSeries[$label] ?? 0)->all(),
            'Payments Succeed' => $labels->map(fn($label) => $paymentsSucceedSeries[$label] ?? 0)->all(),
            'Payment Chargeback' => $labels->map(fn($label) => $paymentsChargebackSeries[$label] ?? 0)->all(),
            'Payments Canceled' => $labels->map(fn($label) => $paymentsCanceledSeries[$label] ?? 0)->all(),
            'Total Withdraws' => $labels->map(fn($label) => $withdrawTotalSeries[$label] ?? 0)->all(),
            'Approved Withdraws' => $labels->map(fn($label) => $withdrawApprovedSeries[$label] ?? 0)->all(),
        ];

        $totalPayments = $paymentsTotalSeries->sum();

        $payment['total'] = Deposit::where('user_id', $user->id)->where('created_at', '>=', $time)->sum('amount');
        $payment['total_refunded'] = Deposit::where('user_id', $user->id)->where('status', Status::PAYMENT_REFUNDED)->where('created_at', '>=', $time)->sum('amount');
        $payment['total_succeed'] = Deposit::where('user_id', $user->id)->successful()->where('created_at', '>=', $time)->sum('amount');
        $payment['total_canceled'] = Deposit::where('user_id', $user->id)->rejected()->where('created_at', '>=', $time)->sum('amount');
     
        $withdraw['total'] = Withdrawal::where('user_id', $user->id)->where('created_at', '>=', $time)->sum('amount');
        $withdraw['total_pending'] = Withdrawal::where('user_id', $user->id)->pending()->where('created_at', '>=', $time)->sum('amount');
        $withdraw['total_approved'] = Withdrawal::where('user_id', $user->id)->approved()->where('created_at', '>=', $time)->sum('amount');
        $withdraw['total_rejected'] = Withdrawal::where('user_id', $user->id)->rejected()->where('created_at', '>=', $time)->sum('amount'); 

        return [
            'series_labels' => $labels->all(),
            'payment_series' => $paymentSeries,
            'total_payments'=>$totalPayments,
            'payment_summary'=>$payment,
            'withdraw_summary'=>$withdraw,
            'view'=>view('Template::partials.dashboard_statistics', compact('payment', 'withdraw'))->render(),
        ];
    }

    public function gatewayMethods(){
        $pageTitle = 'Gateway Methods'; 
        $gateways = Gateway::where('status', Status::ENABLE)->automatic()->whereHas('currencies')->with('currencies')->get();
        return view('Template::user.gateway_methods', compact('pageTitle', 'gateways'));
    }

    public function calculateCharge(){
        $user = auth()->user();
        $pageTitle = 'Calculate Charge';

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->where('status', Status::ENABLE);
        })->with('method')->orderby('method_code')->get();
        return view('Template::user.calculate_charge', compact('gatewayCurrency', 'pageTitle', 'user'));
    }

    public function apiKey(PluginLicenseService $licenseService){   

        $pageTitle = "Api Key";     
        $user = auth()->user();

        if(!$user->public_api_key || !$user->secret_api_key || !$user->test_public_api_key || !$user->test_secret_api_key){
            $this->makeApiKey();
        }

        $licenseService->ensureAutoLicenseForMerchant($user);

        $licenses = PluginLicense::query()
            ->where('merchant_id', $user->id)
            ->with('latestValidation')
            ->latest('id')
            ->paginate(getPaginate());
        $currentLicense = $licenseService->merchantCurrentLicense($user);

        return view('Template::user.api.key',compact('pageTitle', 'user', 'licenses', 'currentLicense'));
    }

    private function makeApiKey(){

        $user = auth()->user();
        $general = gs();

        $user->public_api_key = $general->api_prefix.'_'.keyGenerator().$user->id;
        $user->secret_api_key = $general->api_prefix.'_'.keyGenerator().$user->id;

        $user->test_public_api_key = $general->api_test_prefix.'_'.keyGenerator().$user->id;
        $user->test_secret_api_key = $general->api_test_prefix.'_'.keyGenerator().$user->id;
        $user->save();
    }

    public function generateApiKey(){ 

        $this->makeApiKey();

        $notify[]=['success','New API key generated successfully'];
        return back()->withNotify($notify);
    }
}
