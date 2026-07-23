import { Head } from '@inertiajs/react';
import { Dropdown } from 'primereact/dropdown';
import { useState } from 'react';
import type { FormEvent } from 'react';
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
import type { Repository } from '@/types/codeforge';

type RepositoryCreateResponse = {
    repository: Repository;
    message: string;
};

export default function NewRepositoryPage() {
    const [name, setName] = useState('');
    const [slug, setSlug] = useState('');
    const [description, setDescription] = useState('');
    const [visibility, setVisibility] = useState<'public' | 'private'>(
        'private',
    );
    const [defaultBranch, setDefaultBranch] = useState('main');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setSaving(true);
        setError(null);

        try {
            const response = await apiRequest<RepositoryCreateResponse>(
                '/api/repositories',
                {
                    method: 'POST',
                    body: JSON.stringify({
                        name,
                        slug: slug || undefined,
                        description: description || undefined,
                        visibility,
                        default_branch: defaultBranch,
                    }),
                },
            );

            window.location.href = `/codeforge/repositories/${response.repository.id}`;
        } catch (caughtError) {
            setError(
                caughtError instanceof ApiError
                    ? caughtError.message
                    : 'Unable to create the repository.',
            );
        } finally {
            setSaving(false);
        }
    }

    return (
        <CodeForgeLayout>
            <Head title="New Repository" />
            <Card className="max-w-3xl">
                <CardHeader>
                    <CardTitle>Create repository</CardTitle>
                    <CardDescription>
                        CodeForge will create a bare Git repository on the
                        server and generate its SSH URL.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form className="space-y-5" onSubmit={handleSubmit}>
                        <div className="grid gap-5 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Name
                                </label>
                                <Input
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                    placeholder="demo-api"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Slug
                                </label>
                                <Input
                                    value={slug}
                                    onChange={(event) =>
                                        setSlug(event.target.value)
                                    }
                                    placeholder="demo-api"
                                />
                            </div>
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
                                placeholder="What is this repository for?"
                            />
                        </div>
                        <div className="grid gap-5 md:grid-cols-2">
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Visibility
                                </label>
                                <Dropdown
                                    value={visibility}
                                    options={[
                                        { label: 'Private', value: 'private' },
                                        { label: 'Public', value: 'public' },
                                    ]}
                                    onChange={(event) =>
                                        setVisibility(
                                            event.value as 'public' | 'private',
                                        )
                                    }
                                    className="w-full"
                                    placeholder="Select visibility"
                                />
                            </div>
                            <div className="space-y-2">
                                <label className="text-sm font-medium">
                                    Default branch
                                </label>
                                <Input
                                    value={defaultBranch}
                                    onChange={(event) =>
                                        setDefaultBranch(event.target.value)
                                    }
                                    placeholder="main"
                                />
                            </div>
                        </div>
                        {error && (
                            <p className="text-sm text-red-600">{error}</p>
                        )}
                        <Button type="submit" disabled={saving}>
                            {saving ? 'Creating...' : 'Create repository'}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </CodeForgeLayout>
    );
}
