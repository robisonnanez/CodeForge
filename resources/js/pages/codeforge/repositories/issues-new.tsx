import { Head, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import CodeForgeLayout from '@/layouts/codeforge-layout';
import { ApiError, apiRequest } from '@/lib/codeforge-api';
import { RepositoryTabs } from '../shared';

type PageProps = {
    repositoryId: number;
};

export default function NewIssuePage() {
    const { repositoryId } = usePage<PageProps>().props;
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setSaving(true);
        setError(null);

        try {
            await apiRequest(`/api/repositories/${repositoryId}/issues`, {
                method: 'POST',
                body: JSON.stringify({
                    title,
                    description,
                }),
            });

            window.location.href = `/codeforge/repositories/${repositoryId}/issues`;
        } catch (caughtError) {
            setError(
                caughtError instanceof ApiError
                    ? caughtError.message
                    : 'Unable to create the issue.',
            );
        } finally {
            setSaving(false);
        }
    }

    return (
        <CodeForgeLayout>
            <Head title="New Issue" />
            <div className="space-y-6">
                <RepositoryTabs repositoryId={repositoryId} current="issues" />
                <Card className="max-w-3xl">
                    <CardHeader>
                        <CardTitle>Create issue</CardTitle>
                        <CardDescription>
                            Capture a bug, task or improvement for this
                            repository.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form className="space-y-5" onSubmit={handleSubmit}>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Title
                                </label>
                                <Input
                                    value={title}
                                    onChange={(event) =>
                                        setTitle(event.target.value)
                                    }
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Description
                                </label>
                                <Textarea
                                    value={description}
                                    onChange={(event) =>
                                        setDescription(event.target.value)
                                    }
                                />
                            </div>
                            {error && (
                                <p className="text-sm text-red-600">{error}</p>
                            )}
                            <Button type="submit" disabled={saving}>
                                {saving ? 'Creating...' : 'Create issue'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </CodeForgeLayout>
    );
}
