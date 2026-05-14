import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
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

const KINDS = [
    { value: 'checking', label: 'Checking' },
    { value: 'savings', label: 'Savings / HYSA' },
    { value: 'credit_card', label: 'Credit card' },
    { value: 'cash', label: 'Cash' },
    { value: 'mortgage', label: 'Mortgage' },
    { value: 'auto_loan', label: 'Auto loan' },
    { value: 'student_loan', label: 'Student loan' },
    { value: 'other_liability', label: 'Other liability' },
    { value: 'other_asset', label: 'Other asset' },
];

export default function CreateAccount() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        kind: 'checking',
        subkind: '',
        opening_balance: '0',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/accounts');
    };

    return (
        <>
            <Head title="Add account" />
            <div className="mx-auto max-w-xl space-y-6 p-4">
                <Heading
                    title="Add account"
                    description="Manual accounts you'll track and reconcile yourself."
                />

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder="e.g. Chase Checking"
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="kind">Type</Label>
                        <Select
                            value={data.kind}
                            onValueChange={(value) => setData('kind', value)}
                        >
                            <SelectTrigger id="kind">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {KINDS.map((k) => (
                                    <SelectItem key={k.value} value={k.value}>
                                        {k.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.kind} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="subkind">Subtype (optional)</Label>
                        <Input
                            id="subkind"
                            value={data.subkind}
                            onChange={(e) => setData('subkind', e.target.value)}
                            placeholder="e.g. HYSA, Sapphire Preferred"
                        />
                        <InputError message={errors.subkind} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="opening_balance">Opening balance</Label>
                        <MoneyInput
                            name="opening_balance"
                            value={data.opening_balance}
                            onChange={(v) => setData('opening_balance', v)}
                            allowNegative
                        />
                        <p className="text-muted-foreground text-xs">
                            For liabilities, enter the balance owed as a
                            positive number.
                        </p>
                        <InputError message={errors.opening_balance} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Button type="submit" disabled={processing}>
                            Create account
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

CreateAccount.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Accounts', href: '/accounts' },
        { title: 'Add', href: '/accounts/create' },
    ],
};
