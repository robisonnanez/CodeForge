import { Head, Link } from '@inertiajs/react';
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
import { apiRequest } from '@/lib/codeforge-api';
import type { Repository } from '@/types/codeforge';

type RepositoryIndexResponse = {
    repositories: Repository[];
};

export default function RepositoriesIndexPage() {
    const [repositories, setRepositories] = useState<Repository[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        void apiRequest<RepositoryIndexResponse>('/api/repositories')
            .then((response) => {
                setRepositories(response.repositories);
            })
            .finally(() => {
                setLoading(false);
            });
    }, []);

    return (
        <CodeForgeLayout>
            <Head title="Repositories" />
            <Card>
                <CardHeader className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <CardTitle>Repositories</CardTitle>
                        <CardDescription>
                            Manage your Git remotes and visibility from
                            CodeForge.
                        </CardDescription>
                    </div>
                    <Button asChild>
                        <Link href="/codeforge/repositories/new">
                            Create repository
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent className="space-y-4">
                    {loading && (
                        <p className="text-muted-foreground text-sm">
                            Loading repositories...
                        </p>
                    )}
                    {!loading && repositories.length === 0 && (
                        <p className="text-muted-foreground text-sm">
                            No repositories found. Start by creating one.
                        </p>
                    )}
                    {repositories.map((repository) => (
                        <div
                            key={repository.id}
                            className="rounded-2xl border p-5"
                        >
                            <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <p className="text-lg font-semibold">
                                            {repository.name}
                                        </p>
                                        <span className="text-muted-foreground rounded-full border px-2 py-0.5 text-xs tracking-wide uppercase">
                                            {repository.visibility}
                                        </span>
                                    </div>
                                    <p className="text-muted-foreground text-sm">
                                        {repository.description ??
                                            'No description provided.'}
                                    </p>
                                    <p className="text-muted-foreground font-mono text-xs">
                                        {repository.ssh_url}
                                    </p>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button asChild variant="outline">
                                        <Link
                                            href={`/codeforge/repositories/${repository.id}`}
                                        >
                                            Overview
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline">
                                        <Link
                                            href={`/codeforge/repositories/${repository.id}/files`}
                                        >
                                            Files
                                        </Link>
                                    </Button>
                                    <Button asChild variant="outline">
                                        <Link
                                            href={`/codeforge/repositories/${repository.id}/issues`}
                                        >
                                            Issues
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
        </CodeForgeLayout>
    );
}
