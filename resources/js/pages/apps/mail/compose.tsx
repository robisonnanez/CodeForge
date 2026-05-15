import { Head, Link } from '@inertiajs/react';
import { Editor } from 'primereact/editor';
import { InputText } from 'primereact/inputtext';
import { useState } from 'react';
import { mailFolders } from '@/data/atlantis';

export default function MailComposePage() {
    const [content, setContent] = useState('');

    return (
        <>
            <Head title="Mail Compose" />
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
                    <h2 className="mb-4 text-2xl font-semibold text-white">Compose Message</h2>
                    <div className="space-y-3">
                        <InputText className="w-full" placeholder="To" />
                        <InputText className="w-full" placeholder="Subject" />
                        <Editor value={content} onTextChange={(event) => setContent(event.htmlValue || '')} style={{ height: '300px' }} />
                        <div className="flex justify-end">
                            <button type="button" className="atlantis-pink-btn">
                                Send Message
                            </button>
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}
