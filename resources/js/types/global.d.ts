import type { Auth } from '@/types/auth';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            navigation?: Array<{
                id: number;
                label: string;
                href: string;
                icon?: string | null;
                children?: Array<{
                    id: number;
                    label: string;
                    href: string;
                    icon?: string | null;
                }>;
            }>;
            [key: string]: unknown;
        };
    }
}
