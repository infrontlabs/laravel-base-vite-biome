import { Head, Link, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { MoneyFormat } from '@/components/budget/money-format';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type {
    ObligationInstanceRow,
    ObligationKind,
    ObligationRow,
    SubscriptionRollup,
} from '@/types/budget';

type Props = {
    obligations: ObligationRow[];
    upcoming: ObligationInstanceRow[];
    subscriptionRollup: SubscriptionRollup;
};

type TabKey = 'bills' | 'subscriptions' | 'paychecks' | 'other' | 'upcoming';

const KIND_LABEL: Record<ObligationKind, string> = {
    bill: 'Bill',
    subscription: 'Subscription',
    paycheck: 'Paycheck',
    savings_transfer: 'Savings transfer',
    debt_payment: 'Debt payment',
    other: 'Other',
};

const FREQ_LABEL: Record<string, string> = {
    weekly: 'Weekly',
    biweekly: 'Biweekly',
    semimonthly: 'Semi-monthly',
    monthly: 'Monthly',
    quarterly: 'Quarterly',
    annual: 'Annual',
    custom: 'Custom',
};

function kindsForTab(tab: TabKey): ObligationKind[] {
    switch (tab) {
        case 'bills':
            return ['bill'];
        case 'subscriptions':
            return ['subscription'];
        case 'paychecks':
            return ['paycheck'];
        case 'other':
            return ['savings_transfer', 'debt_payment', 'other'];
        default:
            return [];
    }
}

export default function ObligationsIndex({
    obligations,
    upcoming,
    subscriptionRollup,
}: Props) {
    const [tab, setTab] = useState<TabKey>('bills');

    const counts = useMemo(() => {
        return {
            bills: obligations.filter((o) => o.kind === 'bill').length,
            subscriptions: obligations.filter((o) => o.kind === 'subscription')
                .length,
            paychecks: obligations.filter((o) => o.kind === 'paycheck').length,
            other: obligations.filter((o) =>
                ['savings_transfer', 'debt_payment', 'other'].includes(o.kind),
            ).length,
            upcoming: upcoming.length,
        };
    }, [obligations, upcoming]);

    const filtered = useMemo(() => {
        const kinds = kindsForTab(tab);
        return obligations.filter((o) => kinds.includes(o.kind));
    }, [obligations, tab]);

    return (
        <>
            <Head title="Recurring" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Recurring"
                        description="Anything that repeats: bills, subscriptions, paychecks, planned transfers."
                    />
                    <Button asChild>
                        <Link href="/obligations/create">Add recurring</Link>
                    </Button>
                </div>

                <div className="flex flex-wrap gap-2">
                    <TabButton
                        active={tab === 'bills'}
                        onClick={() => setTab('bills')}
                    >
                        Bills{' '}
                        <Badge variant="outline" className="ml-1">
                            {counts.bills}
                        </Badge>
                    </TabButton>
                    <TabButton
                        active={tab === 'subscriptions'}
                        onClick={() => setTab('subscriptions')}
                    >
                        Subscriptions{' '}
                        <Badge variant="outline" className="ml-1">
                            {counts.subscriptions}
                        </Badge>
                    </TabButton>
                    <TabButton
                        active={tab === 'paychecks'}
                        onClick={() => setTab('paychecks')}
                    >
                        Paychecks{' '}
                        <Badge variant="outline" className="ml-1">
                            {counts.paychecks}
                        </Badge>
                    </TabButton>
                    <TabButton
                        active={tab === 'other'}
                        onClick={() => setTab('other')}
                    >
                        Other{' '}
                        <Badge variant="outline" className="ml-1">
                            {counts.other}
                        </Badge>
                    </TabButton>
                    <TabButton
                        active={tab === 'upcoming'}
                        onClick={() => setTab('upcoming')}
                    >
                        Upcoming 90d{' '}
                        <Badge variant="outline" className="ml-1">
                            {counts.upcoming}
                        </Badge>
                    </TabButton>
                </div>

                {tab === 'subscriptions' && (
                    <SubscriptionsHeader rollup={subscriptionRollup} />
                )}

                {tab === 'upcoming' ? (
                    <UpcomingList upcoming={upcoming} />
                ) : (
                    <ObligationsTable
                        rows={filtered}
                        showSubscriptionExtras={tab === 'subscriptions'}
                    />
                )}
            </div>
        </>
    );
}

function TabButton({
    active,
    onClick,
    children,
}: {
    active: boolean;
    onClick: () => void;
    children: React.ReactNode;
}) {
    return (
        <Button
            size="sm"
            variant={active ? 'default' : 'outline'}
            onClick={onClick}
        >
            {children}
        </Button>
    );
}

function SubscriptionsHeader({ rollup }: { rollup: SubscriptionRollup }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="font-medium text-muted-foreground text-sm">
                    Subscription spend
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="grid gap-4 sm:grid-cols-3">
                    <Stat label="Per month" cents={rollup.monthly_cents} />
                    <Stat label="Annualized" cents={rollup.annual_cents} />
                    <div>
                        <p className="text-muted-foreground text-xs">
                            Active subscriptions
                        </p>
                        <p className="font-semibold text-2xl tabular-nums">
                            {rollup.count}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

function Stat({ label, cents }: { label: string; cents: number }) {
    return (
        <div>
            <p className="text-muted-foreground text-xs">{label}</p>
            <p className="font-semibold text-2xl tabular-nums">
                <MoneyFormat cents={cents} />
            </p>
        </div>
    );
}

function ObligationsTable({
    rows,
    showSubscriptionExtras,
}: {
    rows: ObligationRow[];
    showSubscriptionExtras: boolean;
}) {
    if (rows.length === 0) {
        return (
            <Card>
                <CardContent className="p-6 text-muted-foreground text-sm">
                    Nothing here yet.
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardContent className="p-0">
                <table className="w-full text-sm">
                    <thead className="border-b">
                        <tr className="text-left text-muted-foreground text-xs">
                            <th className="px-4 py-2 font-medium">Name</th>
                            <th className="px-4 py-2 font-medium">Cadence</th>
                            <th className="px-4 py-2 font-medium">Account</th>
                            {showSubscriptionExtras && (
                                <th className="px-4 py-2 font-medium">
                                    Last reviewed
                                </th>
                            )}
                            <th className="px-4 py-2 text-right font-medium">
                                Amount
                            </th>
                            <th className="px-4 py-2 font-medium" />
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((o) => (
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
                                    {o.autopay && (
                                        <Badge
                                            variant="outline"
                                            className="ml-2 text-[10px]"
                                        >
                                            autopay
                                        </Badge>
                                    )}
                                </td>
                                <td className="px-4 py-2 text-muted-foreground">
                                    {FREQ_LABEL[o.frequency] ?? o.frequency}
                                </td>
                                <td className="px-4 py-2 text-muted-foreground">
                                    {o.account_name}
                                </td>
                                {showSubscriptionExtras && (
                                    <td className="px-4 py-2 text-muted-foreground">
                                        {o.last_reviewed_at ?? (
                                            <span className="text-amber-700 dark:text-amber-400">
                                                never
                                            </span>
                                        )}
                                    </td>
                                )}
                                <td className="px-4 py-2 text-right">
                                    <MoneyFormat
                                        cents={
                                            o.direction === 'outflow'
                                                ? -o.amount_cents
                                                : o.amount_cents
                                        }
                                        showSign
                                        colorize
                                    />
                                </td>
                                <td className="px-4 py-2 text-right">
                                    {showSubscriptionExtras && o.cancel_url && (
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="ghost"
                                        >
                                            <a
                                                href={o.cancel_url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                            >
                                                Cancel ↗
                                            </a>
                                        </Button>
                                    )}
                                    <Button
                                        size="sm"
                                        variant="ghost"
                                        onClick={() => {
                                            if (
                                                confirm(`Delete "${o.name}"?`)
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
            </CardContent>
        </Card>
    );
}

function UpcomingList({ upcoming }: { upcoming: ObligationInstanceRow[] }) {
    return (
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
                            const sign = i.direction === 'outflow' ? -1 : 1;
                            return (
                                <li
                                    key={i.id}
                                    className="flex items-center justify-between gap-3 py-2 text-sm"
                                >
                                    <div className="min-w-0">
                                        <div className="truncate font-medium">
                                            {i.name}{' '}
                                            <span className="text-muted-foreground text-xs">
                                                · {KIND_LABEL[i.kind]}
                                            </span>
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
                                            cents={sign * i.amount_cents}
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
    );
}

ObligationsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Recurring', href: '/obligations' },
    ],
};
