<?php

namespace App\Http\Controllers;

use App\Models\ObligationInstance;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ObligationInstanceController extends Controller
{
    public function skip(ObligationInstance $instance): RedirectResponse
    {
        $instance->fill(['status' => 'skipped'])->save();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Obligation skipped.']);

        return back();
    }

    public function match(Request $request, ObligationInstance $instance): RedirectResponse
    {
        $request->validate([
            'transaction_id' => ['required', 'exists:transactions,id'],
        ]);

        $instance->fill([
            'transaction_id' => $request->integer('transaction_id'),
            'status' => 'matched',
            'matched_at' => now(),
        ])->save();

        Transaction::query()->whereKey($request->integer('transaction_id'))->update([
            'category_id' => Transaction::find($request->integer('transaction_id'))?->category_id
                ?? $instance->obligation->category_id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Obligation matched.']);

        return back();
    }
}
