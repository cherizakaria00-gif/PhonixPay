<?php

namespace App\Http\Controllers;

use App\Constants\Status;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\Frontend;
use App\Models\GatewayCurrency;
use App\Models\Language;
use App\Models\Page;
use App\Models\Plan;
use App\Models\Subscriber;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class SiteController extends Controller
{
    public function index(){

        $reference = @$_GET['reference'];
        if ($reference) {
            session()->put('reference', $reference);
        }

        $pageTitle = 'Home';
        $sections = Page::where('tempname',activeTemplate())->where('slug','/')->first();
        $seoContents = $sections->seo_content;
        $seoImage = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        $plans = collect();
        if (Schema::hasTable('plans')) {
            $plans = Plan::where('is_active', 1)->orderBy('sort_order')->get();
        }

        return view('Template::home', compact('pageTitle','sections','seoContents','seoImage', 'plans'));
    }

    public function applePay()
    {
        $reference = @$_GET['reference'];
        if ($reference) {
            session()->put('reference', $reference);
        }

        $pageTitle = 'Pay';
        $sections = Page::where('tempname',activeTemplate())->where('slug','/')->first();
        $seoContents = $sections->seo_content;
        $seoImage = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        $plans = collect();
        if (Schema::hasTable('plans')) {
            $plans = Plan::where('is_active', 1)->orderBy('sort_order')->get();
        }

        return view('Template::pay', compact('pageTitle','sections','seoContents','seoImage', 'plans'));
    }

    public function pages($slug)
    {
        $page = Page::where('tempname',activeTemplate())->where('slug',$slug)->firstOrFail();
        $pageTitle = $page->name;
        $sections = $page->secs;
        $seoContents = $page->seo_content;
        $seoImage = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::pages', compact('pageTitle','sections','seoContents','seoImage', 'page'));
    }


    public function contact()
    {
        $pageTitle = "Contact Us";
        $user = auth()->user();
        $sections = Page::where('tempname',activeTemplate())->where('slug','contact')->first();
        $seoContents = $sections->seo_content;
        $seoImage = @$seoContents->image ? getImage(getFilePath('seo') . '/' . @$seoContents->image, getFileSize('seo')) : null;
        return view('Template::contact',compact('pageTitle','user','sections','seoContents','seoImage'));
    }


    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'subject' => 'required|string|max:255',
            'message' => 'required',
        ]);

        $request->session()->regenerateToken();

        if(!verifyCaptcha()){
            $notify[] = ['error','Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        $random = getNumber();

        $ticket = new SupportTicket();
        $ticket->user_id = auth()->id() ?? 0;
        $ticket->name = $request->name;
        $ticket->email = $request->email;
        $ticket->priority = Status::PRIORITY_MEDIUM;


        $ticket->ticket = $random;
        $ticket->subject = $request->subject;
        $ticket->last_reply = Carbon::now();
        $ticket->status = Status::TICKET_OPEN;
        $ticket->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = auth()->user() ? auth()->user()->id : 0;
        $adminNotification->title = 'A new contact message has been submitted';
        $adminNotification->click_url = urlPath('admin.ticket.view',$ticket->id);
        $adminNotification->save();

        $message = new SupportMessage();
        $message->support_ticket_id = $ticket->id;
        $message->message = $request->message;
        $message->save();

        $notify[] = ['success', 'Ticket created successfully!'];

        return to_route('ticket.view', [$ticket->ticket])->withNotify($notify);
    }

    public function policyPages($slug)
    {   
        $policy = Frontend::where('slug',$slug)->where('data_keys','policy_pages.element')->firstOrFail();
        $pageTitle = $policy->data_values->title;
        $seoContents = $policy->seo_content;
        $seoImage = @$seoContents->image ? frontendImage('policy_pages',$seoContents->image,getFileSize('seo'),true) : null;
        return view('Template::policy',compact('policy','pageTitle','seoContents','seoImage'));
    }

    public function changeLanguage($lang = null)
    {
        $language = Language::where('code', $lang)->first();
        if (!$language) $lang = 'en';
        session()->put('lang', $lang);
        return back();
    }

    public function blogs() {
        $pageTitle = 'Blogs';
        $sections = Page::where('tempname', activeTemplate())->where('slug', 'blog')->first();
        return view('Template::blogs', compact('pageTitle', 'sections'));
    }

    public function blogDetails($slug){
        $blog = Frontend::where('slug',$slug)->where('data_keys','blog.element')->firstOrFail();
        $pageTitle = $blog->data_values->title;
        $seoContents = $blog->seo_content;
        $seoImage = @$seoContents->image ? frontendImage('blog',$seoContents->image,getFileSize('seo'),true) : null;
        $latestBlogs = Frontend::where('data_keys', 'blog.element')->where('id', '!=', $blog->id)->orderBy('id', 'DESC')->take(10)->get();
        return view('Template::blog_details',compact('blog','pageTitle','seoContents','seoImage', 'latestBlogs'));
    }


    public function cookieAccept(){
        Cookie::queue('gdpr_cookie',gs('site_name') , 43200);
    }

    public function cookiePolicy(){
        $cookieContent = Frontend::where('data_keys','cookie.data')->first();
        abort_if($cookieContent->data_values->status != Status::ENABLE,404);
        $pageTitle = 'Cookie Policy';
        $cookie = Frontend::where('data_keys','cookie.data')->first();
        return view('Template::cookie',compact('pageTitle','cookie'));
    }

    public function placeholderImage($size = null){
        $imgWidth = explode('x',$size)[0];
        $imgHeight = explode('x',$size)[1];
        $text = $imgWidth . '×' . $imgHeight;
        $fontFile = realpath('assets/font/solaimanLipi_bold.ttf');
        $fontSize = round(($imgWidth - 50) / 8);
        if ($fontSize <= 9) {
            $fontSize = 9;
        }
        if($imgHeight < 100 && $fontSize > 30){
            $fontSize = 30;
        }

        $image     = imagecreatetruecolor($imgWidth, $imgHeight);
        $colorFill = imagecolorallocate($image, 100, 100, 100);
        $bgFill    = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $bgFill);
        $textBox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth  = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);
        $textX      = ($imgWidth - $textWidth) / 2;
        $textY      = ($imgHeight + $textHeight) / 2;
        header('Content-Type: image/jpeg');
        imagettftext($image, $fontSize, 0, $textX, $textY, $colorFill, $fontFile, $text);
        imagejpeg($image);
        imagedestroy($image);
    }

    public function maintenance()
    {
        $pageTitle = 'Maintenance Mode';
        if(gs('maintenance_mode') == Status::DISABLE){
            return to_route('home');
        }
        $maintenance = Frontend::where('data_keys','maintenance.data')->first();
        return view('Template::maintenance',compact('pageTitle','maintenance'));
    }

    public function subscribe(Request $request) {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255|unique:subscribers,email'
        ]);

        if (!$validator->passes()) {
            return response()->json(['error' => $validator->errors()->all()]);
        }

        $newSubscriber = new Subscriber();
        $newSubscriber->email = $request->email;
        $newSubscriber->save();

        return response()->json(['success' => true, 'message' => 'Thank you, we will notice you our latest news']);
    }

    public function apiDocumentation(){
        $pageTitle = 'Api Documentation';
        $allCurrency = GatewayCurrency::whereHas('method', function ($gate) {
                            $gate->where('status', Status::ENABLE);
                        })->get(['id','currency','symbol'])->unique('currency');
        return view('Template::api_documentation', compact('pageTitle', 'allCurrency'));
    }

    public function successPaymentRedirect(Request $request, $depositId)
    {
        $deposit = Deposit::where('id', $depositId)->orderBy('id', 'desc')->firstOrFail();

        if ($deposit->status == Status::PAYMENT_SUCCESS) {
            return redirect($deposit->success_url ?? route('home'));
        }

        if ($deposit->gateway && $deposit->gateway->alias === 'StripePaymentLink') {
            $this->confirmStripePaymentLink($deposit);
        }

        if ($deposit->gateway && $deposit->gateway->alias === 'BictorysCheckout') {
            $this->confirmBictorysCheckout($deposit, $request);
        }

        if ($deposit->gateway && $deposit->gateway->alias === 'BictorysDirect') {
            $this->confirmBictorysDirect($deposit, $request);
        }

        if ($deposit->status == Status::PAYMENT_REJECT) {
            return redirect($deposit->failed_url ?? route('home'));
        }

        return redirect($deposit->success_url ?? route('home'));
    }

    public function cancelPaymentRedirect($depositId)
    {
        $deposit = Deposit::where('id', $depositId)->orderBy('id', 'desc')->firstOrFail();

        if ($deposit->status == Status::PAYMENT_INITIATE) {
            $deposit->status = Status::PAYMENT_REJECT;
            $deposit->save();
        }

        return redirect($deposit->failed_url ?? route('home'));
    }

    private function confirmStripePaymentLink(Deposit $deposit): void
    {
        if (!$deposit->btc_wallet) {
            return;
        }

        $secretKey = $deposit->stripeAccount->secret_key ?? null;
        if (!$secretKey) {
            $gatewayCurrency = GatewayCurrency::where('gateway_alias', 'StripePaymentLink')
                ->orderBy('id', 'desc')
                ->first();
            $gatewayParams = json_decode($gatewayCurrency->gateway_parameter ?? '{}');
            $secretKey = $gatewayParams->secret_key ?? null;
        }

        if (!$secretKey) {
            return;
        }

        try {
            \Stripe\Stripe::setApiKey($secretKey);
            $sessions = \Stripe\Checkout\Session::all([
                'payment_link' => $deposit->btc_wallet,
                'limit' => 5,
            ]);

            if (empty($sessions->data)) {
                return;
            }

            $paidSession = collect($sessions->data)->first(function ($session) {
                return ($session->payment_status ?? null) === 'paid';
            });

            if (!$paidSession) {
                return;
            }

            if (Schema::hasColumn('deposits', 'stripe_session_id')) {
                $deposit->stripe_session_id = $paidSession->id;
            }

            if (!empty($paidSession->payment_intent) && Schema::hasColumn('deposits', 'stripe_charge_id')) {
                try {
                    $intent = \Stripe\PaymentIntent::retrieve($paidSession->payment_intent, []);
                    if (!empty($intent->latest_charge)) {
                        $deposit->stripe_charge_id = $intent->latest_charge;
                    }
                } catch (\Exception $e) {
                    // Ignore charge lookup errors, continue with success
                }
            }

            PaymentController::userDataUpdate($deposit);
        } catch (\Exception $e) {
            // Ignore Stripe errors on redirect, webhook will finalize if available
        }
    }

    private function confirmBictorysCheckout(Deposit $deposit, Request $request): void
    {
        if ($deposit->status != Status::PAYMENT_INITIATE) {
            return;
        }

        $status = strtolower((string) ($request->input('status')
            ?? $request->input('paymentStatus')
            ?? $request->input('payment_status')
            ?? ''));

        if ($status && !in_array($status, ['success', 'successful', 'paid', 'completed'], true)) {
            return;
        }

        $reference = $request->input('paymentReference')
            ?? $request->input('payment_reference')
            ?? $request->input('reference');

        if (!$deposit->btc_wallet && $reference) {
            $deposit->btc_wallet = $reference;
            $deposit->save();
        }

        PaymentController::userDataUpdate($deposit);
    }

    private function confirmBictorysDirect(Deposit $deposit, Request $request): void
    {
        if ($deposit->status != Status::PAYMENT_INITIATE) {
            return;
        }

        $status = strtolower((string) ($request->input('status')
            ?? $request->input('paymentStatus')
            ?? $request->input('payment_status')
            ?? ''));

        $failedFlags = [
            'failed',
            'failure',
            'error',
            'canceled',
            'cancelled',
            'rejected',
            'expired',
        ];

        if (($request->input('success') === false) || in_array($status, $failedFlags, true)) {
            $deposit->status = Status::PAYMENT_REJECT;
            $deposit->save();
            return;
        }

        $successFlags = [
            'success',
            'successful',
            'paid',
            'completed',
        ];

        if (($request->input('success') ?? null) === true || in_array($status, $successFlags, true)) {
            $reference = $request->input('paymentReference')
                ?? $request->input('payment_reference')
                ?? $request->input('reference');

            if (!$deposit->btc_wallet && $reference) {
                $deposit->btc_wallet = $reference;
                $deposit->save();
            }

            PaymentController::userDataUpdate($deposit);
        }
    }
}
