import { Head, Link, usePage } from '@inertiajs/react';
import { Editor } from 'primereact/editor';
import { useMemo, useState } from 'react';
import { mailFolders, mails } from '@/data/atlantis';

export default function MailDetailPage() {
    const page = usePage<{ id: string }>();
    const [reply, setReply] = useState('');
    const selected = useMemo(() => mails.find((mail) => `${mail.id}` === page.props.id) ?? mails[0], [page.props.id]);

    return (
        <>
            <Head title="Mail Detail" />
            <div className="grid gap-4 lg:grid-cols-[260px,1fr]">
                <aside className="atlantis-card atlantis-dark-card p-4">
                    <Link href="/apps/mail/compose" className="atlantis-outline-btn block text-center">
                        Compose New
                    </Link>
                    <ul className="mt-4 space-y-1">
                        {mailFolders.map((folder) => (
                            <li key={folder.key}>
                                <Link href="/apps/mail/inbox" className="atlantis-folder-btn">
                                    <span>{folder.label}</span>
                                    <span>{folder.count}</span>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </aside>

                <section className="atlantis-card atlantis-dark-card p-4">
                    <div className="mb-4 flex items-center justify-between">
                        <p className="text-xl font-semibold text-white">{selected.subject}</p>
                        <span className="text-slate-400">{selected.date}</span>
                    </div>
                    <p className="mb-3 text-slate-300">From: {selected.from}</p>
                    <p className="mb-6 text-slate-300">{selected.body}</p>
                    <Editor value={reply} onTextChange={(event) => setReply(event.htmlValue || '')} style={{ height: '280px' }} />
                    <div className="mt-4 flex justify-end">
                        <button type="button" className="atlantis-pink-btn">
                            Send
                        </button>
                    </div>
                </section>
            </div>
        </>
    );
}
