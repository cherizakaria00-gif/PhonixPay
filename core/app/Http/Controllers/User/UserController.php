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
use App\Models\Transaction;
use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function home(Request $request)
    {
        $pageTitle = 'Dashboard'; 
        $user = auth()->user();
        $latestTrx = Transaction::where('user_id', $user->id)->orderBy('id','desc')->take(10);
     
        if($request->export_type){
            return $latestTrx->export();
        }
        $latestTrx = $latestTrx->get();
        return view('Template::user.dashboard', compact('pageTitle', 'user', 'latestTrx'));
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

        $deposits = Deposit::where('user_id', $user->id)->when($scope, function($query) use ($scope){
                $query->$scope();
            })->searchable(['trx'])->filter(['method_currency', 'gateway:method_code'])->dateFilter()
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

    public function userData()
    {
        $user = auth()->user();

        if ($user->profile_complete == Status::YES) {
            return to_route('user.home');
        }

        $pageTitle  = 'Complete Your Profile';
        $info       = json_decode(json_encode(getIpInfo()), true);
        $mobileCode = @implode(',', $info['code']);
        $countries  = json_decode(file_get_contents(resource_path('views/partials/country.json')));

        return view('Template::user.user_data', compact('pageTitle', 'user', 'countries', 'mobileCode'));
    }

    public function userDataSubmit(Request $request)
    {

        $user = auth()->user();

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

    public function apiKey(){   

        $pageTitle = "Api Key";     
        $user = auth()->user();

        if(!$user->public_api_key || !$user->secret_api_key || !$user->test_public_api_key || !$user->test_secret_api_key){
            $this->makeApiKey();
        }

        return view('Template::user.api.key',compact('pageTitle', 'user'));
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
