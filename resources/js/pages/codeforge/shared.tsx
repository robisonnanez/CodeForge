import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

export const codeforgeBreadcrumbs: BreadcrumbItem[] = [
    {
        title: 'CodeForge',
        href: '/codeforge',
    },
];

export function RepositoryTabs({
    repositoryId,
    current,
}: {
    repositoryId: number;
    current: 'overview' | 'files' | 'commits' | 'issues';
}) {
    const tabs = [
        {
            key: 'overview',
            label: 'Overview',
            href: `/codeforge/repositories/${repositoryId}`,
        },
        {
            key: 'files',
            label: 'Files',
            href: `/codeforge/repositories/${repositoryId}/files`,
        },
        {
            key: 'commits',
            label: 'Commits',
            href: `/codeforge/repositories/${repositoryId}/commits`,
        },
        {
            key: 'issues',
            label: 'Issues',
            href: `/codeforge/repositories/${repositoryId}/issues`,
        },
    ] as const;

    return (
        <div className="flex flex-wrap gap-2">
            {tabs.map((tab) => (
                <Button
                    key={tab.key}
                    asChild
                    variant={tab.key === current ? 'default' : 'outline'}
                >
                    <Link href={tab.href}>{tab.label}</Link>
                </Button>
            ))}
        </div>
    );
}
