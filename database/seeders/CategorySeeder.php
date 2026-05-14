<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Mapped from Plaid's `personal_finance_category.primary` taxonomy.
     * Identity mapping at ingest: lookup categories.where('plaid_primary', $primary).
     */
    public function run(): void
    {
        $rows = [
            ['INCOME', 'Income', 'income', 'CircleDollarSign'],
            ['TRANSFER_IN', 'Transfer In', 'transfer', 'ArrowDownToLine'],
            ['TRANSFER_OUT', 'Transfer Out', 'transfer', 'ArrowUpFromLine'],
            ['LOAN_PAYMENTS', 'Loan Payments', 'debt_payment', 'Landmark'],
            ['BANK_FEES', 'Bank Fees', 'fixed', 'Banknote'],
            ['ENTERTAINMENT', 'Entertainment', 'flexible', 'Film'],
            ['FOOD_AND_DRINK', 'Food & Drink', 'flexible', 'UtensilsCrossed'],
            ['GENERAL_MERCHANDISE', 'General Merchandise', 'flexible', 'ShoppingBag'],
            ['HOME_IMPROVEMENT', 'Home Improvement', 'flexible', 'Hammer'],
            ['MEDICAL', 'Medical', 'flexible', 'HeartPulse'],
            ['PERSONAL_CARE', 'Personal Care', 'flexible', 'Scissors'],
            ['GENERAL_SERVICES', 'General Services', 'flexible', 'Wrench'],
            ['GOVERNMENT_AND_NON_PROFIT', 'Government & Non-Profit', 'fixed', 'Building2'],
            ['TRANSPORTATION', 'Transportation', 'flexible', 'Car'],
            ['TRAVEL', 'Travel', 'flexible', 'Plane'],
            ['RENT_AND_UTILITIES', 'Rent & Utilities', 'fixed', 'House'],
        ];

        foreach ($rows as $i => [$primary, $name, $group, $icon]) {
            Category::updateOrCreate(
                ['slug' => str($primary)->lower()->slug()->toString()],
                [
                    'name' => $name,
                    'group' => $group,
                    'plaid_primary' => $primary,
                    'icon' => $icon,
                    'position' => $i,
                ],
            );
        }

        Category::updateOrCreate(
            ['slug' => 'uncategorized'],
            [
                'name' => 'Uncategorized',
                'group' => 'flexible',
                'icon' => 'CircleHelp',
                'position' => 99,
            ],
        );
    }
}
