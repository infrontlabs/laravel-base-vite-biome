import { formatCents, formatCentsSigned } from '@/lib/money';
import { cn } from '@/lib/utils';

type Props = {
    cents: number;
    currency?: string;
    showSign?: boolean;
    colorize?: boolean;
    className?: string;
};

export function MoneyFormat({
    cents,
    currency = 'USD',
    showSign = false,
    colorize = false,
    className,
}: Props) {
    const text = showSign
        ? formatCentsSigned(cents, currency)
        : formatCents(cents, currency);
    const color = colorize
        ? cents < 0
            ? 'text-red-600 dark:text-red-400'
            : cents > 0
              ? 'text-emerald-600 dark:text-emerald-400'
              : 'text-muted-foreground'
        : '';
    return <span className={cn('tabular-nums', color, className)}>{text}</span>;
}
