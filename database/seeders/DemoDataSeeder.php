<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\ObligationInstance;
use App\Models\ScheduledObligation;
use App\Models\Transaction;
use App\Services\Budget\ObligationMaterializer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $today = CarbonImmutable::today();

        $accounts = $this->seedAccounts();
        $this->seedObligations($accounts, $today);
        $this->seedTransactions($accounts, $today);

        app(ObligationMaterializer::class)->run($today);

        $this->matchPastInstancesToTransactions();
    }

    /**
     * @return array<string, Account>
     */
    private function seedAccounts(): array
    {
        $rows = [
            [
                'key' => 'checking',
                'name' => 'Everyday Checking',
                'kind' => 'depository',
                'subkind' => 'checking',
                'current_balance_cents' => 320_000,
                'available_balance_cents' => 320_000,
                'is_liability' => false,
                'mask' => '4821',
                'position' => 0,
            ],
            [
                'key' => 'savings',
                'name' => 'High Yield Savings',
                'kind' => 'depository',
                'subkind' => 'savings',
                'current_balance_cents' => 850_000,
                'available_balance_cents' => 850_000,
                'is_liability' => false,
                'mask' => '9012',
                'include_in_safe_to_spend' => false,
                'position' => 1,
            ],
            [
                'key' => 'credit',
                'name' => 'Sapphire Credit',
                'kind' => 'credit',
                'subkind' => 'credit_card',
                'current_balance_cents' => -125_000,
                'available_balance_cents' => null,
                'is_liability' => true,
                'mask' => '7733',
                'include_in_safe_to_spend' => false,
                'position' => 2,
            ],
        ];

        $accounts = [];
        foreach ($rows as $row) {
            $key = $row['key'];
            unset($row['key']);
            $accounts[$key] = Account::updateOrCreate(
                ['name' => $row['name']],
                $row + ['as_of' => CarbonImmutable::now(), 'is_active' => true, 'manual_only' => true],
            );
        }

        return $accounts;
    }

    /**
     * @param  array<string, Account>  $accounts
     * @return array<string, ScheduledObligation>
     */
    private function seedObligations(array $accounts, CarbonImmutable $today): array
    {
        $cat = $this->categoryMap();
        $threeMonthsAgo = $today->subMonths(3)->startOfMonth();

        $rows = [
            [
                'key' => 'salary',
                'name' => 'Acme Payroll',
                'kind' => 'paycheck',
                'direction' => 'inflow',
                'account_id' => $accounts['checking']->id,
                'category_id' => $cat['income'] ?? null,
                'amount_cents' => 450_000,
                'frequency' => 'biweekly',
                'anchor_date' => $threeMonthsAgo->toDateString(),
            ],
            [
                'key' => 'rent',
                'name' => 'Rent',
                'kind' => 'bill',
                'direction' => 'outflow',
                'account_id' => $accounts['checking']->id,
                'category_id' => $cat['rent-and-utilities'] ?? null,
                'amount_cents' => 180_000,
                'frequency' => 'monthly',
                'anchor_date' => $threeMonthsAgo->setDay(1)->toDateString(),
                'day_of_month' => 1,
                'autopay' => true,
            ],
            [
                'key' => 'electric',
                'name' => 'ConEd Electric',
                'kind' => 'bill',
                'direction' => 'outflow',
                'account_id' => $accounts['checking']->id,
                'category_id' => $cat['rent-and-utilities'] ?? null,
                'amount_cents' => 12_000,
                'frequency' => 'monthly',
                'anchor_date' => $threeMonthsAgo->setDay(20)->toDateString(),
                'day_of_month' => 20,
                'autopay' => true,
            ],
            [
                'key' => 'netflix',
                'name' => 'Netflix',
                'kind' => 'subscription',
                'direction' => 'outflow',
                'account_id' => $accounts['credit']->id,
                'category_id' => $cat['entertainment'] ?? null,
                'amount_cents' => 1_599,
                'frequency' => 'monthly',
                'anchor_date' => $threeMonthsAgo->setDay(15)->toDateString(),
                'day_of_month' => 15,
                'autopay' => true,
                'cancel_url' => 'https://www.netflix.com/cancelplan',
                'last_reviewed_at' => $today->subMonths(2)->toDateString(),
            ],
            [
                'key' => 'spotify',
                'name' => 'Spotify Family',
                'kind' => 'subscription',
                'direction' => 'outflow',
                'account_id' => $accounts['credit']->id,
                'category_id' => $cat['entertainment'] ?? null,
                'amount_cents' => 1_699,
                'frequency' => 'monthly',
                'anchor_date' => $threeMonthsAgo->setDay(10)->toDateString(),
                'day_of_month' => 10,
                'autopay' => true,
                'cancel_url' => 'https://www.spotify.com/account/subscription/',
            ],
            [
                'key' => 'gym',
                'name' => 'Equinox',
                'kind' => 'subscription',
                'direction' => 'outflow',
                'account_id' => $accounts['credit']->id,
                'category_id' => $cat['personal-care'] ?? null,
                'amount_cents' => 25_000,
                'frequency' => 'monthly',
                'anchor_date' => $threeMonthsAgo->setDay(5)->toDateString(),
                'day_of_month' => 5,
                'autopay' => true,
            ],
            [
                'key' => 'savings_transfer',
                'name' => 'Auto-save to Emergency Fund',
                'kind' => 'savings_transfer',
                'direction' => 'outflow',
                'account_id' => $accounts['checking']->id,
                'category_id' => $cat['transfer-out'] ?? null,
                'amount_cents' => 50_000,
                'frequency' => 'monthly',
                'anchor_date' => $threeMonthsAgo->setDay(2)->toDateString(),
                'day_of_month' => 2,
                'autopay' => true,
            ],
        ];

        $obligations = [];
        foreach ($rows as $row) {
            $key = $row['key'];
            unset($row['key']);
            $obligations[$key] = ScheduledObligation::updateOrCreate(
                ['name' => $row['name']],
                $row + ['currency' => 'USD', 'interval' => 1, 'is_active' => true],
            );
        }

        return $obligations;
    }

    /**
     * @param  array<string, Account>  $accounts
     */
    private function seedTransactions(array $accounts, CarbonImmutable $today): void
    {
        $cat = $this->categoryMap();
        $checking = $accounts['checking']->id;
        $credit = $accounts['credit']->id;

        $rows = [];

        // Biweekly paychecks (3 months back, 7 occurrences).
        for ($i = 0; $i < 7; $i++) {
            $date = $today->subMonths(3)->startOfMonth()->addWeeks($i * 2);
            if ($date->gt($today)) {
                break;
            }
            $rows[] = [
                'account_id' => $checking,
                'category_id' => $cat['income'] ?? null,
                'amount_cents' => 450_000,
                'posted_date' => $date->toDateString(),
                'description' => 'ACME PAYROLL DIRECT DEPOSIT',
                'merchant_name' => 'Acme Inc.',
            ];
        }

        // Monthly recurring obligations — one per past month.
        for ($monthsBack = 3; $monthsBack >= 1; $monthsBack--) {
            $base = $today->subMonths($monthsBack)->startOfMonth();

            $rows[] = [
                'account_id' => $checking,
                'category_id' => $cat['rent-and-utilities'] ?? null,
                'amount_cents' => -180_000,
                'posted_date' => $base->setDay(1)->toDateString(),
                'description' => 'Pinecrest Property Mgmt — Rent',
                'merchant_name' => 'Pinecrest Property',
            ];
            $rows[] = [
                'account_id' => $checking,
                'category_id' => $cat['transfer-out'] ?? null,
                'amount_cents' => -50_000,
                'posted_date' => $base->setDay(2)->toDateString(),
                'description' => 'Transfer to Savings',
                'merchant_name' => null,
            ];
            $rows[] = [
                'account_id' => $credit,
                'category_id' => $cat['personal-care'] ?? null,
                'amount_cents' => -25_000,
                'posted_date' => $base->setDay(5)->toDateString(),
                'description' => 'EQUINOX MEMBERSHIP',
                'merchant_name' => 'Equinox',
            ];
            $rows[] = [
                'account_id' => $credit,
                'category_id' => $cat['entertainment'] ?? null,
                'amount_cents' => -1_699,
                'posted_date' => $base->setDay(10)->toDateString(),
                'description' => 'SPOTIFY USA',
                'merchant_name' => 'Spotify',
            ];
            $rows[] = [
                'account_id' => $credit,
                'category_id' => $cat['entertainment'] ?? null,
                'amount_cents' => -1_599,
                'posted_date' => $base->setDay(15)->toDateString(),
                'description' => 'NETFLIX.COM',
                'merchant_name' => 'Netflix',
            ];
            $rows[] = [
                'account_id' => $checking,
                'category_id' => $cat['rent-and-utilities'] ?? null,
                'amount_cents' => -12_000 - random_int(-1500, 2500),
                'posted_date' => $base->setDay(20)->toDateString(),
                'description' => 'CONED ELECTRIC AUTOPAY',
                'merchant_name' => 'ConEd',
            ];

            // Variable spending across the month.
            $rows = array_merge($rows, $this->variableSpending($base, $credit, $checking, $cat));
        }

        // A handful of current-month transactions.
        $thisMonth = $today->startOfMonth();
        if ($thisMonth->day < $today->day) {
            $rows = array_merge($rows, $this->variableSpending($thisMonth, $credit, $checking, $cat, partial: true, today: $today));
        }

        foreach ($rows as $row) {
            Transaction::updateOrCreate(
                [
                    'account_id' => $row['account_id'],
                    'posted_date' => $row['posted_date'],
                    'description' => $row['description'],
                ],
                $row + [
                    'currency' => 'USD',
                    'source' => 'manual',
                    'status' => 'posted',
                ],
            );
        }
    }

    /**
     * @param  array<string, int>  $cat
     * @return array<int, array<string, mixed>>
     */
    private function variableSpending(
        CarbonImmutable $monthStart,
        int $creditId,
        int $checkingId,
        array $cat,
        bool $partial = false,
        ?CarbonImmutable $today = null,
    ): array {
        $rows = [];
        $merchants = [
            ['Whole Foods Market', 'food-and-drink', $creditId, [-14_000, -6_500]],
            ['Trader Joe\'s', 'food-and-drink', $creditId, [-8_800, -3_200]],
            ['Chipotle', 'food-and-drink', $creditId, [-2_800, -1_200]],
            ['Starbucks', 'food-and-drink', $creditId, [-1_200, -450]],
            ['Uber', 'transportation', $creditId, [-2_400, -800]],
            ['Shell Gas', 'transportation', $creditId, [-7_500, -3_500]],
            ['Amazon', 'general-merchandise', $creditId, [-8_900, -1_500]],
            ['Target', 'general-merchandise', $creditId, [-9_200, -2_400]],
            ['CVS Pharmacy', 'medical', $checkingId, [-3_800, -1_100]],
            ['Home Depot', 'home-improvement', $creditId, [-12_000, -3_000]],
        ];

        $daysInMonth = $monthStart->daysInMonth;
        $cap = $partial && $today ? min($daysInMonth, $today->day) : $daysInMonth;

        // 12-20 charges per month.
        $count = $partial ? max(3, (int) ($cap / 2)) : random_int(12, 20);
        for ($i = 0; $i < $count; $i++) {
            $merchant = $merchants[array_rand($merchants)];
            $day = random_int(1, $cap);
            [$min, $max] = $merchant[3];
            $rows[] = [
                'account_id' => $merchant[2],
                'category_id' => $cat[$merchant[1]] ?? null,
                'amount_cents' => random_int($min, $max),
                'posted_date' => $monthStart->setDay($day)->toDateString(),
                'description' => strtoupper($merchant[0]).' #'.random_int(100, 999),
                'merchant_name' => $merchant[0],
            ];
        }

        return $rows;
    }

    /**
     * Backfill `obligation_instances.transaction_id` so past instances appear matched.
     */
    private function matchPastInstancesToTransactions(): void
    {
        $today = CarbonImmutable::today();

        ObligationInstance::query()
            ->whereDate('due_date', '<', $today->toDateString())
            ->whereNull('transaction_id')
            ->with('obligation')
            ->get()
            ->each(function (ObligationInstance $instance): void {
                $obligation = $instance->obligation;
                $expected = $obligation->direction === 'outflow'
                    ? -$obligation->amount_cents
                    : $obligation->amount_cents;

                $tx = Transaction::query()
                    ->where('account_id', $obligation->account_id)
                    ->where('amount_cents', $expected)
                    ->whereDate('posted_date', '>=', $instance->due_date->copy()->subDays(3))
                    ->whereDate('posted_date', '<=', $instance->due_date->copy()->addDays(3))
                    ->first();

                if ($tx) {
                    $instance->forceFill([
                        'transaction_id' => $tx->id,
                        'status' => 'matched',
                        'matched_at' => now(),
                    ])->save();
                }
            });
    }

    /**
     * @return array<string, int>
     */
    private function categoryMap(): array
    {
        return Category::query()->pluck('id', 'slug')->all();
    }
}
