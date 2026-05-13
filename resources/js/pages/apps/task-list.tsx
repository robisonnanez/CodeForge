import { Head } from '@inertiajs/react';
import { Card } from 'primereact/card';
import { Checkbox } from 'primereact/checkbox';
import { useState } from 'react';

type Task = {
    label: string;
    done: boolean;
};

const initialTasks: Task[] = [
    { label: 'Review Atlantis sidebar interactions', done: false },
    { label: 'Prepare demo data for chat module', done: true },
    { label: 'Validate responsive behavior on mobile', done: false },
];

export default function TaskListApp() {
    const [tasks, setTasks] = useState(initialTasks);

    return (
        <>
            <Head title="Task List" />
            <Card title="Task List" className="atlantis-card max-w-2xl">
                <ul className="space-y-3">
                    {tasks.map((task, index) => (
                        <li key={task.label} className="flex items-center gap-3">
                            <Checkbox
                                inputId={`task-${index}`}
                                checked={task.done}
                                onChange={(event) => {
                                    setTasks((previous) =>
                                        previous.map((item, itemIndex) =>
                                            itemIndex === index ? { ...item, done: !!event.checked } : item,
                                        ),
                                    );
                                }}
                            />
                            <label
                                htmlFor={`task-${index}`}
                                className={`text-sm ${task.done ? 'text-slate-400 line-through' : 'text-slate-700'}`}
                            >
                                {task.label}
                            </label>
                        </li>
                    ))}
                </ul>
            </Card>
        </>
    );
}
