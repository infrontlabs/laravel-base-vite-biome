import { Head, Link } from '@inertiajs/react';
import { MoneyFormat } from '@/components/budget/money-format';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { CategoryRow } from '@/types/budget';

const GROUP_LABELS: Record<string, { label: string; className: string }> = {
    fixed: {
        label: 'Fixed',
        className:
            'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300',
    },
    flexible: {
        label: 'Flexible',
        className:
            'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
    },
    income: {
        label: 'Income',
        className:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    },
    transfer: {
        label: 'Transfer',
        className:
            'bg-zinc-100 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300',
    },
    savings: {
        label: 'Savings',
        className: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
    },
    debt_payment: {
        label: 'Debt',
        className:
            'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    },
};

export default function CategoriesIndex({
    categories,
}: {
    categories: CategoryRow[];
}) {
    return (
        <>
            <Head title="Categories" />
            <div className="space-y-6 p-4">
                <Heading
                    title="Categories"
                    description="Target vs. actual for the current month. Click any row to drill into the history."
                />

                <Card>
                    <CardContent className="p-0">
                        <table className="w-full text-sm">
                            <thead className="border-b">
                                <tr className="text-left text-muted-foreground text-xs">
                                    <th className="px-4 py-2 font-medium">
                                        Name
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Group
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Target
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        MTD actual
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Trailing 3mo avg
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {categories.map((c) => {
                                    const over =
                                        c.monthly_target_cents !== null &&
                                        c.mtd_actual_cents >
                                            c.monthly_target_cents;
                                    const labels =
                                        GROUP_LABELS[c.group] ??
                                        GROUP_LABELS.flexible;
                                    return (
                                        <tr key={c.id} className="border-b">
                                            <td className="px-4 py-2">
                                                <Link
                                                    href={`/categories/${c.id}`}
                                                    className="hover:underline"
                                                >
                                                    {c.name}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-2">
                                                <Badge
                                                    variant="outline"
                                                    className={labels.className}
                                                >
                                                    {labels.label}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-2 text-right text-muted-foreground">
                                                {c.monthly_target_cents !==
                                                null ? (
                                                    <MoneyFormat
                                                        cents={
                                                            c.monthly_target_cents
                                                        }
                                                    />
                                                ) : (
                                                    '—'
                                                )}
                                            </td>
                                            <td
                                                className={`px-4 py-2 text-right tabular-nums ${over ? 'text-red-600 dark:text-red-400' : ''}`}
                                            >
                                                <MoneyFormat
                                                    cents={c.mtd_actual_cents}
                                                />
                                            </td>
                                            <td className="px-4 py-2 text-right text-muted-foreground">
                                                <MoneyFormat
                                                    cents={
                                                        c.trailing_3mo_avg_cents
                                                    }
                                                />
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CategoriesIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Categories', href: '/categories' },
    ],
};
