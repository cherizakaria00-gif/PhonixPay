<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SetupFeeController extends Controller
{
    public function index()
    {
        $pageTitle = 'Setup Fee Payments';

        if (!Schema::hasColumn('users', 'setup_fee_status')) {
            $users = User::query()->whereRaw('1 = 0')->paginate(getPaginate());
            $notify[] = ['warning', 'Setup fee columns are not available yet. Please run database migrations.'];
            return view('admin.setup_fee.index', compact('pageTitle', 'users'))->withNotify($notify);
        }

        $query = User::query()
            ->whereNotNull('setup_fee_status')
            ->whereIn('setup_fee_status', ['pending_review', 'approved', 'rejected']);

        if (request('status')) {
            $query->where('setup_fee_status', request('status'));
        }

        if (request('search')) {
            $search = trim((string) request('search'));
            $query->where(function ($builder) use ($search) {
                $builder->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByRaw("CASE setup_fee_status WHEN 'pending_review' THEN 0 WHEN 'rejected' THEN 1 WHEN 'approved' THEN 2 ELSE 3 END")
            ->latest('setup_fee_submitted_at')
            ->paginate(getPaginate());

        return view('admin.setup_fee.index', compact('pageTitle', 'users'));
    }

    public function approve($id)
    {
        if (!Schema::hasColumn('users', 'setup_fee_status')) {
            $notify[] = ['error', 'Setup fee columns are missing. Run migrations first.'];
            return back()->withNotify($notify);
        }

        $user = User::findOrFail($id);
        $user->setup_fee_status = 'approved';
        $user->setup_fee_reviewed_at = now();
        $user->setup_fee_rejection_reason = null;
        $user->save();

        $notify[] = ['success', 'Setup fee approved successfully'];
        return back()->withNotify($notify);
    }

    public function reject(Request $request, $id)
    {
        if (!Schema::hasColumn('users', 'setup_fee_status')) {
            $notify[] = ['error', 'Setup fee columns are missing. Run migrations first.'];
            return back()->withNotify($notify);
        }

        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $user = User::findOrFail($id);
        $user->setup_fee_status = 'rejected';
        $user->setup_fee_reviewed_at = now();
        $user->setup_fee_rejection_reason = $request->reason;
        $user->save();

        $notify[] = ['success', 'Setup fee rejected successfully'];
        return back()->withNotify($notify);
    }
}
