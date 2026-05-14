import { MoneyFormat } from '@/components/budget/money-format';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ObligationInstanceRow } from '@/types/budget';

type Props = {
    obligations: ObligationInstanceRow[];
};

export function NextObligationsStrip({ obligations }: Props) {
    if (obligations.length === 0) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle className="font-medium text-muted-foreground text-sm">
                        Upcoming obligations
                    </CardTitle>
                </CardHeader>
                <CardContent className="text-muted-foreground text-sm">
                    Nothing scheduled in the next 30 days.
                </CardContent>
            </Card>
        );
    }

    return (
        <Card>
            <CardHeader>
                <CardTitle className="font-medium text-muted-foreground text-sm">
                    Next obligations
                </CardTitle>
            </CardHeader>
            <CardContent>
                <ul className="divide-y">
                    {obligations.map((o) => {
                        const sign = o.direction === 'outflow' ? -1 : 1;
                        return (
                            <li
                                key={o.id}
                                className="flex items-center justify-between py-2 text-sm"
                            >
                                <div className="min-w-0">
                                    <div className="truncate font-medium">
                                        {o.name}
                                    </div>
                                    <div className="text-muted-foreground text-xs">
                                        {new Date(
                                            o.due_date,
                                        ).toLocaleDateString(undefined, {
                                            month: 'short',
                                            day: 'numeric',
                                        })}
                                        {o.account_name
                                            ? ` · ${o.account_name}`
                                            : ''}
                                    </div>
                                </div>
                                <MoneyFormat
                                    cents={sign * o.amount_cents}
                                    showSign
                                    colorize
                                />
                            </li>
                        );
                    })}
                </ul>
            </CardContent>
        </Card>
    );
}
