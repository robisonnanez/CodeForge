import { Head } from '@inertiajs/react';
import { Avatar } from 'primereact/avatar';
import { Button } from 'primereact/button';
import { InputText } from 'primereact/inputtext';
import { useMemo, useState } from 'react';
import { chatThreads } from '@/data/atlantis';

export default function ChatApp() {
    const [threads, setThreads] = useState(chatThreads);
    const [active, setActive] = useState(chatThreads[0].id);
    const [draft, setDraft] = useState('');
    const current = useMemo(() => threads.find((thread) => thread.id === active) ?? threads[0], [active, threads]);

    return (
        <>
            <Head title="Chat" />
            <div className="grid gap-4 lg:grid-cols-[320px,1fr]">
                <section className="atlantis-card atlantis-dark-card p-4">
                    <InputText className="w-full" placeholder="Search" />
                    <ul className="mt-4 space-y-2">
                        {threads.map((thread) => (
                            <li key={thread.id}>
                                <button
                                    type="button"
                                    className={`atlantis-thread-btn ${active === thread.id ? 'atlantis-thread-active' : ''}`}
                                    onClick={() => setActive(thread.id)}
                                >
                                    <Avatar label={thread.avatar} shape="circle" />
                                    <div className="text-left">
                                        <p className="font-semibold text-white">{thread.name}</p>
                                        <p className="text-sm text-slate-400">{thread.preview}</p>
                                    </div>
                                </button>
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="atlantis-card atlantis-dark-card flex min-h-[620px] flex-col p-4">
                    <div className="mb-4 flex items-center justify-between border-b border-slate-700 pb-4">
                        <div className="flex items-center gap-3">
                            <Avatar label={current.avatar} shape="circle" />
                            <div>
                                <p className="font-semibold text-white">{current.name}</p>
                                <p className="text-sm text-slate-400">Last active 1 hour ago</p>
                            </div>
                        </div>
                        <div className="flex gap-2 text-slate-300">
                            <i className="pi pi-phone" />
                            <i className="pi pi-ellipsis-v" />
                        </div>
                    </div>

                    <div className="flex-1 space-y-4 overflow-y-auto pb-4">
                        {current.messages.map((message) => (
                            <div key={message.id} className={message.fromMe ? 'text-right' : ''}>
                                <div className={`atlantis-bubble ${message.fromMe ? 'atlantis-bubble-me' : 'atlantis-bubble-them'}`}>
                                    {message.body}
                                </div>
                                <p className="mt-1 text-xs text-slate-400">{message.time}</p>
                            </div>
                        ))}
                    </div>

                    <div className="mt-auto flex gap-2">
                        <InputText value={draft} className="w-full" placeholder="Type a message" onChange={(e) => setDraft(e.target.value)} />
                        <Button
                            label="Send"
                            className="atlantis-pink-btn"
                            onClick={() => {
                                if (!draft.trim()) {
                                    return;
                                }

                                setThreads((previous) =>
                                    previous.map((thread) =>
                                        thread.id === active
                                            ? {
                                                  ...thread,
                                                  messages: [
                                                      ...thread.messages,
                                                      { id: `${Date.now()}`, fromMe: true, body: draft.trim(), time: '15:30' },
                                                  ],
                                              }
                                            : thread,
                                    ),
                                );
                                setDraft('');
                            }}
                        />
                    </div>
                </section>
            </div>
        </>
    );
}
