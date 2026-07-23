import { Head, Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import CodeForgeLayout from '@/layouts/codeforge-layout';
import { ApiError, apiRequest } from '@/lib/codeforge-api';
import type { RepositoryIssue } from '@/types/codeforge';
import { RepositoryTabs } from '../shared';

type IssuesResponse = {
    issues: RepositoryIssue[];
};

type IssueResponse = {
    issue: RepositoryIssue;
};

type PageProps = {
    repositoryId: number;
};

export default function RepositoryIssuesPage() {
    const { repositoryId } = usePage<PageProps>().props;
    const [issues, setIssues] = useState<RepositoryIssue[]>([]);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let active = true;

        void apiRequest<IssuesResponse>(
            `/api/repositories/${repositoryId}/issues`,
        )
            .then((response) => {
                if (active) {
                    setIssues(response.issues);
                }
            })
            .catch(() => {
                if (active) {
                    setIssues([]);
                }
            });

        return () => {
            active = false;
        };
    }, [repositoryId]);

    async function toggleStatus(issue: RepositoryIssue) {
        setError(null);

        try {
            const response = await apiRequest<IssueResponse>(
                `/api/issues/${issue.id}`,
                {
                    method: 'PUT',
                    body: JSON.stringify({
                        status: issue.status === 'open' ? 'closed' : 'open',
                    }),
                },
            );

            setIssues((current) =>
                current.map((candidate) =>
                    candidate.id === issue.id ? response.issue : candidate,
                ),
            );
        } catch (caughtError) {
            setError(
                caughtError instanceof ApiError
                    ? caughtError.message
                    : 'Unable to update the issue.',
            );
        }
    }

    return (
        <CodeForgeLayout>
            <Head title="Repository Issues" />
            <div className="space-y-6">
                <RepositoryTabs repositoryId={repositoryId} current="issues" />
                <Card>
                    <CardHeader className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <CardTitle>Issues</CardTitle>
                            <CardDescription>
                                Track open work and close issues from the
                                repository view.
                            </CardDescription>
                        </div>
                        <Button asChild>
                            <Link
                                href={`/codeforge/repositories/${repositoryId}/issues/new`}
                            >
                                New issue
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {error && (
                            <p className="text-sm text-red-600">{error}</p>
                        )}
                        {issues.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No issues have been created yet.
                            </p>
                        ) : (
                            issues.map((issue) => (
                                <div
                                    key={issue.id}
                                    className="rounded-2xl border p-4"
                                >
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                        <div className="space-y-2">
                                            <div className="flex items-center gap-2">
                                                <p className="font-medium">
                                                    {issue.title}
                                                </p>
                                                <span className="text-muted-foreground rounded-full border px-2 py-0.5 text-xs tracking-wide uppercase">
                                                    {issue.status}
                                                </span>
                                            </div>
                                            <p className="text-muted-foreground text-sm">
                                                {issue.description ??
                                                    'No description provided.'}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                Opened by{' '}
                                                {issue.author?.name ??
                                                    'Unknown'}{' '}
                                                on {issue.created_at}
                                            </p>
                                        </div>
                                        <Button
                                            variant="outline"
                                            onClick={() =>
                                                void toggleStatus(issue)
                                            }
                                        >
                                            {issue.status === 'open'
                                                ? 'Close issue'
                                                : 'Reopen issue'}
                                        </Button>
                                    </div>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </CodeForgeLayout>
    );
}
