<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StripeAccount;
use Illuminate\Http\Request;

class StripeAccountController extends Controller
{
    public function index()
    {
        $pageTitle = 'Stripe Accounts';
        $accounts = StripeAccount::orderBy('id')->get();
        return view('admin.stripe_accounts.index', compact('pageTitle', 'accounts'));
    }

    public function create()
    {
        $pageTitle = 'Add Stripe Account';
        return view('admin.stripe_accounts.create', compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'publishable_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gte:min_amount',
            'is_active' => 'required|in:0,1',
        ]);

        StripeAccount::create([
            'name' => $request->name,
            'publishable_key' => $request->publishable_key,
            'secret_key' => $request->secret_key,
            'min_amount' => $request->min_amount,
            'max_amount' => $request->max_amount ?? 0,
            'is_active' => (bool) $request->is_active,
        ]);

        $notify[] = ['success', 'Stripe account added successfully'];
        return to_route('admin.stripe.accounts.index')->withNotify($notify);
    }

    public function edit($id)
    {
        $pageTitle = 'Edit Stripe Account';
        $account = StripeAccount::findOrFail($id);
        return view('admin.stripe_accounts.edit', compact('pageTitle', 'account'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'publishable_key' => 'required|string|max:255',
            'secret_key' => 'required|string|max:255',
            'min_amount' => 'required|numeric|min:0',
            'max_amount' => 'nullable|numeric|gte:min_amount',
            'is_active' => 'required|in:0,1',
        ]);

        $account = StripeAccount::findOrFail($id);
        $account->update([
            'name' => $request->name,
            'publishable_key' => $request->publishable_key,
            'secret_key' => $request->secret_key,
            'min_amount' => $request->min_amount,
            'max_amount' => $request->max_amount ?? 0,
            'is_active' => (bool) $request->is_active,
        ]);

        $notify[] = ['success', 'Stripe account updated successfully'];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        $account = StripeAccount::findOrFail($id);
        $account->is_active = !$account->is_active;
        $account->save();

        $notify[] = ['success', 'Status changed successfully'];
        return back()->withNotify($notify);
    }
}
