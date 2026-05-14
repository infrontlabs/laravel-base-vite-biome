export type AccountKind =
    | 'checking'
    | 'savings'
    | 'credit_card'
    | 'mortgage'
    | 'auto_loan'
    | 'student_loan'
    | 'cash'
    | 'other_liability'
    | 'other_asset';

export type AccountOption = {
    id: number;
    name: string;
    kind: AccountKind;
};

export type AccountRow = AccountOption & {
    subkind: string | null;
    current_balance_cents: number;
    available_balance_cents: number | null;
    is_liability: boolean;
    is_active: boolean;
    manual_only: boolean;
    include_in_safe_to_spend: boolean;
    include_in_net_worth: boolean;
    mask: string | null;
};

export type CategoryGroup =
    | 'fixed'
    | 'flexible'
    | 'income'
    | 'transfer'
    | 'savings'
    | 'debt_payment';

export type CategoryOption = {
    id: number;
    name: string;
    group: CategoryGroup;
    icon: string | null;
};

export type CategoryRow = CategoryOption & {
    slug: string;
    color: string | null;
    monthly_target_cents: number | null;
    mtd_actual_cents: number;
    trailing_3mo_avg_cents: number;
};

export type TransactionStatus = 'pending' | 'posted' | 'void';
export type TransactionSource = 'manual' | 'plaid' | 'merged';

export type TransactionRow = {
    id: number;
    description: string;
    amount_cents: number;
    date: string | null;
    status: TransactionStatus;
    source: TransactionSource;
    account_name: string | null;
    category_name: string | null;
};

export type RecurrenceFrequency =
    | 'weekly'
    | 'biweekly'
    | 'semimonthly'
    | 'monthly'
    | 'quarterly'
    | 'annual'
    | 'custom';

export type ObligationKind =
    | 'bill'
    | 'subscription'
    | 'paycheck'
    | 'savings_transfer'
    | 'debt_payment'
    | 'other';

export type ObligationDirection = 'inflow' | 'outflow';

export type ObligationRow = {
    id: number;
    name: string;
    kind: ObligationKind;
    direction: ObligationDirection;
    amount_cents: number;
    frequency: RecurrenceFrequency;
    interval: number;
    anchor_date: string;
    is_active: boolean;
    autopay: boolean;
    account_name: string | null;
    category_name: string | null;
};

export type ObligationInstanceRow = {
    id: number;
    name: string;
    kind: ObligationKind;
    direction: ObligationDirection;
    due_date: string;
    amount_cents: number;
    status: 'expected' | 'matched' | 'missed' | 'skipped';
    account_name: string | null;
};

export type SafeToSpendBreakdownItem = {
    label: string;
    amount_cents: number;
};

export type SafeToSpend = {
    safe_to_spend_cents: number;
    horizon_end: string;
    liquid_cents: number;
    pending_manual_outflows_cents: number;
    upcoming_obligations_cents: number;
    upcoming_inflows_cents: number;
    buffer_cents: number;
    breakdown: SafeToSpendBreakdownItem[];
};

export type BufferZone = 'red' | 'amber' | 'green';

export type BillsBeforeIncome = {
    has_shortfall: boolean;
    count: number;
    outflow_cents: number;
    liquid_cents: number;
    coverage_gap_cents: number;
};
