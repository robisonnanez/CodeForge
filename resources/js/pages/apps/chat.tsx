import { Head } from '@inertiajs/react';
import { Avatar } from 'primereact/avatar';
import { Button } from 'primereact/button';
import { Card } from 'primereact/card';
import { InputText } from 'primereact/inputtext';
import { useMemo, useState } from 'react';

const seedMessages = [
    { from: 'Sofia', body: 'Can you review the latest release notes?' },
    { from: 'You', body: 'Sure, I will check them this afternoon.' },
];

export default function ChatApp() {
    const [messages, setMessages] = useState(seedMessages);
    const [draft, setDraft] = useState('');

    const canSend = useMemo(() => draft.trim().length > 0, [draft]);

    return (
        <>
            <Head title="Chat" />
            <Card title="Chat" className="atlantis-card max-w-2xl">
                <div className="mb-4 space-y-3">
                    {messages.map((message, index) => (
                        <div key={`${message.from}-${index}`} className="flex items-start gap-2">
                            <Avatar label={message.from[0]} shape="circle" size="normal" />
                            <div>
                                <p className="text-xs font-semibold text-slate-500">{message.from}</p>
                                <p className="text-sm text-slate-700">{message.body}</p>
                            </div>
                        </div>
                    ))}
                </div>
                <div className="flex gap-2">
                    <InputText
                        value={draft}
                        className="w-full"
                        placeholder="Type a message"
                        onChange={(event) => setDraft(event.target.value)}
                    />
                    <Button
                        label="Send"
                        disabled={!canSend}
                        onClick={() => {
                            if (!canSend) {
return;
}

                            setMessages((previous) => [...previous, { from: 'You', body: draft.trim() }]);
                            setDraft('');
                        }}
                    />
                </div>
            </Card>
        </>
    );
}
