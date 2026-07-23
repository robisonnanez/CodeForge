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
import { RepositoryTabs } from '../shared';

type TreeEntry = {
    mode: string;
    type: 'blob' | 'tree' | 'commit';
    hash: string;
    size: number | null;
    name: string;
};

type TreeResponse = {
    entries: TreeEntry[];
    ref: string;
};

type PageProps = {
    repositoryId: number;
};

export default function RepositoryFilesPage() {
    const { repositoryId } = usePage<PageProps>().props;
    const [entries, setEntries] = useState<TreeEntry[]>([]);
    const [branch, setBranch] = useState('main');

    useEffect(() => {
        void apiRequest<TreeResponse>(
            `/api/repositories/${repositoryId}/tree`,
        ).then((response) => {
            setEntries(response.entries);
            setBranch(response.ref);
        });
    }, [repositoryId]);

    return (
        <CodeForgeLayout>
            <Head title="Repository Files" />
            <div className="space-y-6">
                <RepositoryTabs repositoryId={repositoryId} current="files" />
                <Card>
                    <CardHeader>
                        <CardTitle>Files</CardTitle>
                        <CardDescription>Branch: {branch}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {entries.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No tracked files yet. Push your first commit to
                                populate this list.
                            </p>
                        ) : (
                            entries.map((entry) => (
                                <div
                                    key={entry.hash + entry.name}
                                    className="flex items-center justify-between rounded-xl border px-4 py-3 font-mono text-sm"
                                >
                                    <span>
                                        {entry.type === 'tree' ? '📁' : '📄'}{' '}
                                        {entry.name}
                                    </span>
                                    <span className="text-muted-foreground text-xs">
                                        {entry.size === null
                                            ? entry.type
                                            : `${entry.size} bytes`}
                                    </span>
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </CodeForgeLayout>
    );
}
