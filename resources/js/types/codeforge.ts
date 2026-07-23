export type Repository = {
    id: number;
    name: string;
    namespace: string;
    slug: string;
    description: string | null;
    visibility: 'public' | 'private';
    state: 'provisioning' | 'ready' | 'deleting' | 'trashed' | 'error';
    ssh_url: string;
    https_url: string | null;
    capabilities: {
        read: boolean;
        write: boolean;
        admin: boolean;
        delete: boolean;
    };
    default_branch: string;
    created_at: string;
    updated_at: string;
    owner?: {
        id: number;
        name: string;
        username: string;
    };
};

export type UserSshKey = {
    id: number;
    name: string;
    algorithm: string;
    fingerprint: string;
    last_used_at: string | null;
    revoked_at: string | null;
    created_at: string;
};

export type RepositoryBranchResponse = {
    branches: string[];
    default_branch: string;
};

export type RepositoryCommit = {
    hash: string;
    short_hash: string;
    author_name: string;
    author_email: string;
    committed_at: string;
    message: string;
};

export type RepositoryIssue = {
    id: number;
    repository_id: number;
    user_id: number;
    title: string;
    description: string | null;
    status: 'open' | 'closed';
    created_at: string;
    updated_at: string;
    author?: {
        id: number;
        name: string;
        email: string;
    };
};
