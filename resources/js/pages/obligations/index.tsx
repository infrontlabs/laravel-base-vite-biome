import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { MoneyFormat } from '@/components/budget/money-format';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ObligationInstanceRow, ObligationRow } from '@/types/budget';

type Props = {
    obligations: ObligationRow[];
    upcoming: ObligationInstanceRow[];
};

const KIND_LABELS: Record<string, string> = {
    bill: 'Bill',
    subscription: 'Subscription',
    paycheck: 'Paycheck',
    savings_transfer: 'Savings transfer',
    debt_payment: 'Debt payment',
    other: 'Other',
};

const FREQ_LABELS: Record<string, string> = {
    weekly: 'Weekly',
    biweekly: 'Biweekly',
    semimonthly: 'Semi-monthly',
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    annual: 'Annual',
    custom: 'Custom',
};

export default function ObligationsIndex({ obligations, upcoming }: Props) {
    const [tab, setTab] = useState<'schedule' | 'upcoming'>('schedule');

    return (
        <>
            <Head title="Obligations" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Scheduled obligations"
                        description="Bills, subscriptions, paychecks, planned transfers — anything that should affect the forecast."
                    />
                    <Button asChild>
                        <Link href="/obligations/create">Add obligation</Link>
                    </Button>
                </div>

                <div className="flex gap-2">
                    <Button
                        variant={tab === 'schedule' ? 'default' : 'outline'}
                        onClick={() => setTab('schedule')}
                        size="sm"
                    >
                        Schedule
                    </Button>
                    <Button
                        variant={tab === 'upcoming' ? 'default' : 'outline'}
                        onClick={() => setTab('upcoming')}
                        size="sm"
                    >
                        Upcoming 90 days
                    </Button>
                </div>

                {tab === 'schedule' ? (
                    <Card>
                        <CardContent className="p-0">
                            {obligations.length === 0 ? (
                                <p className="p-6 text-muted-foreground text-sm">
                                    No obligations yet. Add a paycheck or
                                    recurring bill to drive the forecast.
                                </p>
                            ) : (
                                <table className="w-full text-sm">
                                    <thead className="border-b">
                                        <tr className="text-left text-muted-foreground text-xs">
                                            <th className="px-4 py-2 font-medium">
                                                Name
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                Kind
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                Cadence
                                            </th>
                                            <th className="px-4 py-2 font-medium">
                                                Account
                                            </th>
                                            <th className="px-4 py-2 text-right font-medium">
                                                Amount
                                            </th>
                                            <th className="px-4 py-2 font-medium" />
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {obligations.map((o) => (
                                            <tr key={o.id} className="border-b">
                                                <td className="px-4 py-2">
                                                    <Link
                                                        href={`/obligations/${o.id}/edit`}
                                                        className="hover:underline"
                                                    >
                                                        {o.name}
                                                    </Link>
                                                    {!o.is_active && (
                                                        <Badge
                                                            variant="outline"
                                                            className="ml-2 text-[10px]"
                                                        >
                                                            paused
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="px-4 py-2 text-muted-foreground">
                                                    {KIND_LABELS[o.kind] ??
                                                        o.kind}
                                                </td>
                                                <td className="px-4 py-2 text-muted-foreground">
                                                    {FREQ_LABELS[o.frequency] ??
                                                        o.frequency}
                                                </td>
                                                <td className="px-4 py-2 text-muted-foreground">
                                                    {o.account_name}
                                                </td>
                                                <td className="px-4 py-2 text-right">
                                                    <MoneyFormat
                                                        cents={
                                                            o.direction ===
                                                            'outflow'
                                                                ? -o.amount_cents
                                                                : o.amount_cents
                                                        }
                                                        showSign
                                                        colorize
                                                    />
                                                </td>
                                                <td className="px-4 py-2 text-right">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => {
                                                            if (
                                                                confirm(
                                                                    `Delete "${o.name}"?`,
                                                                )
                                                            ) {
                                                                router.delete(
                                                                    `/obligations/${o.id}`,
                                                                );
                                                            }
                                                        }}
                                                    >
                                                        Delete
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardHeader>
                            <CardTitle className="font-medium text-muted-foreground text-sm">
                                Next 90 days
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {upcoming.length === 0 ? (
                                <p className="text-muted-foreground text-sm">
                                    Nothing scheduled in the next 90 days.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {upcoming.map((i) => {
                                        const sign =
                                            i.direction === 'outflow' ? -1 : 1;
                                        return (
                                            <li
                                                key={i.id}
                                                className="flex items-center justify-between gap-3 py-2 text-sm"
                                            >
                                                <div className="min-w-0">
                                                    <div className="truncate font-medium">
                                                        {i.name}
                                                    </div>
                                                    <div className="text-muted-foreground text-xs">
                                                        {i.due_date}
                                                        {i.account_name
                                                            ? ` · ${i.account_name}`
                                                            : ''}
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-3">
                                                    <Button
                                                        size="sm"
                                                        variant="ghost"
                                                        onClick={() => {
                                                            router.post(
                                                                `/obligation-instances/${i.id}/skip`,
                                                            );
                                                        }}
                                                    >
                                                        Skip
                                                    </Button>
                                                    <MoneyFormat
                                                        cents={
                                                            sign *
                                                            i.amount_cents
                                                        }
                                                        showSign
                                                        colorize
                                                    />
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

ObligationsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Obligations', href: '/obligations' },
    ],
};
