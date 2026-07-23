import { Head, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import CodeForgeLayout from '@/layouts/codeforge-layout';
import { apiRequest } from '@/lib/codeforge-api';
import type { RepositoryCommit } from '@/types/codeforge';
import { RepositoryTabs } from '../shared';

type CommitsResponse = {
    commits: RepositoryCommit[];
};

type PageProps = {
    repositoryId: number;
};

export default function RepositoryCommitsPage() {
    const { repositoryId } = usePage<PageProps>().props;
    const [commits, setCommits] = useState<RepositoryCommit[]>([]);

    useEffect(() => {
        void apiRequest<CommitsResponse>(
            `/api/repositories/${repositoryId}/commits`,
        ).then((response) => {
            setCommits(response.commits);
        });
    }, [repositoryId]);

    return (
        <CodeForgeLayout>
            <Head title="Repository Commits" />
            <div className="space-y-6">
                <RepositoryTabs repositoryId={repositoryId} current="commits" />
                <Card>
                    <CardHeader>
                        <CardTitle>Commits</CardTitle>
                        <CardDescription>
                            Recent activity for this repository.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {commits.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No commits available yet.
                            </p>
                        ) : (
                            commits.map((commit) => (
                                <div
                                    key={commit.hash}
                                    className="rounded-2xl border p-4"
                                >
                                    <div className="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <p className="font-medium">
                                                {commit.message}
                                            </p>
                                            <p className="text-muted-foreground text-sm">
                                                {commit.author_name} •{' '}
                                                {commit.author_email}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-mono text-sm">
                                                {commit.short_hash}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                {commit.committed_at}
                                            </p>
                                        </div>
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
