import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import FullCalendar from '@fullcalendar/react';
import timeGridPlugin from '@fullcalendar/timegrid';
import { Head } from '@inertiajs/react';
import { Dialog } from 'primereact/dialog';
import { Dropdown } from 'primereact/dropdown';
import { InputText } from 'primereact/inputtext';
import { useState } from 'react';
import { calendarEvents } from '@/data/atlantis';
import type { CalendarEventItem } from '@/types/atlantis';

const colorOptions = [
    { label: 'Company A', value: '#ef7bc3' },
    { label: 'Company B', value: '#7c83ff' },
    { label: 'Company C', value: '#8cd4ff' },
];

export default function CalendarApp() {
    const [events, setEvents] = useState(calendarEvents);
    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<CalendarEventItem>({
        id: '',
        title: '',
        location: '',
        description: '',
        start: '',
        end: '',
        color: '#ef7bc3',
    });

    return (
        <>
            <Head title="Calendar" />
            <div className="atlantis-card atlantis-dark-card p-4">
                <FullCalendar
                    plugins={[dayGridPlugin, timeGridPlugin, interactionPlugin]}
                    initialView="dayGridMonth"
                    headerToolbar={{
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay',
                    }}
                    events={events.map((event) => ({
                        ...event,
                        backgroundColor: event.color,
                        borderColor: event.color,
                    }))}
                    dateClick={(info) => {
                        setDraft((previous) => ({
                            ...previous,
                            id: `${Date.now()}`,
                            start: `${info.dateStr}T09:00:00`,
                            end: `${info.dateStr}T10:00:00`,
                        }));
                        setOpen(true);
                    }}
                />
            </div>

            <Dialog
                header="New Event"
                visible={open}
                className="atlantis-modal"
                onHide={() => setOpen(false)}
                footer={
                    <button
                        type="button"
                        className="atlantis-pink-btn"
                        onClick={() => {
                            if (!draft.title.trim()) {
                                return;
                            }

                            setEvents((previous) => [...previous, draft]);
                            setOpen(false);
                        }}
                    >
                        Save
                    </button>
                }
            >
                <div className="grid gap-3 md:grid-cols-2">
                    <div>
                        <label className="atlantis-label">Title</label>
                        <InputText
                            className="w-full"
                            value={draft.title}
                            onChange={(e) =>
                                setDraft({ ...draft, title: e.target.value })
                            }
                        />
                    </div>
                    <div>
                        <label className="atlantis-label">Location</label>
                        <InputText
                            className="w-full"
                            value={draft.location}
                            onChange={(e) =>
                                setDraft({ ...draft, location: e.target.value })
                            }
                        />
                    </div>
                    <div className="md:col-span-2">
                        <label className="atlantis-label">
                            Event Description
                        </label>
                        <InputText
                            className="w-full"
                            value={draft.description}
                            onChange={(e) =>
                                setDraft({
                                    ...draft,
                                    description: e.target.value,
                                })
                            }
                        />
                    </div>
                    <div>
                        <label className="atlantis-label">Start Date</label>
                        <InputText
                            className="w-full"
                            value={draft.start}
                            onChange={(e) =>
                                setDraft({ ...draft, start: e.target.value })
                            }
                        />
                    </div>
                    <div>
                        <label className="atlantis-label">End Date</label>
                        <InputText
                            className="w-full"
                            value={draft.end}
                            onChange={(e) =>
                                setDraft({ ...draft, end: e.target.value })
                            }
                        />
                    </div>
                    <div className="md:col-span-2">
                        <label className="atlantis-label">Color</label>
                        <Dropdown
                            className="w-full"
                            options={colorOptions}
                            value={draft.color}
                            onChange={(e) =>
                                setDraft({ ...draft, color: e.value })
                            }
                        />
                    </div>
                </div>
            </Dialog>
        </>
    );
}
