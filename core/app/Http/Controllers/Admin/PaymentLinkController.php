<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentLink;
use Illuminate\Http\Request;

class PaymentLinkController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Payment Links';
        $query = PaymentLink::with('user')->orderBy('id', 'desc');

        if ($request->search) {
            $search = $request->search;
            $query->where('code', 'like', '%' . $search . '%')
                ->orWhereHas('user', function ($user) use ($search) {
                    $user->where('username', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
        }

        $paymentLinks = $query->paginate(getPaginate());
        $paymentLinks->getCollection()->each->markExpiredIfNeeded();

        return view('admin.payment_links.index', compact('pageTitle', 'paymentLinks'));
    }
}
