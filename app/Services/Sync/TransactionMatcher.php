<?php

namespace App\Services\Sync;

use App\Models\Transaction;
use Illuminate\Support\Collection;

/**
 * Suggests manual transactions that likely correspond to a Plaid candidate.
 *
 * Score components (max 100):
 *   - exact amount match: 60, off-by-cents (<$5 abs): 35
 *   - same account: 20
 *   - date within window: 0-15 by closeness (0 days = 15, 7 days = 0)
 *   - merchant name token overlap: up to 5
 */
class TransactionMatcher
{
    private const DATE_WINDOW_DAYS = 7;

    private const MIN_SCORE = 50;

    private const MAX_SUGGESTIONS = 3;

    /**
     * @return Collection<int, array{transaction: Transaction, score: int}>
     */
    public function suggestionsFor(Transaction $plaidCandidate): Collection
    {
        $date = $plaidCandidate->posted_date ?? $plaidCandidate->pending_date;
        if ($date === null) {
            return collect();
        }

        $candidates = Transaction::query()
            ->with(['account', 'category'])
            ->where('source', 'manual')
            ->whereIn('status', ['pending', 'posted'])
            ->whereNull('plaid_transaction_id')
            ->where(function ($q) use ($plaidCandidate) {
                $absAmount = abs($plaidCandidate->amount_cents);
                $q->whereBetween('amount_cents', [$plaidCandidate->amount_cents - 500, $plaidCandidate->amount_cents + 500])
                  // Allow sign-flipped manual entries (user logged it as positive, plaid is negative or vice-versa).
                    ->orWhereBetween('amount_cents', [-$absAmount - 500, -$absAmount + 500]);
            })
            ->where(function ($q) use ($date) {
                $start = $date->copy()->subDays(self::DATE_WINDOW_DAYS);
                $end = $date->copy()->addDays(self::DATE_WINDOW_DAYS);
                $q->whereBetween('posted_date', [$start, $end])
                    ->orWhereBetween('pending_date', [$start, $end]);
            })
            ->limit(20)
            ->get();

        return $candidates
            ->map(fn (Transaction $tx) => [
                'transaction' => $tx,
                'score' => $this->score($plaidCandidate, $tx),
            ])
            ->filter(fn (array $row) => $row['score'] >= self::MIN_SCORE)
            ->sortByDesc('score')
            ->values()
            ->take(self::MAX_SUGGESTIONS);
    }

    private function score(Transaction $plaid, Transaction $manual): int
    {
        $score = 0;

        $amountDiff = abs(abs($plaid->amount_cents) - abs($manual->amount_cents));
        if ($amountDiff === 0) {
            $score += 60;
        } elseif ($amountDiff <= 500) {
            $score += 35;
        }

        if ($plaid->account_id === $manual->account_id) {
            $score += 20;
        }

        $plaidDate = $plaid->posted_date ?? $plaid->pending_date;
        $manualDate = $manual->posted_date ?? $manual->pending_date;
        if ($plaidDate && $manualDate) {
            $days = abs((int) $plaidDate->diffInDays($manualDate, true));
            if ($days <= self::DATE_WINDOW_DAYS) {
                $score += (int) max(0, 15 - 2 * $days);
            }
        }

        $score += $this->merchantOverlap($plaid, $manual);

        return $score;
    }

    private function merchantOverlap(Transaction $plaid, Transaction $manual): int
    {
        $plaidTokens = $this->tokenize($plaid->merchant_name ?? $plaid->description);
        $manualTokens = $this->tokenize($manual->merchant_name ?? $manual->description);
        if ($plaidTokens === [] || $manualTokens === []) {
            return 0;
        }

        $overlap = count(array_intersect($plaidTokens, $manualTokens));

        return min(5, $overlap * 2);
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(?string $text): array
    {
        if ($text === null || $text === '') {
            return [];
        }

        $lower = mb_strtolower($text);
        $cleaned = preg_replace('/[^a-z0-9 ]+/', ' ', $lower) ?? '';
        $tokens = array_filter(
            preg_split('/\s+/', $cleaned) ?: [],
            fn (string $token) => mb_strlen($token) >= 3,
        );

        return array_values(array_unique($tokens));
    }
}
