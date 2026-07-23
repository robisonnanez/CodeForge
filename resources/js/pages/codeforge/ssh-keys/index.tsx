import { Head } from '@inertiajs/react';
import { Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
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
import type { UserSshKey } from '@/types/codeforge';

type SshKeysResponse = {
    ssh_keys: UserSshKey[];
};

export default function SshKeysPage() {
    const [keys, setKeys] = useState<UserSshKey[]>([]);
    const [name, setName] = useState('');
    const [publicKey, setPublicKey] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function loadKeys() {
        const response = await apiRequest<SshKeysResponse>('/api/ssh-keys');
        setKeys(response.ssh_keys);
    }

    useEffect(() => {
        let active = true;

        void apiRequest<SshKeysResponse>('/api/ssh-keys').then((response) => {
            if (active) {
                setKeys(response.ssh_keys);
            }
        });

        return () => {
            active = false;
        };
    }, []);

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setSaving(true);
        setError(null);

        try {
            await apiRequest('/api/ssh-keys', {
                method: 'POST',
                body: JSON.stringify({
                    name,
                    public_key: publicKey,
                }),
            });

            setName('');
            setPublicKey('');
            await loadKeys();
        } catch (caughtError) {
            setError(
                caughtError instanceof ApiError
                    ? caughtError.message
                    : 'Unable to save the SSH key.',
            );
        } finally {
            setSaving(false);
        }
    }

    async function handleDelete(keyId: number) {
        await apiRequest(`/api/ssh-keys/${keyId}`, { method: 'DELETE' });
        await loadKeys();
    }

    return (
        <CodeForgeLayout>
            <Head title="SSH Keys" />
            <div className="space-y-6">
                <Card className="max-w-4xl">
                    <CardHeader>
                        <CardTitle>SSH keys</CardTitle>
                        <CardDescription>
                            Add the public key from your machine so CodeForge
                            can let you clone, pull and push without using the
                            account password.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <form className="space-y-4" onSubmit={handleSubmit}>
                            <div className="space-y-2">
                                <label
                                    htmlFor="ssh-key-name"
                                    className="text-sm font-medium"
                                >
                                    Key name
                                </label>
                                <Input
                                    id="ssh-key-name"
                                    value={name}
                                    onChange={(event) =>
                                        setName(event.target.value)
                                    }
                                    placeholder="Personal laptop"
                                    required
                                />
                            </div>
                            <div className="space-y-2">
                                <label
                                    htmlFor="ssh-public-key"
                                    className="text-sm font-medium"
                                >
                                    Public key
                                </label>
                                <Textarea
                                    id="ssh-public-key"
                                    value={publicKey}
                                    onChange={(event) =>
                                        setPublicKey(event.target.value)
                                    }
                                    placeholder="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAI... user@machine"
                                    className="min-h-32 font-mono text-sm"
                                    required
                                />
                            </div>
                            {error && (
                                <p
                                    role="alert"
                                    aria-live="polite"
                                    className="text-sm text-red-600"
                                >
                                    {error}
                                </p>
                            )}
                            <Button type="submit" disabled={saving}>
                                {saving ? 'Saving...' : 'Add SSH key'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Setup steps</CardTitle>
                        <CardDescription>
                            Copy and paste these commands into the terminal of
                            the machine that will use Git.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <pre className="bg-muted/30 overflow-x-auto rounded-xl p-4 text-sm">
                            <code>
                                {[
                                    'ssh-keygen -t ed25519 -C "tu-correo@ejemplo.com"',
                                    'cat ~/.ssh/id_ed25519.pub',
                                    '# pega esa llave publica aqui en CodeForge',
                                ].join('\n')}
                            </code>
                        </pre>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Registered keys</CardTitle>
                        <CardDescription>
                            Only key metadata is shown after registration.
                            Revocation takes effect at the SSH authorization
                            gateway.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {keys.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                You do not have any SSH keys yet.
                            </p>
                        ) : (
                            keys.map((key) => (
                                <div
                                    key={key.id}
                                    className="space-y-3 rounded-2xl border p-4"
                                >
                                    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                        <div>
                                            <p className="font-medium">
                                                {key.name}
                                            </p>
                                            <p className="text-muted-foreground text-xs">
                                                Added on{' '}
                                                {new Date(
                                                    key.created_at,
                                                ).toLocaleString()}
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                onClick={() =>
                                                    void handleDelete(key.id)
                                                }
                                            >
                                                <Trash2 className="size-4" />
                                                Remove
                                            </Button>
                                        </div>
                                    </div>
                                    <div className="bg-muted/30 rounded-xl border p-4 font-mono text-xs break-all">
                                        {key.algorithm} {key.fingerprint}
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
