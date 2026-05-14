import { Head, Link } from '@inertiajs/react';
import { MoneyFormat } from '@/components/budget/money-format';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { AccountRow } from '@/types/budget';

const KIND_LABELS: Record<string, string> = {
    checking: 'Checking',
    savings: 'Savings',
    credit_card: 'Credit card',
    mortgage: 'Mortgage',
    auto_loan: 'Auto loan',
    student_loan: 'Student loan',
    cash: 'Cash',
    other_liability: 'Other liability',
    other_asset: 'Other asset',
};

function groupOf(
    kind: AccountRow['kind'],
): 'spending' | 'savings' | 'liability' {
    if (kind === 'savings') {
        return 'savings';
    }
    if (
        kind === 'credit_card' ||
        kind === 'mortgage' ||
        kind === 'auto_loan' ||
        kind === 'student_loan' ||
        kind === 'other_liability'
    ) {
        return 'liability';
    }
    return 'spending';
}

export default function AccountsIndex({
    accounts,
}: {
    accounts: AccountRow[];
}) {
    const groups = {
        spending: accounts.filter((a) => groupOf(a.kind) === 'spending'),
        savings: accounts.filter((a) => groupOf(a.kind) === 'savings'),
        liability: accounts.filter((a) => groupOf(a.kind) === 'liability'),
    };

    const netWorth = accounts.reduce(
        (sum, a) =>
            sum +
            (a.is_liability
                ? -a.current_balance_cents
                : a.current_balance_cents),
        0,
    );

    return (
        <>
            <Head title="Accounts" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Accounts"
                        description="Balances by account, grouped by type."
                    />
                    <Button asChild>
                        <Link href="/accounts/create">Add account</Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="font-medium text-muted-foreground text-sm">
                            Net worth
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="font-semibold text-3xl">
                            <MoneyFormat cents={netWorth} colorize />
                        </div>
                    </CardContent>
                </Card>

                {(['spending', 'savings', 'liability'] as const).map(
                    (group) => (
                        <AccountGroup
                            key={group}
                            title={
                                group === 'spending'
                                    ? 'Spending'
                                    : group === 'savings'
                                      ? 'Savings'
                                      : 'Liabilities'
                            }
                            accounts={groups[group]}
                        />
                    ),
                )}
            </div>
        </>
    );
}

function AccountGroup({
    title,
    accounts,
}: {
    title: string;
    accounts: AccountRow[];
}) {
    if (accounts.length === 0) {
        return null;
    }
    return (
        <div className="space-y-2">
            <h3 className="font-medium text-muted-foreground text-sm">
                {title}
            </h3>
            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                {accounts.map((a) => (
                    <Card key={a.id}>
                        <CardHeader className="space-y-1">
                            <CardTitle className="flex items-center justify-between text-base">
                                <span>{a.name}</span>
                                {!a.is_active && (
                                    <Badge variant="outline">Inactive</Badge>
                                )}
                            </CardTitle>
                            <p className="text-muted-foreground text-xs">
                                {KIND_LABELS[a.kind] ?? a.kind}
                                {a.subkind ? ` · ${a.subkind}` : ''}
                                {a.mask ? ` · ····${a.mask}` : ''}
                            </p>
                        </CardHeader>
                        <CardContent>
                            <div className="font-semibold text-xl">
                                <MoneyFormat
                                    cents={
                                        a.is_liability
                                            ? -a.current_balance_cents
                                            : a.current_balance_cents
                                    }
                                    colorize
                                />
                            </div>
                            {a.available_balance_cents !== null && (
                                <div className="mt-1 text-muted-foreground text-xs">
                                    Available:{' '}
                                    <MoneyFormat
                                        cents={a.available_balance_cents}
                                    />
                                </div>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}

AccountsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Accounts', href: '/accounts' },
    ],
};
