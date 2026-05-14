import { Head, Link, router, useForm } from '@inertiajs/react';
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

type ObligationData = {
    id: number;
    name: string;
    kind: ObligationKind;
    direction: ObligationDirection;
    account_id: number;
    category_id: number | null;
    amount_cents: number;
    frequency: RecurrenceFrequency;
    interval: number;
    anchor_date: string;
    day_of_month: number | null;
    secondary_day_of_month: number | null;
    day_of_week: number | null;
    end_date: string | null;
    autopay: boolean;
    is_active: boolean;
    notes: string | null;
};

const KINDS: { value: ObligationKind; label: string }[] = [
    { value: 'bill', label: 'Bill' },
    { value: 'subscription', label: 'Subscription' },
    { value: 'paycheck', label: 'Paycheck' },
    { value: 'savings_transfer', label: 'Savings transfer' },
    { value: 'debt_payment', label: 'Debt payment' },
    { value: 'other', label: 'Other' },
];

export default function EditObligation({
    obligation,
    accounts,
    categories,
}: {
    obligation: ObligationData;
    accounts: AccountOption[];
    categories: CategoryOption[];
}) {
    const { data, setData, patch, transform, processing, errors } = useForm({
        name: obligation.name,
        kind: obligation.kind,
        direction: obligation.direction,
        account_id: String(obligation.account_id),
        category_id:
            obligation.category_id !== null
                ? String(obligation.category_id)
                : 'none',
        amount: (obligation.amount_cents / 100).toFixed(2),
        frequency: obligation.frequency,
        interval: obligation.interval,
        anchor_date: obligation.anchor_date,
        day_of_month: obligation.day_of_month,
        secondary_day_of_month: obligation.secondary_day_of_month,
        day_of_week: obligation.day_of_week,
        autopay: obligation.autopay,
        is_active: obligation.is_active,
        notes: obligation.notes ?? '',
    });

    transform((d) => ({
        ...d,
        category_id: d.category_id === 'none' ? null : d.category_id,
    }));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        patch(`/obligations/${obligation.id}`);
    };

    const destroy = () => {
        if (confirm(`Delete "${obligation.name}"?`)) {
            router.delete(`/obligations/${obligation.id}`);
        }
    };

    return (
        <>
            <Head title={`Edit ${obligation.name}`} />
            <div className="mx-auto max-w-2xl space-y-6 p-4">
                <Heading title={`Edit ${obligation.name}`} />

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
                                        Outflow
                                    </SelectItem>
                                    <SelectItem value="inflow">
                                        Inflow
                                    </SelectItem>
                                </SelectContent>
                            </Select>
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
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="category_id">Category</Label>
                            <CategorySelect
                                name="category_id"
                                categories={categories}
                                value={data.category_id}
                                onChange={(v) => setData('category_id', v)}
                            />
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

                    <div className="flex flex-wrap items-center gap-6">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="autopay"
                                checked={data.autopay}
                                onCheckedChange={(v) =>
                                    setData('autopay', Boolean(v))
                                }
                            />
                            <Label htmlFor="autopay" className="cursor-pointer">
                                Autopay
                            </Label>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="is_active"
                                checked={data.is_active}
                                onCheckedChange={(v) =>
                                    setData('is_active', Boolean(v))
                                }
                            />
                            <Label
                                htmlFor="is_active"
                                className="cursor-pointer"
                            >
                                Active
                            </Label>
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="notes">Notes</Label>
                        <Input
                            id="notes"
                            value={data.notes}
                            onChange={(e) => setData('notes', e.target.value)}
                        />
                    </div>

                    <div className="flex items-center justify-between gap-3">
                        <div className="flex gap-2">
                            <Button type="submit" disabled={processing}>
                                Save
                            </Button>
                            <Button asChild variant="outline">
                                <Link href="/obligations">Cancel</Link>
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

EditObligation.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Obligations', href: '/obligations' },
        { title: 'Edit', href: '#' },
    ],
};
