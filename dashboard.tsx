import { Head, usePage } from '@inertiajs/react';
import { Messages } from 'primereact/messages';
import { useEffect, useRef } from 'react';

export default function Dashboard() {
  const messages = useRef<Messages>(null);
  const { auth } = usePage<{ auth: { welcomeMessage?: string | null } }>().props;

  useEffect(() => {
    if (auth.welcomeMessage) {
      messages.current?.show([
        {
          severity: 'success',
          sticky: false,
          summary: 'Bienvenido',
          detail: auth.welcomeMessage,
          closable: true,
        },
      ]);
    }
  }, [auth.welcomeMessage]);

  return (
    <>
      <Head title="Dashboard" />
      <div className="space-y-4">
        <Messages ref={messages} />
        <div className="atlantis-card atlantis-dark-card p-6">
          <h1 className="text-2xl font-semibold text-white">Dashboard</h1>
        </div>
      </div>
    </>
  );
}
