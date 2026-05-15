import type { CalendarEventItem, ChatThread, KpiCard, MailFolder, MailItem, TaskItem } from '@/types/atlantis';

export const kpiCards: KpiCard[] = [
    { label: 'Sales', value: '$14.2k', delta: '+12%' },
    { label: 'Users', value: '1,284', delta: '+8%' },
    { label: 'Tickets', value: '42', delta: '-3%' },
    { label: 'Tasks', value: '19', delta: '+4%' },
];

export const monthlyRevenue = [120, 180, 140, 210, 250, 240, 300, 290, 340, 360, 390, 430];
export const trafficSources = [
    { value: 41, name: 'Social' },
    { value: 29, name: 'Search' },
    { value: 18, name: 'Ads' },
    { value: 12, name: 'Email' },
];

export const calendarEvents: CalendarEventItem[] = [
    {
        id: '1',
        title: 'Product Sync',
        location: 'Zoom',
        description: 'Weekly planning with design and engineering.',
        start: '2026-05-19T09:00:00',
        end: '2026-05-19T10:00:00',
        color: '#ef7bc3',
    },
    {
        id: '2',
        title: 'Sprint Review',
        location: 'Room A',
        description: 'Demo and retrospective preparation.',
        start: '2026-05-26T14:00:00',
        end: '2026-05-26T15:30:00',
        color: '#7c83ff',
    },
];

export const chatThreads: ChatThread[] = [
    {
        id: 't1',
        name: 'Ioni Bowcher',
        status: 'online',
        preview: 'Sed do eiusmod tempor...',
        avatar: 'IB',
        messages: [
            { id: 'm1', fromMe: false, body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', time: '15:25' },
            { id: 'm2', fromMe: false, body: 'Sed do eiusmod tempor incididunt ut labore.', time: '15:26' },
            { id: 'm3', fromMe: true, body: 'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua', time: '15:26' },
        ],
    },
    {
        id: 't2',
        name: 'Stephen Shaw',
        status: 'away',
        preview: 'Consequat mauris nunc...',
        avatar: 'SS',
        messages: [{ id: 'm4', fromMe: false, body: 'Can we move the release by one day?', time: '11:10' }],
    },
];

export const mailFolders: MailFolder[] = [
    { key: 'inbox', label: 'Inbox', count: 10 },
    { key: 'starred', label: 'Starred', count: 8 },
    { key: 'spam', label: 'Spam', count: 3 },
    { key: 'important', label: 'Important', count: 5 },
    { key: 'sent', label: 'Sent', count: 3 },
    { key: 'archived', label: 'Archived', count: 6 },
    { key: 'trash', label: 'Trash', count: 4 },
];

export const mails: MailItem[] = [
    {
        id: 1000,
        from: 'Ioni Bowcher',
        subject: 'Apply These 7 Secret Techniques To Improve Event',
        excerpt: 'Eget ipsum quam eu a, sit pellentesque molestie tristique.',
        body: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
        date: 'May 30 2022',
        folder: 'inbox',
        starred: true,
    },
    {
        id: 1001,
        from: 'Xuxue Feng',
        subject: 'Consequat sed nibh laoreet ultrices at elit tellus',
        excerpt: 'Nullam purus metus, cras adipiscing magna et, aliquam gravida.',
        body: 'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
        date: 'May 28 2022',
        folder: 'inbox',
        starred: false,
    },
    {
        id: 1002,
        from: 'Ivan Magalhaes',
        subject: 'Consectetur sed dis viverra lorem',
        excerpt: 'Augue felis sed elit rhoncus in.',
        body: 'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
        date: 'May 26 2022',
        folder: 'inbox',
        starred: true,
    },
];

export const tasks: TaskItem[] = [
    { id: 'ta', title: 'Create a New Landing UI', comments: 3, files: 2, due: '13 May', done: false, members: [{ id: 'a', initials: 'IB' }, { id: 'b', initials: 'IM' }] },
    { id: 'tb', title: 'Create Dashboard', comments: 2, files: 4, due: '16 May', done: false, members: [{ id: 'c', initials: 'SS' }] },
    { id: 'tc', title: 'Brand logo design', comments: 4, files: 1, due: '17 May', done: false, members: [{ id: 'd', initials: 'XF' }, { id: 'e', initials: 'AL' }] },
    { id: 'td', title: 'Analyze New Sprint', comments: 3, files: 2, due: '10 May', done: true, members: [{ id: 'f', initials: 'ON' }] },
];
