import { Head, Link } from '@inertiajs/react';
import { MoneyFormat } from '@/components/budget/money-format';
import { NextObligationsStrip } from '@/components/budget/next-obligations-strip';
import { SafeToSpendCard } from '@/components/budget/safe-to-spend-card';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type {
    BillsBeforeIncome,
    BufferZone,
    ObligationInstanceRow,
    SafeToSpend,
    TransactionRow,
} from '@/types/budget';

type Props = {
    safeToSpend: SafeToSpend;
    bufferZone: BufferZone;
    upcomingObligations: ObligationInstanceRow[];
    pendingTransactions: TransactionRow[];
    recentTransactions: TransactionRow[];
    netWorthCents: number;
    billsBeforeIncome: BillsBeforeIncome;
};

export default function Dashboard({
    safeToSpend,
    bufferZone,
    upcomingObligations,
    pendingTransactions,
    recentTransactions,
    netWorthCents,
    billsBeforeIncome,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {billsBeforeIncome.has_shortfall && (
                    <Alert variant="destructive">
                        <AlertTitle>
                            Coverage gap before next paycheck
                        </AlertTitle>
                        <AlertDescription>
                            {billsBeforeIncome.count} bill
                            {billsBeforeIncome.count === 1 ? '' : 's'} totaling{' '}
                            <MoneyFormat
                                cents={billsBeforeIncome.outflow_cents}
                            />{' '}
                            hit before income arrives — short{' '}
                            <MoneyFormat
                                cents={billsBeforeIncome.coverage_gap_cents}
                            />
                            .
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="md:col-span-2">
                        <SafeToSpendCard
                            safeToSpend={safeToSpend}
                            zone={bufferZone}
                        />
                    </div>
                    <Card>
                        <CardHeader>
                            <CardTitle className="font-medium text-muted-foreground text-sm">
                                Net worth
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="font-semibold text-2xl">
                                <MoneyFormat cents={netWorthCents} colorize />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <NextObligationsStrip obligations={upcomingObligations} />

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0">
                            <CardTitle className="font-medium text-muted-foreground text-sm">
                                Pending transactions
                            </CardTitle>
                            <Button asChild size="sm" variant="outline">
                                <Link href="/transactions/create">
                                    Add transaction
                                </Link>
                            </Button>
                        </CardHeader>
                        <CardContent>
                            {pendingTransactions.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    Nothing pending.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {pendingTransactions.map((t) => (
                                        <li
                                            key={t.id}
                                            className="flex items-center justify-between py-2 text-sm"
                                        >
                                            <div className="min-w-0">
                                                <div className="truncate font-medium">
                                                    {t.description}
                                                </div>
                                                <div className="text-muted-foreground text-xs">
                                                    {t.account_name}
                                                </div>
                                            </div>
                                            <MoneyFormat
                                                cents={t.amount_cents}
                                                colorize
                                            />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="font-medium text-muted-foreground text-sm">
                            Recent transactions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {recentTransactions.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No posted transactions yet.{' '}
                                <Link
                                    href="/accounts/create"
                                    className="underline"
                                >
                                    Add an account
                                </Link>{' '}
                                or{' '}
                                <Link
                                    href="/transactions/create"
                                    className="underline"
                                >
                                    log a transaction
                                </Link>{' '}
                                to get started.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {recentTransactions.map((t) => (
                                    <li
                                        key={t.id}
                                        className="flex items-center justify-between py-2 text-sm"
                                    >
                                        <div className="min-w-0">
                                            <div className="truncate font-medium">
                                                {t.description}
                                            </div>
                                            <div className="text-muted-foreground text-xs">
                                                {t.date} · {t.account_name}
                                                {t.category_name
                                                    ? ` · ${t.category_name}`
                                                    : ''}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge
                                                variant="outline"
                                                className="text-[10px]"
                                            >
                                                {t.source}
                                            </Badge>
                                            <MoneyFormat
                                                cents={t.amount_cents}
                                                colorize
                                            />
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
};
