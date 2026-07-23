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

type FilesResponse = {
    files: string[];
    branch: string;
};

type PageProps = {
    repositoryId: number;
};

export default function RepositoryFilesPage() {
    const { repositoryId } = usePage<PageProps>().props;
    const [files, setFiles] = useState<string[]>([]);
    const [branch, setBranch] = useState('main');

    useEffect(() => {
        void apiRequest<FilesResponse>(
            `/api/repositories/${repositoryId}/files`,
        ).then((response) => {
            setFiles(response.files);
            setBranch(response.branch);
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
                        {files.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                No tracked files yet. Push your first commit to
                                populate this list.
                            </p>
                        ) : (
                            files.map((file) => (
                                <div
                                    key={file}
                                    className="rounded-xl border px-4 py-3 font-mono text-sm"
                                >
                                    {file}
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>
            </div>
        </CodeForgeLayout>
    );
}
