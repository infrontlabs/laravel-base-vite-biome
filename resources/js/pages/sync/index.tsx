import { Head, router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { MoneyFormat } from '@/components/budget/money-format';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Suggestion = {
    id: number;
    description: string;
    amount_cents: number;
    date: string | null;
    status: 'pending' | 'posted' | 'void';
    account_name: string | null;
    category_name: string | null;
    score: number;
};

type Candidate = {
    id: number;
    description: string;
    merchant_name: string | null;
    amount_cents: number;
    date: string | null;
    account_name: string | null;
    plaid_transaction_id: string | null;
    suggestions: Suggestion[];
};

type Props = {
    candidates: Candidate[];
    lastSyncedAt: string | null;
    mergedCount: number;
};

function scoreBadge(score: number): { label: string; tone: string } {
    if (score >= 90) {
        return {
            label: 'Strong match',
            tone: 'bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-200',
        };
    }
    if (score >= 70) {
        return {
            label: 'Likely match',
            tone: 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
        };
    }
    return {
        label: 'Possible',
        tone: 'bg-muted text-muted-foreground',
    };
}

function formatSyncedAt(value: string | null): string {
    if (!value) {
        return 'Never';
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return value;
    }
    return date.toLocaleString();
}

export default function SyncIndex({
    candidates,
    lastSyncedAt,
    mergedCount,
}: Props) {
    const runSync = () => {
        router.post(
            '/sync/simulate',
            {},
            { preserveScroll: true, preserveState: false },
        );
    };

    const matchTo = (candidateId: number, manualId: number) => {
        router.post(
            `/sync/${candidateId}/match`,
            { manual_transaction_id: manualId },
            { preserveScroll: true },
        );
    };

    const acceptAsNew = (candidateId: number) => {
        router.post(
            `/sync/${candidateId}/accept`,
            {},
            { preserveScroll: true },
        );
    };

    const dismiss = (candidateId: number) => {
        router.delete(`/sync/${candidateId}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Sync" />
            <div className="space-y-6 p-4">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Sync"
                        description={`Last synced: ${formatSyncedAt(lastSyncedAt)} · ${mergedCount} merged to date`}
                    />
                    <Button onClick={runSync}>
                        <RefreshCw className="mr-2 h-4 w-4" />
                        Run sync
                    </Button>
                </div>

                {candidates.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center gap-2 py-12 text-center">
                            <p className="font-medium">
                                No pending Plaid transactions.
                            </p>
                            <p className="text-muted-foreground text-sm">
                                Click{' '}
                                <span className="font-medium">Run sync</span> to
                                pull a fresh batch of simulated Plaid
                                transactions and reconcile them against your
                                manual entries.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="space-y-4">
                        {candidates.map((candidate) => (
                            <CandidateCard
                                key={candidate.id}
                                candidate={candidate}
                                onMatch={(manualId) =>
                                    matchTo(candidate.id, manualId)
                                }
                                onAccept={() => acceptAsNew(candidate.id)}
                                onDismiss={() => dismiss(candidate.id)}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

function CandidateCard({
    candidate,
    onMatch,
    onAccept,
    onDismiss,
}: {
    candidate: Candidate;
    onMatch: (manualId: number) => void;
    onAccept: () => void;
    onDismiss: () => void;
}) {
    return (
        <Card>
            <CardHeader className="flex flex-row items-start justify-between gap-4 space-y-0">
                <div className="min-w-0 space-y-1">
                    <CardTitle className="text-base">
                        {candidate.description}
                    </CardTitle>
                    <div className="text-muted-foreground text-xs">
                        {candidate.date} ·{' '}
                        {candidate.account_name ?? 'Unknown account'}
                        {candidate.merchant_name
                            ? ` · ${candidate.merchant_name}`
                            : ''}
                    </div>
                </div>
                <div className="flex items-center gap-2">
                    <Badge variant="outline" className="text-[10px]">
                        plaid
                    </Badge>
                    <MoneyFormat
                        cents={candidate.amount_cents}
                        colorize
                        className="font-semibold"
                    />
                </div>
            </CardHeader>
            <CardContent className="space-y-3">
                {candidate.suggestions.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No likely manual match found.
                    </p>
                ) : (
                    <div className="space-y-2">
                        <p className="font-medium text-muted-foreground text-xs uppercase tracking-wide">
                            Suggested matches
                        </p>
                        <ul className="divide-y rounded border">
                            {candidate.suggestions.map((s) => {
                                const badge = scoreBadge(s.score);
                                return (
                                    <li
                                        key={s.id}
                                        className="flex items-center justify-between gap-4 p-3 text-sm"
                                    >
                                        <div className="min-w-0 space-y-1">
                                            <div className="flex items-center gap-2">
                                                <span
                                                    className={`rounded px-1.5 py-0.5 text-[10px] font-medium ${badge.tone}`}
                                                >
                                                    {badge.label}
                                                </span>
                                                <span className="truncate font-medium">
                                                    {s.description}
                                                </span>
                                            </div>
                                            <div className="text-muted-foreground text-xs">
                                                {s.date} ·{' '}
                                                {s.account_name ?? 'Unknown'}
                                                {s.category_name
                                                    ? ` · ${s.category_name}`
                                                    : ''}{' '}
                                                · {s.status}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <MoneyFormat
                                                cents={s.amount_cents}
                                                colorize
                                            />
                                            <Button
                                                size="sm"
                                                variant="secondary"
                                                onClick={() => onMatch(s.id)}
                                            >
                                                Match
                                            </Button>
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                )}

                <div className="flex justify-end gap-2 border-t pt-3">
                    <Button size="sm" variant="ghost" onClick={onDismiss}>
                        Dismiss
                    </Button>
                    <Button size="sm" variant="outline" onClick={onAccept}>
                        Accept as new
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

SyncIndex.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Sync', href: '/sync' },
    ],
};
