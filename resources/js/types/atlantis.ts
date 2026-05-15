export type KpiCard = {
    label: string;
    value: string;
    delta: string;
};

export type ChartPoint = {
    label: string;
    value: number;
};

export type CalendarEventItem = {
    id: string;
    title: string;
    location: string;
    description: string;
    start: string;
    end: string;
    color: string;
};

export type ChatMessage = {
    id: string;
    fromMe: boolean;
    body: string;
    time: string;
};

export type ChatThread = {
    id: string;
    name: string;
    status: 'online' | 'busy' | 'away';
    preview: string;
    avatar: string;
    messages: ChatMessage[];
};

export type MailFolder = {
    key: string;
    label: string;
    count: number;
};

export type MailItem = {
    id: number;
    from: string;
    subject: string;
    excerpt: string;
    body: string;
    date: string;
    folder: string;
    starred: boolean;
};

export type TaskMember = {
    id: string;
    initials: string;
};

export type TaskItem = {
    id: string;
    title: string;
    comments: number;
    files: number;
    due: string;
    done: boolean;
    members: TaskMember[];
};
