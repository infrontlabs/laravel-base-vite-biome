export function formatCents(cents: number, currency = 'USD'): string {
    const negative = cents < 0;
    const abs = Math.abs(cents) / 100;
    const symbol = currency === 'USD' ? '$' : `${currency} `;
    const body = abs.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
    return `${negative ? '-' : ''}${symbol}${body}`;
}

export function formatCentsSigned(cents: number, currency = 'USD'): string {
    if (cents > 0) {
        return `+${formatCents(cents, currency)}`;
    }
    return formatCents(cents, currency);
}

export function parseDollarsToCents(input: string | number): number {
    const value = typeof input === 'number' ? input : Number(input);
    if (Number.isNaN(value)) {
        return 0;
    }
    return Math.round(value * 100);
}
