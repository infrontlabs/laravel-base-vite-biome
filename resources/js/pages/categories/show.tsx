import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { MoneyFormat } from '@/components/budget/money-format';
import { MoneyInput } from '@/components/budget/money-input';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';

type Category = {
    id: number;
    name: string;
    slug: string;
    group: string;
    icon: string | null;
    color: string | null;
    monthly_target_cents: number | null;
};

type MonthlyRow = {
    month: string;
    amount_cents: number;
};

type Transaction = {
    id: number;
    amount_cents: number;
    description: string;
    date: string | null;
    account_name: string | null;
};

export default function CategoryShow({
    category,
    transactions,
    monthly,
}: {
    category: Category;
    transactions: Transaction[];
    monthly: MonthlyRow[];
}) {
    const maxMonthly =
        monthly.reduce((m, r) => Math.max(m, r.amount_cents), 0) || 1;

    const { data, setData, patch, processing } = useForm({
        monthly_target:
            category.monthly_target_cents !== null
                ? (category.monthly_target_cents / 100).toFixed(2)
                : '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        patch(`/categories/${category.id}`);
    };

    return (
        <>
            <Head title={category.name} />
            <div className="space-y-6 p-4">
                <Heading title={category.name} />

                <Card>
                    <CardHeader>
                        <CardTitle className="font-medium text-muted-foreground text-sm">
                            Monthly target
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form
                            onSubmit={submit}
                            className="flex items-end gap-3"
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="monthly_target">Target</Label>
                                <MoneyInput
                                    name="monthly_target"
                                    value={data.monthly_target}
                                    onChange={(v) =>
                                        setData('monthly_target', v)
                                    }
                                />
                            </div>
                            <Button type="submit" disabled={processing}>
                                Save
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="font-medium text-muted-foreground text-sm">
                            Last 12 months
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-end gap-2">
                            {monthly.map((m) => (
                                <div
                                    key={m.month}
                                    className="flex flex-1 flex-col items-center gap-1"
                                >
                                    <div
                                        className="w-full rounded-sm bg-primary/20"
                                        style={{
                                            height: `${Math.max(
                                                4,
                                                (m.amount_cents / maxMonthly) *
                                                    120,
                                            )}px`,
                                        }}
                                        title={`${m.month}: $${(m.amount_cents / 100).toFixed(2)}`}
                                    />
                                    <span className="text-[10px] text-muted-foreground">
                                        {m.month.slice(5)}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="font-medium text-muted-foreground text-sm">
                            Recent transactions
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {transactions.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No transactions in this category yet.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {transactions.map((t) => (
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
        </>
    );
}

CategoryShow.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Categories', href: '/categories' },
        { title: 'Detail', href: '#' },
    ],
};
