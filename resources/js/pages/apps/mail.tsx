import { Head } from '@inertiajs/react';
import { Badge } from 'primereact/badge';
import { Card } from 'primereact/card';
import { Divider } from 'primereact/divider';
import { useState } from 'react';

const inboxSeed = [
    { from: 'Product Team', subject: 'Sprint planning update', unread: true },
    { from: 'Support', subject: 'Client escalation #483', unread: true },
    { from: 'Infra', subject: 'Maintenance completed', unread: false },
];

export default function MailApp() {
    const [selected, setSelected] = useState(0);

    return (
        <>
            <Head title="Mail" />
            <div className="grid gap-4 lg:grid-cols-[320px,1fr]">
                <Card title="Inbox" className="atlantis-card">
                    <ul className="space-y-2">
                        {inboxSeed.map((mail, index) => (
                            <li key={mail.subject}>
                                <button
                                    type="button"
                                    className={`w-full rounded-lg px-3 py-2 text-left ${selected === index ? 'bg-blue-50' : 'hover:bg-slate-50'}`}
                                    onClick={() => setSelected(index)}
                                >
                                    <div className="flex items-center justify-between">
                                        <p className="text-sm font-semibold text-slate-700">{mail.from}</p>
                                        {mail.unread && <Badge value="new" severity="info" />}
                                    </div>
                                    <p className="text-sm text-slate-500">{mail.subject}</p>
                                </button>
                            </li>
                        ))}
                    </ul>
                </Card>

                <Card title={inboxSeed[selected].subject} className="atlantis-card">
                    <p className="text-sm text-slate-500">From: {inboxSeed[selected].from}</p>
                    <Divider />
                    <p className="text-sm text-slate-700">
                        This is a demo mail body for the Atlantis application module. You can navigate the inbox on the left and
                        inspect each selected message.
                    </p>
                </Card>
            </div>
        </>
    );
}
