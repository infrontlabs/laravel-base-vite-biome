import { Head, useForm } from '@inertiajs/react';
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

export default function CreateTransaction({
    accounts,
    categories,
}: {
    accounts: AccountOption[];
    categories: CategoryOption[];
}) {
    const today = new Date().toISOString().slice(0, 10);

    const { data, setData, post, transform, processing, errors } = useForm({
        account_id: accounts[0] ? String(accounts[0].id) : '',
        category_id: 'none',
        amount: '',
        date: today,
        status: 'pending' as 'pending' | 'posted',
        description: '',
        notes: '',
    });

    transform((d) => ({
        ...d,
        category_id: d.category_id === 'none' ? null : d.category_id,
    }));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/transactions');
    };

    if (accounts.length === 0) {
        return (
            <div className="mx-auto max-w-xl p-4">
                <p>
                    You need to{' '}
                    <a className="underline" href="/accounts/create">
                        add an account
                    </a>{' '}
                    before logging transactions.
                </p>
            </div>
        );
    }

    return (
        <>
            <Head title="Add transaction" />
            <div className="mx-auto max-w-xl space-y-6 p-4">
                <Heading
                    title="Add transaction"
                    description="Use a negative amount for outflows (expenses)."
                />

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
                                    setData('status', v as 'pending' | 'posted')
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

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            Save
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

CreateTransaction.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Transactions', href: '/transactions' },
        { title: 'Add', href: '/transactions/create' },
    ],
};
