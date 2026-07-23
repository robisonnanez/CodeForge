import { Head, Link } from '@inertiajs/react';
import { FolderGit2, GitCommitHorizontal, GitBranch, Bug } from 'lucide-react';
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

export default function CodeForgeDashboard() {
    const [repositories, setRepositories] = useState<Repository[]>([]);

    useEffect(() => {
        void apiRequest<RepositoryIndexResponse>('/api/repositories')
            .then((response) => {
                setRepositories(response.repositories);
            })
            .catch(() => {
                setRepositories([]);
            });
    }, []);

    const stats = [
        { label: 'Repositories', value: repositories.length, icon: FolderGit2 },
        {
            label: 'Public',
            value: repositories.filter(
                (repository) => repository.visibility === 'public',
            ).length,
            icon: GitBranch,
        },
        {
            label: 'Private',
            value: repositories.filter(
                (repository) => repository.visibility === 'private',
            ).length,
            icon: GitCommitHorizontal,
        },
        { label: 'Bare remotes', value: repositories.length, icon: Bug },
    ];

    return (
        <CodeForgeLayout>
            <Head title="CodeForge" />
            <div className="space-y-6">
                <section className="rounded-3xl border bg-gradient-to-r from-slate-950 via-slate-900 to-orange-900 px-6 py-8 text-white shadow-sm">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div className="max-w-2xl space-y-2">
                            <p className="text-sm tracking-[0.3em] text-orange-200 uppercase">
                                CodeForge
                            </p>
                            <h1 className="text-3xl font-semibold">
                                Mini Git hosting for your team
                            </h1>
                            <p className="text-sm text-slate-200">
                                Create repositories, publish an SSH URL, inspect
                                commits and track simple issues from one place.
                            </p>
                        </div>
                        <div className="flex gap-3">
                            <Button asChild variant="secondary">
                                <Link href="/codeforge/repositories/new">
                                    New repository
                                </Link>
                            </Button>
                            <Button
                                asChild
                                variant="outline"
                                className="border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white"
                            >
                                <Link href="/codeforge/repositories">
                                    Browse repositories
                                </Link>
                            </Button>
                        </div>
                    </div>
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {stats.map((stat) => (
                        <Card key={stat.label}>
                            <CardHeader className="flex-row items-center justify-between space-y-0">
                                <CardTitle className="text-base">
                                    {stat.label}
                                </CardTitle>
                                <stat.icon className="text-muted-foreground size-5" />
                            </CardHeader>
                            <CardContent>
                                <p className="text-3xl font-semibold">
                                    {stat.value}
                                </p>
                            </CardContent>
                        </Card>
                    ))}
                </section>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent repositories</CardTitle>
                        <CardDescription>
                            Your latest CodeForge projects.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {repositories.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                You do not have repositories yet. Create the
                                first one to initialize a bare Git remote.
                            </p>
                        ) : (
                            repositories.slice(0, 6).map((repository) => (
                                <div
                                    key={repository.id}
                                    className="flex flex-col gap-3 rounded-xl border p-4 lg:flex-row lg:items-center lg:justify-between"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {repository.name}
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            {repository.description ??
                                                'No description provided.'}
                                        </p>
                                    </div>
                                    <Button asChild variant="outline">
                                        <Link
                                            href={`/codeforge/repositories/${repository.id}`}
                                        >
                                            Open
                                        </Link>
                                    </Button>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </CodeForgeLayout>
    );
}
