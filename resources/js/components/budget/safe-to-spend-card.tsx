import { useState } from 'react';
import { BufferZoneBadge } from '@/components/budget/buffer-zone-badge';
import { MoneyFormat } from '@/components/budget/money-format';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import type { BufferZone, SafeToSpend } from '@/types/budget';

type Props = {
    safeToSpend: SafeToSpend;
    zone: BufferZone;
};

export function SafeToSpendCard({ safeToSpend, zone }: Props) {
    const [open, setOpen] = useState(false);
    const isLow = safeToSpend.safe_to_spend_cents < 0;

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                <CardTitle className="font-medium text-muted-foreground text-sm">
                    Safe to spend until{' '}
                    {new Date(safeToSpend.horizon_end).toLocaleDateString(
                        undefined,
                        { month: 'short', day: 'numeric' },
                    )}
                </CardTitle>
                <BufferZoneBadge zone={zone} />
            </CardHeader>
            <CardContent>
                <div
                    className={cn(
                        'font-semibold text-4xl tabular-nums',
                        isLow && 'text-red-600 dark:text-red-400',
                    )}
                >
                    <MoneyFormat cents={safeToSpend.safe_to_spend_cents} />
                </div>
                <Button
                    type="button"
                    variant="link"
                    className="mt-2 h-auto p-0 text-muted-foreground text-xs"
                    onClick={() => setOpen((o) => !o)}
                >
                    {open ? 'Hide breakdown' : 'Why this number?'}
                </Button>
                {open && (
                    <dl className="mt-3 grid gap-1 border-t pt-3 text-sm">
                        {safeToSpend.breakdown.map((item) => (
                            <div
                                key={item.label}
                                className="flex justify-between"
                            >
                                <dt className="text-muted-foreground">
                                    {item.label}
                                </dt>
                                <dd>
                                    <MoneyFormat
                                        cents={item.amount_cents}
                                        showSign
                                        colorize
                                    />
                                </dd>
                            </div>
                        ))}
                    </dl>
                )}
            </CardContent>
        </Card>
    );
}
