import { router } from '@inertiajs/react';
import { useEffect } from 'react';

export default function MailApp() {
    useEffect(() => {
        router.visit('/apps/mail/inbox', { replace: true });
    }, []);

    return null;
}
