import type { Auth } from '@/types/auth';

export type BudgetSharedProps = {
    buffer_threshold_cents: number;
    default_currency: string;
};

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            budget: BudgetSharedProps | null;
            [key: string]: unknown;
        };
    }
}
