import { Head } from '@inertiajs/react';
import { Dialog } from 'primereact/dialog';
import { InputText } from 'primereact/inputtext';
import { useMemo, useState } from 'react';
import { tasks } from '@/data/atlantis';

export default function TaskListApp() {
    const [items, setItems] = useState(tasks);
    const [open, setOpen] = useState(false);
    const [title, setTitle] = useState('');
    const [description, setDescription] = useState('');
    const todo = useMemo(() => items.filter((item) => !item.done), [items]);
    const done = useMemo(() => items.filter((item) => item.done), [items]);

    return (
        <>
            <Head title="Task List" />
            <div className="atlantis-card atlantis-dark-card p-4">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-2xl font-semibold text-white">
                        Task List
                    </h2>
                    <button
                        type="button"
                        className="atlantis-pink-btn"
                        onClick={() => setOpen(true)}
                    >
                        + Create Task
                    </button>
                </div>

                <h3 className="mb-2 text-lg font-semibold text-white">ToDo</h3>
                <ul className="mb-6">
                    {todo.map((item) => (
                        <li key={item.id} className="atlantis-task-row">
                            <label className="flex items-center gap-3 text-white">
                                <input
                                    type="checkbox"
                                    checked={item.done}
                                    onChange={() =>
                                        setItems((previous) =>
                                            previous.map((entry) =>
                                                entry.id === item.id
                                                    ? {
                                                          ...entry,
                                                          done: !entry.done,
                                                      }
                                                    : entry,
                                            ),
                                        )
                                    }
                                />
                                {item.title}
                            </label>
                            <p className="text-sm text-slate-300">
                                {item.comments} comments | {item.files} files |{' '}
                                {item.due}
                            </p>
                        </li>
                    ))}
                </ul>

                <h3 className="mb-2 text-lg font-semibold text-white">
                    Completed
                </h3>
                <ul>
                    {done.map((item) => (
                        <li key={item.id} className="atlantis-task-row">
                            <label className="flex items-center gap-3 text-slate-300 line-through">
                                <input
                                    type="checkbox"
                                    checked={item.done}
                                    onChange={() =>
                                        setItems((previous) =>
                                            previous.map((entry) =>
                                                entry.id === item.id
                                                    ? {
                                                          ...entry,
                                                          done: !entry.done,
                                                      }
                                                    : entry,
                                            ),
                                        )
                                    }
                                />
                                {item.title}
                            </label>
                            <p className="text-sm text-slate-400">
                                {item.comments} comments | {item.files} files |{' '}
                                {item.due}
                            </p>
                        </li>
                    ))}
                </ul>
            </div>

            <Dialog
                header="Create Task"
                visible={open}
                className="atlantis-modal"
                onHide={() => setOpen(false)}
            >
                <div className="space-y-3">
                    <div>
                        <label htmlFor="task-name" className="atlantis-label">
                            Task Name
                        </label>
                        <InputText
                            id="task-name"
                            className="w-full"
                            value={title}
                            onChange={(event) => setTitle(event.target.value)}
                        />
                    </div>
                    <div>
                        <label
                            htmlFor="task-description"
                            className="atlantis-label"
                        >
                            Description
                        </label>
                        <textarea
                            id="task-description"
                            className="min-h-44 w-full rounded-md border bg-transparent p-3"
                            value={description}
                            onChange={(event) =>
                                setDescription(event.target.value)
                            }
                        />
                    </div>
                    <div className="flex justify-end gap-2">
                        <button
                            type="button"
                            className="atlantis-cancel-btn"
                            onClick={() => setOpen(false)}
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            className="atlantis-pink-btn"
                            onClick={() => {
                                if (!title.trim()) {
                                    return;
                                }

                                setItems((previous) => [
                                    {
                                        id: `${Date.now()}`,
                                        title: title.trim(),
                                        comments: 0,
                                        files: 0,
                                        due: '13 May',
                                        done: false,
                                        members: [],
                                    },
                                    ...previous,
                                ]);
                                setTitle('');
                                setDescription('');
                                setOpen(false);
                            }}
                        >
                            Save
                        </button>
                    </div>
                </div>
            </Dialog>
        </>
    );
}
