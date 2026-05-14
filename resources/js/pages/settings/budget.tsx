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

type Props = {
    preferences: {
        buffer_threshold_cents: number;
        default_currency: string;
        default_account_id: number | null;
    };
    accounts: { id: number; name: string }[];
};

export default function BudgetSettings({ preferences, accounts }: Props) {
    const { data, setData, patch, transform, processing, errors } = useForm({
        buffer_threshold: (preferences.buffer_threshold_cents / 100).toFixed(2),
        default_currency: preferences.default_currency,
        default_account_id:
            preferences.default_account_id !== null
                ? String(preferences.default_account_id)
                : 'none',
    });

    transform((d) => ({
        ...d,
        default_account_id:
            d.default_account_id === 'none' ? null : d.default_account_id,
    }));

    const submit = (e: FormEvent) => {
        e.preventDefault();
        patch('/settings/budget');
    };

    return (
        <>
            <Head title="Budget settings" />
            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Budget settings"
                    description="Tweak the household-wide defaults."
                />

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="buffer_threshold">
                            Buffer threshold
                        </Label>
                        <MoneyInput
                            name="buffer_threshold"
                            value={data.buffer_threshold}
                            onChange={(v) => setData('buffer_threshold', v)}
                        />
                        <p className="text-muted-foreground text-xs">
                            Safe-to-spend subtracts this. The dashboard turns
                            red when spending breaches this amount.
                        </p>
                        <InputError message={errors.buffer_threshold} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="default_currency">
                            Default currency
                        </Label>
                        <Input
                            id="default_currency"
                            value={data.default_currency}
                            onChange={(e) =>
                                setData(
                                    'default_currency',
                                    e.target.value.toUpperCase(),
                                )
                            }
                            maxLength={3}
                            required
                        />
                        <InputError message={errors.default_currency} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="default_account_id">
                            Default account for new transactions
                        </Label>
                        <Select
                            value={data.default_account_id}
                            onValueChange={(v) =>
                                setData('default_account_id', v)
                            }
                        >
                            <SelectTrigger id="default_account_id">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                {accounts.map((a) => (
                                    <SelectItem key={a.id} value={String(a.id)}>
                                        {a.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
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

BudgetSettings.layout = {
    breadcrumbs: [
        { title: 'Settings', href: '/settings/profile' },
        { title: 'Budget', href: '/settings/budget' },
    ],
};
