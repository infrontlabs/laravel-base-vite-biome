import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { AccountSelect } from '@/components/budget/account-select';
import { CategorySelect } from '@/components/budget/category-select';
import { MoneyInput } from '@/components/budget/money-input';
import { RecurrencePicker } from '@/components/budget/recurrence-picker';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    AccountOption,
    CategoryOption,
    ObligationDirection,
    ObligationKind,
    RecurrenceFrequency,
} from '@/types/budget';

const KINDS: { value: ObligationKind; label: string }[] = [
    { value: 'bill', label: 'Bill' },
    { value: 'subscription', label: 'Subscription' },
    { value: 'paycheck', label: 'Paycheck' },
    { value: 'savings_transfer', label: 'Savings transfer' },
    { value: 'debt_payment', label: 'Debt payment' },
    { value: 'other', label: 'Other' },
];

export default function CreateObligation({
    accounts,
    categories,
}: {
    accounts: AccountOption[];
    categories: CategoryOption[];
}) {
    const today = new Date().toISOString().slice(0, 10);

    const { data, setData, post, transform, processing, errors } = useForm<{
        name: string;
        kind: ObligationKind;
        direction: ObligationDirection;
        account_id: string;
        category_id: string;
        amount: string;
        frequency: RecurrenceFrequency;
        interval: number;
        anchor_date: string;
        day_of_month: number | null;
        secondary_day_of_month: number | null;
        day_of_week: number | null;
        autopay: boolean;
        is_active: boolean;
        notes: string;
    }>({
        name: '',
        kind: 'bill',
        direction: 'outflow',
        account_id: accounts[0] ? String(accounts[0].id) : '',
        category_id: 'none',
        amount: '',
        frequency: 'monthly',
        interval: 1,
        anchor_date: today,
        day_of_month: null,
        secondary_day_of_month: null,
        day_of_week: null,
        autopay: false,
        is_active: true,
        notes: '',
    });

    transform((d) => ({
        ...d,
        category_id: d.category_id === 'none' ? null : d.category_id,
    }));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/obligations');
    };

    if (accounts.length === 0) {
        return (
            <div className="mx-auto max-w-xl p-4">
                <p>
                    Add an account first before scheduling obligations.{' '}
                    <a className="underline" href="/accounts/create">
                        Add account
                    </a>
                </p>
            </div>
        );
    }

    return (
        <>
            <Head title="Add obligation" />
            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading
                    title="Add scheduled obligation"
                    description="Bills, subscriptions, paychecks, planned transfers."
                />

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="kind">Kind</Label>
                            <Select
                                value={data.kind}
                                onValueChange={(v) =>
                                    setData('kind', v as ObligationKind)
                                }
                            >
                                <SelectTrigger id="kind">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {KINDS.map((k) => (
                                        <SelectItem
                                            key={k.value}
                                            value={k.value}
                                        >
                                            {k.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.kind} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="direction">Direction</Label>
                            <Select
                                value={data.direction}
                                onValueChange={(v) =>
                                    setData(
                                        'direction',
                                        v as ObligationDirection,
                                    )
                                }
                            >
                                <SelectTrigger id="direction">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="outflow">
                                        Outflow (money out)
                                    </SelectItem>
                                    <SelectItem value="inflow">
                                        Inflow (money in)
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError message={errors.direction} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
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
                        <div className="grid gap-2">
                            <Label htmlFor="category_id">
                                Category (optional)
                            </Label>
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
                        <Label htmlFor="amount">Amount</Label>
                        <MoneyInput
                            name="amount"
                            value={data.amount}
                            onChange={(v) => setData('amount', v)}
                            required
                        />
                        <p className="text-muted-foreground text-xs">
                            Always positive. Direction above controls sign.
                        </p>
                        <InputError message={errors.amount} />
                    </div>

                    <RecurrencePicker
                        frequency={data.frequency}
                        interval={data.interval}
                        anchorDate={data.anchor_date}
                        dayOfMonth={data.day_of_month}
                        secondaryDayOfMonth={data.secondary_day_of_month}
                        dayOfWeek={data.day_of_week}
                        onChange={(patch) => {
                            if (patch.frequency !== undefined) {
                                setData('frequency', patch.frequency);
                            }
                            if (patch.interval !== undefined) {
                                setData('interval', patch.interval);
                            }
                            if (patch.anchor_date !== undefined) {
                                setData('anchor_date', patch.anchor_date);
                            }
                            if (patch.day_of_month !== undefined) {
                                setData('day_of_month', patch.day_of_month);
                            }
                            if (patch.secondary_day_of_month !== undefined) {
                                setData(
                                    'secondary_day_of_month',
                                    patch.secondary_day_of_month,
                                );
                            }
                            if (patch.day_of_week !== undefined) {
                                setData('day_of_week', patch.day_of_week);
                            }
                        }}
                    />

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="autopay"
                            checked={data.autopay}
                            onCheckedChange={(v) =>
                                setData('autopay', Boolean(v))
                            }
                        />
                        <Label htmlFor="autopay" className="cursor-pointer">
                            Autopay (we'll expect it to hit automatically)
                        </Label>
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
                            Create obligation
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

CreateObligation.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Obligations', href: '/obligations' },
        { title: 'Add', href: '/obligations/create' },
    ],
};
