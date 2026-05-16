<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\Sync\SimulatedPlaidSync;
use App\Services\Sync\TransactionMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SyncController extends Controller
{
    public function __construct(
        private readonly SimulatedPlaidSync $simulatedSync,
        private readonly TransactionMatcher $matcher,
    ) {}

    public function index(): Response
    {
        $pendingPlaid = Transaction::query()
            ->with(['account', 'category'])
            ->where('source', 'plaid')
            ->where('status', 'pending')
            ->orderByDesc('posted_date')
            ->orderByDesc('id')
            ->get();

        $candidates = $pendingPlaid->map(function (Transaction $tx) {
            $suggestions = $this->matcher->suggestionsFor($tx);

            return [
                'id' => $tx->id,
                'description' => $tx->description,
                'merchant_name' => $tx->merchant_name,
                'amount_cents' => $tx->amount_cents,
                'date' => $tx->posted_date?->toDateString() ?? $tx->pending_date?->toDateString(),
                'account_name' => $tx->account?->name,
                'plaid_transaction_id' => $tx->plaid_transaction_id,
                'suggestions' => $suggestions->map(fn (array $row) => [
                    'id' => $row['transaction']->id,
                    'description' => $row['transaction']->description,
                    'amount_cents' => $row['transaction']->amount_cents,
                    'date' => $row['transaction']->posted_date?->toDateString()
                        ?? $row['transaction']->pending_date?->toDateString(),
                    'status' => $row['transaction']->status,
                    'account_name' => $row['transaction']->account?->name,
                    'category_name' => $row['transaction']->category?->name,
                    'score' => $row['score'],
                ])->all(),
            ];
        })->all();

        $lastSyncedAt = Transaction::query()
            ->where('source', 'plaid')
            ->max('created_at');

        return Inertia::render('sync/index', [
            'candidates' => $candidates,
            'lastSyncedAt' => $lastSyncedAt,
            'mergedCount' => Transaction::query()->where('source', 'merged')->count(),
        ]);
    }

    public function simulate(): RedirectResponse
    {
        $result = $this->simulatedSync->run();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $result['created'] === 0
                ? 'No new Plaid transactions — you are caught up.'
                : "Pulled {$result['created']} simulated Plaid transactions.",
        ]);

        return to_route('sync.index');
    }

    public function match(Transaction $candidate): RedirectResponse
    {
        $this->assertIsPlaidCandidate($candidate);

        $manualId = request()->integer('manual_transaction_id');
        $manual = Transaction::query()
            ->where('source', 'manual')
            ->whereNull('plaid_transaction_id')
            ->findOrFail($manualId);

        DB::transaction(function () use ($candidate, $manual) {
            $plaidTransactionId = $candidate->plaid_transaction_id;
            $plaidItemId = $candidate->plaid_item_id;

            // Release the unique plaid_transaction_id from the candidate first
            // so the manual transaction can claim it on merge.
            $candidate->fill([
                'plaid_transaction_id' => null,
                'merged_into_id' => $manual->id,
                'status' => 'void',
            ])->save();

            $manual->fill([
                'plaid_transaction_id' => $plaidTransactionId,
                'plaid_item_id' => $plaidItemId,
                'merged_from_transaction_id' => $candidate->id,
                'merchant_name' => $manual->merchant_name ?? $candidate->merchant_name,
                'raw_description' => $candidate->description,
                'posted_date' => $candidate->posted_date ?? $manual->posted_date,
                'pending_date' => null,
                'status' => 'posted',
                'source' => 'merged',
            ])->save();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transactions merged.']);

        return to_route('sync.index');
    }

    public function accept(Transaction $candidate): RedirectResponse
    {
        $this->assertIsPlaidCandidate($candidate);

        $candidate->fill([
            'status' => 'posted',
            'posted_date' => $candidate->posted_date ?? $candidate->pending_date ?? now()->toDateString(),
            'pending_date' => null,
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plaid transaction added to ledger.']);

        return to_route('sync.index');
    }

    public function reject(Transaction $candidate): RedirectResponse
    {
        $this->assertIsPlaidCandidate($candidate);

        $candidate->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Plaid candidate dismissed.']);

        return to_route('sync.index');
    }

    private function assertIsPlaidCandidate(Transaction $tx): void
    {
        if ($tx->source !== 'plaid' || $tx->status !== 'pending') {
            throw ValidationException::withMessages([
                'candidate' => 'Transaction is not an open Plaid candidate.',
            ]);
        }
    }
}
