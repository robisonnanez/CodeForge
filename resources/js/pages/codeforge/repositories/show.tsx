import { Head, Link, usePage } from '@inertiajs/react';
import {
    Copy,
    FolderTree,
    GitBranch,
    KeyRound,
    ShieldCheck,
    TerminalSquare,
} from 'lucide-react';
import { SelectButton } from 'primereact/selectbutton';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useClipboard } from '@/hooks/use-clipboard';
import CodeForgeLayout from '@/layouts/codeforge-layout';
import { apiRequest } from '@/lib/codeforge-api';
import type { Repository, RepositoryBranchResponse } from '@/types/codeforge';
import { RepositoryTabs } from '../shared';

type RepositoryShowResponse = {
    repository: Repository;
};

type PageProps = {
    repositoryId: number;
};

export default function RepositoryShowPage() {
    const { repositoryId } = usePage<PageProps>().props;
    const [repository, setRepository] = useState<Repository | null>(null);
    const [branches, setBranches] = useState<string[]>([]);
    const [copiedText, copy] = useClipboard();
    const [cloneMode, setCloneMode] = useState<'ssh' | 'https'>('ssh');

    useEffect(() => {
        void Promise.all([
            apiRequest<RepositoryShowResponse>(
                `/api/repositories/${repositoryId}`,
            ),
            apiRequest<RepositoryBranchResponse>(
                `/api/repositories/${repositoryId}/branches`,
            ),
        ]).then(([repositoryResponse, branchResponse]) => {
            setRepository(repositoryResponse.repository);
            setBranches(branchResponse.branches);
        });
    }, [repositoryId]);

    const sshSteps = useMemo(() => {
        if (!repository) {
            return [];
        }

        return [
            `ssh-keygen -t ed25519 -C "${repository.owner?.username ?? 'codeforge-user'}"`,
            'cat ~/.ssh/id_ed25519.pub',
            `git clone ${repository.ssh_url}`,
            `git remote set-url origin ${repository.ssh_url}`,
        ];
    }, [repository]);

    const firstPushSteps = useMemo(() => {
        if (!repository) {
            return [];
        }

        return [
            `mkdir ${repository.slug}`,
            `cd ${repository.slug}`,
            `git init -b ${repository.default_branch}`,
            `git remote add origin ${repository.ssh_url}`,
            `echo "# ${repository.name}" > README.md`,
            'git add README.md',
            'git commit -m "Initial commit"',
            `git push -u origin ${repository.default_branch}`,
        ];
    }, [repository]);

    const pushFixSteps = useMemo(() => {
        if (!repository) {
            return [];
        }

        return [
            `git remote -v`,
            `git remote set-url origin ${repository.ssh_url}`,
            `git push -u origin ${repository.default_branch}`,
        ];
    }, [repository]);

    const cloneOptions = useMemo(
        () => [
            { label: 'SSH', value: 'ssh' as const },
            { label: 'HTTPS', value: 'https' as const },
        ],
        [],
    );

    const cloneTitle =
        cloneMode === 'ssh' ? 'Clone over SSH' : 'Clone over HTTPS';
    const cloneDescription =
        cloneMode === 'ssh'
            ? 'Recommended for push and pull. Add your public key once and reuse it in every repository.'
            : repository?.https_url
              ? 'Use a personal access token as the password.'
              : 'HTTPS Git transport is unavailable.';
    const cloneValue =
        cloneMode === 'ssh'
            ? (repository?.ssh_url ?? 'Loading SSH URL...')
            : (repository?.https_url ?? 'Not configured yet');
    const cloneDisabled = cloneMode === 'https' && !repository?.https_url;
    const cloneCopied =
        copiedText ===
        (cloneMode === 'ssh' ? repository?.ssh_url : repository?.https_url);

    return (
        <CodeForgeLayout>
            <Head title="Repository Overview" />
            <div className="space-y-6">
                <RepositoryTabs
                    repositoryId={repositoryId}
                    current="overview"
                />

                <Card>
                    <CardHeader className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div>
                            <CardTitle>
                                {repository?.name ?? 'Loading repository...'}
                            </CardTitle>
                            <CardDescription>
                                {repository?.description ??
                                    'Repository overview, clone URLs and SSH access.'}
                            </CardDescription>
                        </div>
                        {repository && (
                            <div className="flex flex-wrap gap-2">
                                <Button
                                    variant="outline"
                                    onClick={() =>
                                        void copy(repository.ssh_url)
                                    }
                                >
                                    <Copy className="size-4" />
                                    {copiedText === repository.ssh_url
                                        ? 'Copied SSH URL'
                                        : 'Copy SSH URL'}
                                </Button>
                                <Button asChild variant="outline">
                                    <Link href="/codeforge/ssh-keys">
                                        <KeyRound className="size-4" />
                                        Manage SSH keys
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </CardHeader>
                    <CardContent className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <InfoTile
                            title="Visibility"
                            value={repository?.visibility ?? '-'}
                            icon={ShieldCheck}
                        />
                        <InfoTile
                            title="Default branch"
                            value={repository?.default_branch ?? '-'}
                            icon={GitBranch}
                        />
                        <InfoTile
                            title="Branches"
                            value={String(branches.length)}
                            icon={FolderTree}
                        />
                        <InfoTile
                            title="Owner"
                            value={repository?.owner?.name ?? '-'}
                            icon={ShieldCheck}
                        />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="space-y-4">
                        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <CardTitle>{cloneTitle}</CardTitle>
                                <CardDescription>
                                    {cloneDescription}
                                </CardDescription>
                            </div>
                            <SelectButton
                                value={cloneMode}
                                options={cloneOptions}
                                onChange={(event) =>
                                    setCloneMode(event.value as 'ssh' | 'https')
                                }
                                allowEmpty={false}
                            />
                        </div>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <CloneBox
                            title={
                                cloneMode === 'ssh'
                                    ? 'SSH remote'
                                    : 'HTTPS remote'
                            }
                            value={cloneValue}
                            isCopied={cloneCopied}
                            onCopy={() => {
                                if (cloneMode === 'ssh' && repository) {
                                    void copy(repository.ssh_url);
                                }

                                if (
                                    cloneMode === 'https' &&
                                    repository?.https_url
                                ) {
                                    void copy(repository.https_url);
                                }
                            }}
                            disabled={cloneDisabled}
                        />

                        {cloneMode === 'ssh' ? (
                            <>
                                <CommandList
                                    title="Configure your SSH access"
                                    commands={sshSteps}
                                />
                                <p className="text-muted-foreground text-sm">
                                    If Git asks for{' '}
                                    <span className="font-mono">
                                        git@localhost
                                    </span>
                                    , your local remote is still pointing to the
                                    old URL. Use the commands below to fix it.
                                </p>
                                <CommandList
                                    title="Fix an existing remote"
                                    commands={pushFixSteps}
                                />
                            </>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                Authenticate with your username and a personal
                                access token that has the required repository
                                ability.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Copy and paste Git commands</CardTitle>
                        <CardDescription>
                            Use these exact commands in your terminal to create
                            the first commit and push it to this repository.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <CommandList
                            title="First push from a new folder"
                            commands={firstPushSteps}
                        />
                        <div className="flex flex-wrap gap-2">
                            <Button asChild variant="outline">
                                <Link
                                    href={`/codeforge/repositories/${repositoryId}/files`}
                                >
                                    Browse files
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link
                                    href={`/codeforge/repositories/${repositoryId}/commits`}
                                >
                                    View commits
                                </Link>
                            </Button>
                            <Button asChild variant="outline">
                                <Link
                                    href={`/codeforge/repositories/${repositoryId}/issues`}
                                >
                                    Open issues
                                </Link>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </CodeForgeLayout>
    );
}

function CloneBox({
    title,
    value,
    isCopied,
    onCopy,
    disabled = false,
}: {
    title: string;
    value: string;
    isCopied: boolean;
    onCopy: () => void;
    disabled?: boolean;
}) {
    return (
        <div className="space-y-3 rounded-2xl border p-4">
            <div className="flex items-center justify-between gap-3">
                <p className="text-sm font-medium">{title}</p>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={onCopy}
                    disabled={disabled}
                >
                    <Copy className="size-4" />
                    {isCopied ? 'Copied' : 'Copy'}
                </Button>
            </div>
            <div className="bg-muted/30 rounded-xl border p-4 font-mono text-sm break-all">
                {value}
            </div>
        </div>
    );
}

function CommandList({
    title,
    commands,
}: {
    title: string;
    commands: string[];
}) {
    return (
        <div className="space-y-3 rounded-2xl border p-4">
            <div className="flex items-center gap-2">
                <TerminalSquare className="text-muted-foreground size-4" />
                <p className="text-sm font-medium">{title}</p>
            </div>
            <pre className="bg-muted/30 overflow-x-auto rounded-xl p-4 text-sm">
                <code>{commands.join('\n')}</code>
            </pre>
        </div>
    );
}

function InfoTile({
    title,
    value,
    icon: Icon,
}: {
    title: string;
    value: string;
    icon: typeof ShieldCheck;
}) {
    return (
        <div className="rounded-2xl border p-4">
            <div className="mb-3 flex items-center justify-between">
                <p className="text-muted-foreground text-sm">{title}</p>
                <Icon className="text-muted-foreground size-4" />
            </div>
            <p className="text-xl font-semibold capitalize">{value}</p>
        </div>
    );
}
