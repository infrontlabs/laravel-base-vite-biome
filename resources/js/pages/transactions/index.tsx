import { Head, Link, router } from '@inertiajs/react';
import { MoneyFormat } from '@/components/budget/money-format';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AccountOption, CategoryOption } from '@/types/budget';

function decodePagerLabel(label: string): string {
    return label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»');
}

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number | null;
    to: number | null;
    total: number;
};

type TxRow = {
    id: number;
    account_id: number;
    category_id: number | null;
    amount_cents: number;
    description: string;
    posted_date: string | null;
    pending_date: string | null;
    status: string;
    source: string;
    account: { id: number; name: string } | null;
    category: { id: number; name: string } | null;
};

type Props = {
    transactions: Paginated<TxRow>;
    accounts: AccountOption[];
    categories: CategoryOption[];
    filters: {
        account_id?: string;
        category_id?: string;
        status?: string;
        search?: string;
    };
};

export default function TransactionsIndex({
    transactions,
    accounts,
    categories,
    filters,
}: Props) {
    const updateFilter = (key: string, value: string) => {
        router.get(
            '/transactions',
            { ...filters, [key]: value || undefined },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Transactions" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Transactions"
                        description={`${transactions.total} total · showing ${transactions.from ?? 0}–${transactions.to ?? 0}`}
                    />
                    <Button asChild>
                        <Link href="/transactions/create">Add transaction</Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="font-medium text-muted-foreground text-sm">
                            Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-3 sm:grid-cols-4">
                            <Input
                                placeholder="Search description"
                                defaultValue={filters.search ?? ''}
                                onChange={(e) =>
                                    updateFilter('search', e.target.value)
                                }
                            />
                            <Select
                                value={filters.account_id ?? 'all'}
                                onValueChange={(v) =>
                                    updateFilter(
                                        'account_id',
                                        v === 'all' ? '' : v,
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Account" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All accounts
                                    </SelectItem>
                                    {accounts.map((a) => (
                                        <SelectItem
                                            key={a.id}
                                            value={String(a.id)}
                                        >
                                            {a.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.category_id ?? 'all'}
                                onValueChange={(v) =>
                                    updateFilter(
                                        'category_id',
                                        v === 'all' ? '' : v,
                                    )
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Category" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        All categories
                                    </SelectItem>
                                    {categories.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={String(c.id)}
                                        >
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Select
                                value={filters.status ?? 'all'}
                                onValueChange={(v) =>
                                    updateFilter('status', v === 'all' ? '' : v)
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All</SelectItem>
                                    <SelectItem value="pending">
                                        Pending
                                    </SelectItem>
                                    <SelectItem value="posted">
                                        Posted
                                    </SelectItem>
                                    <SelectItem value="void">Void</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-0">
                        <table className="w-full text-sm">
                            <thead className="border-b">
                                <tr className="text-left text-muted-foreground text-xs">
                                    <th className="px-4 py-2 font-medium">
                                        Date
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Description
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Account
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Category
                                    </th>
                                    <th className="px-4 py-2 font-medium">
                                        Status
                                    </th>
                                    <th className="px-4 py-2 text-right font-medium">
                                        Amount
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {transactions.data.length === 0 ? (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-4 py-8 text-center text-muted-foreground"
                                        >
                                            No transactions yet.
                                        </td>
                                    </tr>
                                ) : (
                                    transactions.data.map((t) => (
                                        <tr key={t.id} className="border-b">
                                            <td className="whitespace-nowrap px-4 py-2">
                                                {t.posted_date ??
                                                    t.pending_date}
                                            </td>
                                            <td className="px-4 py-2">
                                                <Link
                                                    href={`/transactions/${t.id}/edit`}
                                                    className="underline-offset-2 hover:underline"
                                                >
                                                    {t.description}
                                                </Link>
                                            </td>
                                            <td className="px-4 py-2 text-muted-foreground">
                                                {t.account?.name}
                                            </td>
                                            <td className="px-4 py-2 text-muted-foreground">
                                                {t.category?.name ?? '—'}
                                            </td>
                                            <td className="px-4 py-2">
                                                <Badge
                                                    variant="outline"
                                                    className="text-[10px]"
                                                >
                                                    {t.status}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-2 text-right">
                                                <MoneyFormat
                                                    cents={t.amount_cents}
                                                    colorize
                                                />
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                {transactions.links.length > 3 && (
                    <div className="flex flex-wrap gap-1">
                        {transactions.links.map((link) => (
                            <Link
                                key={link.label}
                                href={link.url ?? '#'}
                                className={`rounded px-2 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'}`}
                                preserveScroll
                                preserveState
                                only={[
                                    'transactions',
                                    'accounts',
                                    'categories',
                                    'filters',
                                ]}
                            >
                                {decodePagerLabel(link.label)}
                            </Link>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

TransactionsIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Transactions', href: '/transactions' },
    ],
};
