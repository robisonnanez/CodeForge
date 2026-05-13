import { Head } from '@inertiajs/react';
import { Calendar } from 'primereact/calendar';
import { Card } from 'primereact/card';
import { useState } from 'react';

export default function CalendarApp() {
    const [date, setDate] = useState<Date | null>(new Date());

    return (
        <>
            <Head title="Calendar" />
            <Card title="Calendar" className="atlantis-card max-w-xl">
                <p className="mb-3 text-sm text-slate-500">Demo calendar interaction for the Atlantis app module.</p>
                <Calendar value={date} onChange={(event) => setDate(event.value ?? null)} inline showWeek />
            </Card>
        </>
    );
}
