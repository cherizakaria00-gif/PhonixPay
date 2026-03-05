<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Lib\CurlRequest;
use App\Models\AdminNotification;
use App\Models\CurrencyConversionRate;
use App\Models\Deposit;
use App\Models\GatewayCurrency;
use App\Models\Plan;
use App\Models\PlanChangeRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserLogin;
use App\Models\Withdrawal;
use App\Rules\FileTypeValidate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AdminController extends Controller
{

    public function dashboard()
    {
        $pageTitle = 'Dashboard';

        // User Info
        $widget['total_users']             = User::count();
        $widget['verified_users']          = User::active()->count();
        $widget['email_unverified_users']  = User::emailUnverified()->count();
        $widget['mobile_unverified_users'] = User::mobileUnverified()->count();
        $widget['total_transactions']      = Transaction::count();
        $widget['pending_kyc']             = User::kycPending()->count();


        // user Browsing, Country, Operating Log
        $userLoginData = UserLogin::where('created_at', '>=', Carbon::now()->subDays(30))->get(['browser', 'os', 'country']);

        $chart['user_browser_counter'] = $userLoginData->groupBy('browser')->map(function ($item, $key) {
            return collect($item)->count();
        });
        $chart['user_os_counter'] = $userLoginData->groupBy('os')->map(function ($item, $key) {
            return collect($item)->count();
        });
        $chart['user_country_counter'] = $userLoginData->groupBy('country')->map(function ($item, $key) {
            return collect($item)->count();
        })->sort()->reverse()->take(5);


        $deposit['total_deposit_amount']        = Deposit::successful()->sum('amount');
        $deposit['total_deposit_pending']       = Deposit::pending()->count();
        $deposit['total_deposit_rejected']      = Deposit::rejected()->count();
        $deposit['total_deposit_refunded']      = Deposit::refunded()->count();
        $depositChargeFromDeposits = (float) Deposit::successful()
            ->selectRaw('COALESCE(SUM(charge),0) + COALESCE(SUM(payment_charge),0) as total_charge')
            ->value('total_charge');

        $depositChargeFromTransactions = (float) Transaction::query()
            ->whereIn('remark', ['gateway_charge', 'payment_charge'])
            ->sum('amount');

        $deposit['total_deposit_charge'] = $depositChargeFromDeposits > 0
            ? $depositChargeFromDeposits
            : $depositChargeFromTransactions;
        $bictorysBalance = $this->resolveBictorysBalance();

        $withdrawals['total_withdraw_amount']   = Withdrawal::approved()->sum('amount');
        $withdrawals['total_withdraw_pending']  = Withdrawal::pending()->count();
        $withdrawals['total_withdraw_rejected'] = Withdrawal::rejected()->count();
        $withdrawals['total_withdraw_charge']   = Withdrawal::approved()->sum('charge');

        $subscription = [
            'enabled' => Schema::hasTable('plans') && Schema::hasColumn('users', 'plan_id'),
        ];

        if ($subscription['enabled']) {
            $subscription['total_plans'] = Plan::query()->count();
            $subscription['active_plans'] = Plan::query()->where('is_active', true)->count();
            $subscription['active_merchants'] = User::query()
                ->whereNotNull('plan_id')
                ->where('plan_status', 'active')
                ->count();
            $subscription['pending_requests'] = Schema::hasTable('plan_change_requests')
                ? PlanChangeRequest::query()->where('status', 'pending')->count()
                : 0;

            $subscription['mrr_estimate'] = User::query()
                ->join('plans', 'plans.id', '=', 'users.plan_id')
                ->where('users.plan_status', 'active')
                ->sum('plans.price_monthly_cents') / 100;
        }

        $baseCurrency = strtoupper((string) gs('cur_text'));
        $conversionCurrencies = $this->conversionCurrencyOptions($baseCurrency);
        $hasConversionTable = Schema::hasTable('currency_conversion_rates');
        $gatewayRates = GatewayCurrency::query()
            ->get(['currency', 'rate'])
            ->groupBy(function ($row) {
                return strtoupper(trim((string) $row->currency));
            })
            ->map(function ($rows) {
                return (float) $rows->max('rate');
            });
        $storedRates = collect();
        if ($hasConversionTable) {
            $storedRates = CurrencyConversionRate::query()
                ->where('base_currency', $baseCurrency)
                ->orderBy('quote_currency')
                ->get()
                ->keyBy('quote_currency');
        }

        $conversionCurrencies = collect($conversionCurrencies)
            ->merge($storedRates->keys())
            ->unique()
            ->values()
            ->all();

        $conversionRates = collect($conversionCurrencies)->mapWithKeys(function ($currency) use ($storedRates, $gatewayRates) {
            $row = $storedRates->get($currency);
            $fallbackRate = $this->toPositiveFloat($gatewayRates->get($currency));
            $resolvedRate = $row ? (float) $row->rate : $fallbackRate;
            return [
                $currency => [
                    'rate' => $resolvedRate ? round($resolvedRate, 8) : null,
                    'is_active' => $row ? (bool) $row->is_active : true,
                ],
            ];
        })->all();

        $totalRevenueXof = [
            'amount' => null,
            'rate' => null,
            'base_currency' => $baseCurrency,
            'target_currency' => 'XOF',
        ];

        if ($baseCurrency === 'XOF') {
            $totalRevenueXof['rate'] = 1.0;
            $totalRevenueXof['amount'] = round((float) $deposit['total_deposit_amount'], 2);
        } else {
            $xofRate = $this->toPositiveFloat(data_get($conversionRates, 'XOF.rate'));
            $xofRateIsActive = (bool) data_get($conversionRates, 'XOF.is_active', false);

            if ($xofRate !== null && $xofRateIsActive) {
                $totalRevenueXof['rate'] = $xofRate;
                $totalRevenueXof['amount'] = round((float) $deposit['total_deposit_amount'] * $xofRate, 2);
            }
        }

        return view('admin.dashboard', compact(
            'pageTitle',
            'widget',
            'chart',
            'deposit',
            'withdrawals',
            'subscription',
            'bictorysBalance',
            'baseCurrency',
            'hasConversionTable',
            'conversionCurrencies',
            'conversionRates',
            'totalRevenueXof'
        ));
    }

    private function resolveBictorysBalance(): array
    {
        return Cache::remember('admin_dashboard_bictorys_balance_v1', now()->addMinutes(3), function () {
            try {
                $credentials = $this->extractBictorysCredentials();
                if (!$credentials) {
                    return [
                        'is_available' => false,
                        'amount' => null,
                        'currency' => null,
                    ];
                }

                $headers = [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-Api-Key: ' . $credentials['api_key'],
                ];

                foreach ($this->bictorysBalanceEndpointCandidates($credentials) as $endpointUrl) {
                    $raw = CurlRequest::curlContent($endpointUrl, $headers);
                    if (!is_string($raw) || trim($raw) === '') {
                        continue;
                    }

                    $payload = json_decode($raw, true);
                    if (!is_array($payload)) {
                        continue;
                    }

                    $balance = $this->extractBictorysBalanceFromPayload($payload);
                    if ($balance !== null) {
                        return [
                            'is_available' => true,
                            'amount' => round((float) $balance['amount'], 2),
                            'currency' => strtoupper((string) ($balance['currency'] ?: 'XOF')),
                        ];
                    }
                }
            } catch (\Throwable $exception) {
                Log::warning('Unable to resolve Bictorys balance for admin dashboard', [
                    'message' => $exception->getMessage(),
                ]);
            }

            return [
                'is_available' => false,
                'amount' => null,
                'currency' => null,
            ];
        });
    }

    private function extractBictorysCredentials(): ?array
    {
        $gateway = \App\Models\Gateway::query()
            ->whereIn('alias', ['BictorysDirect', 'BictorysCheckout'])
            ->where('status', Status::ENABLE)
            ->orderByRaw("CASE WHEN alias = 'BictorysDirect' THEN 0 ELSE 1 END")
            ->first();

        if (!$gateway) {
            return null;
        }

        $parameters = $this->flattenGatewayParameters($gateway->gateway_parameters);

        $gatewayCurrency = $gateway->singleCurrency()->first();
        if ($gatewayCurrency && !empty($gatewayCurrency->gateway_parameter)) {
            $parameters = array_merge($parameters, $this->flattenGatewayParameters($gatewayCurrency->gateway_parameter));
        }

        $apiKey = trim((string) ($parameters['api_key'] ?? ''));
        if ($apiKey === '') {
            return null;
        }

        $baseUrl = trim((string) ($parameters['api_base_url'] ?? 'https://api.bictorys.com'));
        $baseUrl = rtrim($baseUrl !== '' ? $baseUrl : 'https://api.bictorys.com', '/');

        return [
            'base_url' => $baseUrl,
            'api_key' => $apiKey,
            'merchant_reference' => trim((string) ($parameters['merchant_reference'] ?? '')),
            'custom_balance_endpoint' => trim((string) ($parameters['balance_endpoint'] ?? $parameters['balance_path'] ?? '')),
        ];
    }

    private function flattenGatewayParameters($raw): array
    {
        if (is_string($raw)) {
            $raw = json_decode($raw, true);
        } elseif (is_object($raw)) {
            $raw = (array) $raw;
        }

        if (!is_array($raw)) {
            return [];
        }

        $output = [];
        foreach ($raw as $key => $value) {
            if (is_array($value)) {
                if (array_key_exists('value', $value)) {
                    $output[$key] = $this->scalarParameterValue($value['value']);
                }
                continue;
            }

            if (is_object($value)) {
                if (isset($value->value)) {
                    $output[$key] = $this->scalarParameterValue($value->value);
                }
                continue;
            }

            $scalar = $this->scalarParameterValue($value);
            if ($scalar !== null) {
                $output[$key] = $scalar;
            }
        }

        return $output;
    }

    private function scalarParameterValue($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        return trim((string) $value);
    }

    private function bictorysBalanceEndpointCandidates(array $credentials): array
    {
        $baseUrl = $credentials['base_url'];
        $merchantReference = $credentials['merchant_reference'] ?? '';

        $paths = [];
        $custom = trim((string) ($credentials['custom_balance_endpoint'] ?? ''));
        if ($custom !== '') {
            $paths[] = $custom;
        }

        $paths = array_merge($paths, [
            '/pay/v1/balance',
            '/pay/v1/wallet/balance',
            '/pay/v1/account/balance',
            '/pay/v1/accounts/balance',
            '/pay/v1/merchant/balance',
            '/pay/v1/me/balance',
        ]);

        $urls = [];
        foreach ($paths as $path) {
            $path = trim((string) $path);
            if ($path === '') {
                continue;
            }

            $isAbsolute = str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
            $url = $isAbsolute ? $path : $baseUrl . '/' . ltrim($path, '/');
            $urls[] = $url;

            if ($merchantReference !== '') {
                $separator = str_contains($url, '?') ? '&' : '?';
                $urls[] = $url . $separator . 'merchantReference=' . urlencode($merchantReference);
            }
        }

        return array_values(array_unique($urls));
    }

    private function extractBictorysBalanceFromPayload(array $payload): ?array
    {
        $rows = [];
        $directList = [
            data_get($payload, 'balances'),
            data_get($payload, 'data.balances'),
            data_get($payload, 'data.wallets'),
            data_get($payload, 'wallets'),
        ];

        foreach ($directList as $candidate) {
            if (is_array($candidate)) {
                foreach ($candidate as $row) {
                    if (is_array($row)) {
                        $rows[] = $row;
                    }
                }
            }
        }

        $rows[] = $payload;
        if (is_array(data_get($payload, 'data'))) {
            $rows[] = data_get($payload, 'data');
        }
        if (is_array(data_get($payload, 'wallet'))) {
            $rows[] = data_get($payload, 'wallet');
        }
        if (is_array(data_get($payload, 'data.wallet'))) {
            $rows[] = data_get($payload, 'data.wallet');
        }
        if (is_array(data_get($payload, 'account'))) {
            $rows[] = data_get($payload, 'account');
        }
        if (is_array(data_get($payload, 'data.account'))) {
            $rows[] = data_get($payload, 'data.account');
        }

        $amountPaths = [
            'amount',
            'balance',
            'availableBalance',
            'available_balance',
            'currentBalance',
            'current_balance',
            'walletBalance',
            'wallet_balance',
            'totalBalance',
            'total_balance',
            'value',
            'data.amount',
            'data.balance',
            'data.availableBalance',
            'data.available_balance',
        ];

        $currencyPaths = [
            'currency',
            'currencyCode',
            'currency_code',
            'asset',
            'unit',
            'data.currency',
            'data.currencyCode',
            'data.currency_code',
        ];

        foreach ($rows as $row) {
            foreach ($amountPaths as $amountPath) {
                $amount = $this->normalizeApiNumeric(data_get($row, $amountPath));
                if ($amount === null) {
                    continue;
                }

                $currency = null;
                foreach ($currencyPaths as $currencyPath) {
                    $candidate = trim((string) data_get($row, $currencyPath, ''));
                    if ($candidate !== '') {
                        $currency = strtoupper($candidate);
                        break;
                    }
                }

                return [
                    'amount' => $amount,
                    'currency' => $currency ?: 'XOF',
                ];
            }
        }

        return null;
    }

    private function normalizeApiNumeric($value): ?float
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
            if (!preg_match('/^-?\d+(\.\d+)?$/', $value)) {
                return null;
            }
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    public function updateCurrencyConversion(Request $request)
    {
        $hasRatesTable = Schema::hasTable('currency_conversion_rates');

        $normalizedRates = collect($request->input('rates', []))
            ->map(function ($row) {
                if (!is_array($row)) {
                    return $row;
                }

                if (array_key_exists('rate', $row) && trim((string) $row['rate']) === '') {
                    $row['rate'] = null;
                }

                return $row;
            })
            ->all();

        $request->merge(['rates' => $normalizedRates]);

        $request->validate([
            'rates' => 'required|array|min:1',
            'rates.*.quote_currency' => 'required|string|max:10',
            'rates.*.rate' => 'nullable|numeric|gt:0',
            'rates.*.is_active' => 'nullable|in:0,1',
        ]);

        $baseCurrency = strtoupper((string) gs('cur_text'));
        $existingRates = $hasRatesTable
            ? CurrencyConversionRate::query()
                ->where('base_currency', $baseCurrency)
                ->get()
                ->keyBy('quote_currency')
            : collect();
        $upsertPayload = [];

        foreach ($request->input('rates', []) as $row) {
            $quoteCurrency = strtoupper(trim((string) data_get($row, 'quote_currency')));
            $rate = $this->toPositiveFloat(data_get($row, 'rate'));
            $isActive = (int) data_get($row, 'is_active', 0) === 1;

            if ($quoteCurrency === '' || $quoteCurrency === $baseCurrency) {
                continue;
            }

            $existingRow = $existingRates->get($quoteCurrency);
            if ($rate === null) {
                if (!$isActive && $existingRow) {
                    $rate = (float) $existingRow->rate;
                } else {
                    continue;
                }
            }

            $upsertPayload[] = [
                'base_currency' => $baseCurrency,
                'quote_currency' => $quoteCurrency,
                'rate' => $rate,
                'is_active' => $isActive,
                'source' => 'manual',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!$upsertPayload) {
            $notify[] = ['error', 'Please provide at least one valid currency rate.'];
            return back()->withNotify($notify);
        }

        if ($hasRatesTable) {
            CurrencyConversionRate::query()->upsert(
                $upsertPayload,
                ['base_currency', 'quote_currency'],
                ['rate', 'is_active', 'source', 'updated_at']
            );
        }

        // Keep gateway currency rates aligned with central conversion settings.
        foreach ($upsertPayload as $rateRow) {
            if ((int) $rateRow['is_active'] !== 1) {
                continue;
            }

            GatewayCurrency::query()
                ->where('currency', $rateRow['quote_currency'])
                ->update(['rate' => $rateRow['rate']]);
        }

        if ($hasRatesTable) {
            $notify[] = ['success', 'Currency conversion rates updated successfully'];
        } else {
            $notify[] = ['success', 'Rates were saved to gateway currencies. Run migrations to enable central conversion table.'];
        }
        return back()->withNotify($notify);
    }

    private function conversionCurrencyOptions(string $baseCurrency): array
    {
        $baseCurrency = strtoupper(trim($baseCurrency));
        $fallback = ['USD', 'EUR', 'XOF', 'CAD', 'GBP'];

        $gatewayCurrencies = GatewayCurrency::query()
            ->distinct()
            ->pluck('currency')
            ->filter()
            ->map(fn ($currency) => strtoupper((string) $currency))
            ->all();

        return collect(array_merge($fallback, $gatewayCurrencies))
            ->filter(fn ($currency) => $currency !== '' && $currency !== $baseCurrency)
            ->unique()
            ->values()
            ->all();
    }

    private function toPositiveFloat($value): ?float
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




    public function depositAndWithdrawReport(Request $request) {

        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format = $diffInDays > 30 ? '%M-%Y'  : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }
        $deposits = Deposit::successful()
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();


        $withdrawals = Withdrawal::approved()
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $data = [];

        foreach ($dates as $date) {
            $data[] = [
                'created_on' => $date,
                'deposits' => getAmount($deposits->where('created_on', $date)->first()?->amount ?? 0),
                'withdrawals' => getAmount($withdrawals->where('created_on', $date)->first()?->amount ?? 0)
            ];
        }

        $data = collect($data);

        // Monthly Deposit & Withdraw Report Graph
        $report['created_on']   = $data->pluck('created_on');
        $report['data']     = [
            [
                'name' => 'Deposited',
                'data' => $data->pluck('deposits')
            ],
            [
                'name' => 'Withdrawn',
                'data' => $data->pluck('withdrawals')
            ]
        ];

        return response()->json($report);
    }

    public function transactionReport(Request $request) {

        $diffInDays = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date));

        $groupBy = $diffInDays > 30 ? 'months' : 'days';
        $format = $diffInDays > 30 ? '%M-%Y'  : '%d-%M-%Y';

        if ($groupBy == 'days') {
            $dates = $this->getAllDates($request->start_date, $request->end_date);
        } else {
            $dates = $this->getAllMonths($request->start_date, $request->end_date);
        }

        $plusTransactions   = Transaction::where('trx_type','+')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();

        $minusTransactions  = Transaction::where('trx_type','-')
            ->whereDate('created_at', '>=', $request->start_date)
            ->whereDate('created_at', '<=', $request->end_date)
            ->selectRaw('SUM(amount) AS amount')
            ->selectRaw("DATE_FORMAT(created_at, '{$format}') as created_on")
            ->latest()
            ->groupBy('created_on')
            ->get();


        $data = [];

        foreach ($dates as $date) {
            $data[] = [
                'created_on' => $date,
                'credits' => getAmount($plusTransactions->where('created_on', $date)->first()?->amount ?? 0),
                'debits' => getAmount($minusTransactions->where('created_on', $date)->first()?->amount ?? 0)
            ];
        }

        $data = collect($data);

        // Monthly Deposit & Withdraw Report Graph
        $report['created_on']   = $data->pluck('created_on');
        $report['data']     = [
            [
                'name' => 'Plus Transactions',
                'data' => $data->pluck('credits')
            ],
            [
                'name' => 'Minus Transactions',
                'data' => $data->pluck('debits')
            ]
        ];

        return response()->json($report);
    }


    private function getAllDates($startDate, $endDate) {
        $dates = [];
        $currentDate = new \DateTime($startDate);
        $endDate = new \DateTime($endDate);

        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->format('d-F-Y');
            $currentDate->modify('+1 day');
        }

        return $dates;
    }

    private function  getAllMonths($startDate, $endDate) {
        if ($endDate > now()) {
            $endDate = now()->format('Y-m-d');
        }

        $startDate = new \DateTime($startDate);
        $endDate = new \DateTime($endDate);

        $months = [];

        while ($startDate <= $endDate) {
            $months[] = $startDate->format('F-Y');
            $startDate->modify('+1 month');
        }

        return $months;
    }


    public function profile()
    {
        $pageTitle = 'Profile';
        $admin = auth('admin')->user();
        return view('admin.profile', compact('pageTitle', 'admin'));
    }

    public function profileUpdate(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'image' => ['nullable','image',new FileTypeValidate(['jpg','jpeg','png'])]
        ]);
        $user = auth('admin')->user();

        if ($request->hasFile('image')) {
            try {
                $old = $user->image;
                $user->image = fileUploader($request->image, getFilePath('adminProfile'), getFileSize('adminProfile'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();
        $notify[] = ['success', 'Profile updated successfully'];
        return to_route('admin.profile')->withNotify($notify);
    }

    public function password()
    {
        $pageTitle = 'Password Setting';
        $admin = auth('admin')->user();
        return view('admin.password', compact('pageTitle', 'admin'));
    }

    public function passwordUpdate(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|min:5|confirmed',
        ]);

        $user = auth('admin')->user();
        if (!Hash::check($request->old_password, $user->password)) {
            $notify[] = ['error', 'Password doesn\'t match!!'];
            return back()->withNotify($notify);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        $notify[] = ['success', 'Password changed successfully.'];
        return to_route('admin.password')->withNotify($notify);
    }

    public function notifications(){
        $notifications = AdminNotification::orderBy('id','desc')->with('user')->paginate(getPaginate());
        $hasUnread = AdminNotification::where('is_read',Status::NO)->exists();
        $hasNotification = AdminNotification::exists();
        $pageTitle = 'Notifications';
        return view('admin.notifications',compact('pageTitle','notifications','hasUnread','hasNotification'));
    }

    public function notificationPoll()
    {
        $notifications = AdminNotification::where('is_read', Status::NO)
            ->orderBy('id', 'desc')
            ->take(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'title' => trim(strip_tags((string) $notification->title)) ?: 'Notification',
                    'time' => diffForHumans($notification->created_at),
                    'url' => route('admin.notification.read', $notification->id),
                ];
            });

        return response()->json([
            'status'       => 'success',
            'unread_count' => AdminNotification::where('is_read', Status::NO)->count(),
            'notifications'=> $notifications,
        ]);
    }


    public function notificationRead($id){
        $notification = AdminNotification::findOrFail($id);
        $notification->is_read = Status::YES;
        $notification->save();
        $url = $notification->click_url;
        if ($url == '#') {
            $url = url()->previous();
        }
        return redirect($url);
    }

    public function requestReport()
    {
        $pageTitle = 'Your Listed Report & Request';
        $arr['app_name'] = systemDetails()['name'];
        $arr['app_url'] = env('APP_URL');
        $arr['purchase_code'] = env('PURCHASECODE');
        $url = "https://license.viserlab.com/issue/get?".http_build_query($arr);
        $response = CurlRequest::curlContent($url);
        $response = json_decode($response);
        if (!$response || !@$response->status || !@$response->message) {
            return to_route('admin.dashboard')->withErrors('Something went wrong');
        }
        if ($response->status == 'error') {
            return to_route('admin.dashboard')->withErrors($response->message);
        }
        $reports = $response->message[0];
        return view('admin.reports',compact('reports','pageTitle'));
    }

    public function reportSubmit(Request $request)
    {
        $request->validate([
            'type'=>'required|in:bug,feature',
            'message'=>'required',
        ]);
        $url = 'https://license.viserlab.com/issue/add';

        $arr['app_name'] = systemDetails()['name'];
        $arr['app_url'] = env('APP_URL');
        $arr['purchase_code'] = env('PURCHASECODE');
        $arr['req_type'] = $request->type;
        $arr['message'] = $request->message;
        $response = CurlRequest::curlPostContent($url,$arr);
        $response = json_decode($response);
        if (!$response || !@$response->status || !@$response->message) {
            return to_route('admin.dashboard')->withErrors('Something went wrong');
        }
        if ($response->status == 'error') {
            return back()->withErrors($response->message);
        }
        $notify[] = ['success',$response->message];
        return back()->withNotify($notify);
    }

    public function readAllNotification(){
        AdminNotification::where('is_read',Status::NO)->update([
            'is_read'=>Status::YES
        ]);
        $notify[] = ['success','Notifications read successfully'];
        return back()->withNotify($notify);
    }

    public function deleteAllNotification(){
        AdminNotification::truncate();
        $notify[] = ['success','Notifications deleted successfully'];
        return back()->withNotify($notify);
    }

    public function deleteSingleNotification($id){
        AdminNotification::where('id',$id)->delete();
        $notify[] = ['success','Notification deleted successfully'];
        return back()->withNotify($notify);
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


}
