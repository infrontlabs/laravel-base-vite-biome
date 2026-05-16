<?php

namespace App\Services\Sync;

use App\Models\Account;
use App\Models\ScheduledObligation;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Generates simulated Plaid transaction candidates.
 *
 * Pulls from active scheduled obligations within a recent window so that
 * most candidates have a real manual counterpart to match against, then
 * sprinkles in a few "extras" so the unmatched/accept-new flow has data
 * to exercise too.
 */
class SimulatedPlaidSync
{
    private const PLAID_ITEM_ID = 'sim_item_brnfmly';

    /**
     * @return array{created:int, item_id:string}
     */
    public function run(?CarbonImmutable $today = null): array
    {
        $today = $today ?? CarbonImmutable::today();
        $windowStart = $today->subDays(14);

        $created = 0;
        $created += $this->pullFromObligations($windowStart, $today);
        $created += $this->generateExtras($windowStart, $today);

        return ['created' => $created, 'item_id' => self::PLAID_ITEM_ID];
    }

    private function pullFromObligations(CarbonImmutable $windowStart, CarbonImmutable $today): int
    {
        $obligations = ScheduledObligation::query()
            ->where('is_active', true)
            ->with('account')
            ->get();

        $created = 0;

        foreach ($obligations as $obligation) {
            $occurrences = $obligation->occurrencesBetween($windowStart, $today);
            foreach ($occurrences as $occurrence) {
                // Plaid posts arrive 0-3 days after the scheduled date in real life.
                $postedDate = $occurrence->addDays(random_int(0, 2));
                if ($postedDate->gt($today)) {
                    continue;
                }

                $amountCents = $obligation->direction === 'outflow'
                    ? -$obligation->amount_cents
                    : $obligation->amount_cents;

                // Add a tiny jitter to a couple obligations so amount-based matching
                // is exercised both in the happy path and in the "close but not exact" path.
                if ($obligation->kind === 'bill' && random_int(0, 3) === 0) {
                    $amountCents += random_int(-200, 200);
                }

                if ($this->candidateAlreadyExists($obligation->account_id, $amountCents, $postedDate, $obligation->name)) {
                    continue;
                }

                Transaction::create([
                    'account_id' => $obligation->account_id,
                    'amount_cents' => $amountCents,
                    'currency' => $obligation->currency ?? 'USD',
                    'posted_date' => $postedDate->toDateString(),
                    'authorized_date' => $occurrence->toDateString(),
                    'description' => $this->plaidify($obligation->name),
                    'merchant_name' => $obligation->name,
                    'source' => 'plaid',
                    'status' => 'pending',
                    'plaid_item_id' => self::PLAID_ITEM_ID,
                    'plaid_transaction_id' => 'sim_'.Str::random(20),
                ]);
                $created++;
            }
        }

        return $created;
    }

    private function generateExtras(CarbonImmutable $windowStart, CarbonImmutable $today): int
    {
        $accounts = Account::query()
            ->where('is_active', true)
            ->whereIn('kind', ['depository', 'credit', 'checking', 'savings', 'credit_card'])
            ->get();

        if ($accounts->isEmpty()) {
            return 0;
        }

        $merchants = [
            ['AMAZON.COM*MK7Y2', 'Amazon', -4_299],
            ['STARBUCKS STORE 4421', 'Starbucks', -687],
            ['UBER *TRIP HELP.UBER.COM', 'Uber', -1_842],
            ['DOORDASH*CHIPOTLE', 'DoorDash', -2_318],
            ['SHELL OIL 57442910', 'Shell', -5_134],
        ];

        $windowDays = max(0, (int) $windowStart->diffInDays($today, absolute: true));
        $count = random_int(3, 5);
        $created = 0;
        for ($i = 0; $i < $count; $i++) {
            $merchant = $merchants[array_rand($merchants)];
            $account = $accounts->random();
            $postedDate = $windowStart->addDays(random_int(0, $windowDays));

            Transaction::create([
                'account_id' => $account->id,
                'amount_cents' => $merchant[2] + random_int(-300, 300),
                'currency' => 'USD',
                'posted_date' => $postedDate->toDateString(),
                'description' => $merchant[0],
                'merchant_name' => $merchant[1],
                'source' => 'plaid',
                'status' => 'pending',
                'plaid_item_id' => self::PLAID_ITEM_ID,
                'plaid_transaction_id' => 'sim_'.Str::random(20),
            ]);
            $created++;
        }

        return $created;
    }

    private function candidateAlreadyExists(int $accountId, int $amountCents, CarbonImmutable $postedDate, string $merchantName): bool
    {
        return Transaction::query()
            ->where('source', 'plaid')
            ->where('status', 'pending')
            ->where('account_id', $accountId)
            ->where('amount_cents', $amountCents)
            ->where('merchant_name', $merchantName)
            ->whereDate('posted_date', $postedDate->toDateString())
            ->exists();
    }

    private function plaidify(string $name): string
    {
        return Str::upper($name).' '.random_int(1000, 9999);
    }
}
