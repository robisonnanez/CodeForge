import { Head, Link } from '@inertiajs/react';
import { InputText } from 'primereact/inputtext';
import { mails, mailFolders } from '@/data/atlantis';

export default function MailInboxPage() {
    return (
        <>
            <Head title="Mail Inbox" />
            <div className="grid gap-4 lg:grid-cols-[260px,1fr]">
                <aside className="atlantis-card atlantis-dark-card p-4">
                    <Link href="/apps/mail/compose" className="atlantis-outline-btn block text-center">
                        Compose New
                    </Link>
                    <ul className="mt-4 space-y-1">
                        {mailFolders.map((folder) => (
                            <li key={folder.key}>
                                <button type="button" className={`atlantis-folder-btn ${folder.key === 'inbox' ? 'atlantis-folder-active' : ''}`}>
                                    <span>{folder.label}</span>
                                    <span>{folder.count}</span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </aside>

                <section className="atlantis-card atlantis-dark-card p-4">
                    <div className="mb-4 flex justify-end">
                        <InputText className="w-full max-w-sm" placeholder="Search Mail" />
                    </div>
                    <ul className="space-y-1">
                        {mails.map((mail) => (
                            <li key={mail.id}>
                                <Link href={`/apps/mail/detail/${mail.id}`} className="atlantis-mail-row">
                                    <p className="font-semibold text-white">{mail.from}</p>
                                    <p className="text-sm text-slate-300">{mail.subject}</p>
                                    <p className="truncate text-sm text-slate-400">{mail.excerpt}</p>
                                    <p className="text-sm text-slate-400">{mail.date}</p>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </section>
            </div>
        </>
    );
}
