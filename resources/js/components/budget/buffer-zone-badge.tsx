import { Badge } from '@/components/ui/badge';
import type { BufferZone } from '@/types/budget';

const LABELS: Record<BufferZone, { text: string; className: string }> = {
    green: {
        text: 'Healthy',
        className:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    },
    amber: {
        text: 'Watch',
        className:
            'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    },
    red: {
        text: 'Buffer breach',
        className: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
    },
};

export function BufferZoneBadge({ zone }: { zone: BufferZone }) {
    const { text, className } = LABELS[zone];
    return (
        <Badge variant="outline" className={className}>
            {text}
        </Badge>
    );
}
