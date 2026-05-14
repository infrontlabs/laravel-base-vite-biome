import { Head, Link, router, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AccountSelect } from '@/components/budget/account-select';
import { CategorySelect } from '@/components/budget/category-select';
import { MoneyInput } from '@/components/budget/money-input';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { AccountOption, CategoryOption } from '@/types/budget';

type Transaction = {
    id: number;
    account_id: number;
    category_id: number | null;
    amount_cents: number;
    date: string | null;
    status: 'pending' | 'posted' | 'void';
    source: string;
    description: string;
    notes: string | null;
    excluded_from_budget: boolean;
};

export default function EditTransaction({
    transaction,
    accounts,
    categories,
}: {
    transaction: Transaction;
    accounts: AccountOption[];
    categories: CategoryOption[];
}) {
    const { data, setData, patch, transform, processing, errors } = useForm({
        account_id: String(transaction.account_id),
        category_id:
            transaction.category_id !== null
                ? String(transaction.category_id)
                : 'none',
        amount: (transaction.amount_cents / 100).toFixed(2),
        date: transaction.date ?? '',
        status: transaction.status,
        description: transaction.description,
        notes: transaction.notes ?? '',
        excluded_from_budget: transaction.excluded_from_budget,
    });

    transform((d) => ({
        ...d,
        category_id: d.category_id === 'none' ? null : d.category_id,
    }));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        patch(`/transactions/${transaction.id}`);
    };

    const destroy = () => {
        if (confirm('Delete this transaction?')) {
            router.delete(`/transactions/${transaction.id}`);
        }
    };

    return (
        <>
            <Head title="Edit transaction" />
            <div className="mx-auto max-w-xl space-y-6 p-4">
                <Heading title="Edit transaction" />

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="account_id">Account</Label>
                        <AccountSelect
                            name="account_id"
                            accounts={accounts}
                            value={data.account_id}
                            onChange={(v) => setData('account_id', v)}
                        />
                        <InputError message={errors.account_id} />
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="amount">Amount</Label>
                            <MoneyInput
                                name="amount"
                                value={data.amount}
                                onChange={(v) => setData('amount', v)}
                                allowNegative
                                required
                            />
                            <InputError message={errors.amount} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="date">Date</Label>
                            <Input
                                id="date"
                                type="date"
                                value={data.date}
                                onChange={(e) =>
                                    setData('date', e.target.value)
                                }
                                required
                            />
                            <InputError message={errors.date} />
                        </div>
                    </div>

                    <div className="grid gap-2 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <Select
                                value={data.status}
                                onValueChange={(v) =>
                                    setData(
                                        'status',
                                        v as 'pending' | 'posted' | 'void',
                                    )
                                }
                            >
                                <SelectTrigger id="status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">
                                        Pending
                                    </SelectItem>
                                    <SelectItem value="posted">
                                        Posted
                                    </SelectItem>
                                    <SelectItem value="void">Void</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.status} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="category_id">Category</Label>
                            <CategorySelect
                                name="category_id"
                                categories={categories}
                                value={data.category_id}
                                onChange={(v) => setData('category_id', v)}
                            />
                            <InputError message={errors.category_id} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <Input
                            id="description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            required
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Notes</Label>
                        <Input
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                        <InputError message={errors.notes} />
                    </div>

                    <div className="flex items-center justify-between gap-3">
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save
                            </Button>
                            <Button asChild variant="outline">
                                <Link href="/transactions">Cancel</Link>
                            </Button>
                        </div>
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={destroy}
                        >
                            Delete
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

EditTransaction.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Transactions', href: '/transactions' },
        { title: 'Edit', href: '#' },
    ],
};
