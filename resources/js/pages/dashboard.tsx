import { Head } from '@inertiajs/react';
import { Card } from 'primereact/card';
import { ProgressBar } from 'primereact/progressbar';

const stats = [
    { label: 'Sales', value: '$14.2k', delta: '+12%' },
    { label: 'Users', value: '1,284', delta: '+8%' },
    { label: 'Tickets', value: '42', delta: '-3%' },
    { label: 'Tasks', value: '19', delta: '+4%' },
];

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {stats.map((item) => (
                    <Card key={item.label} className="atlantis-card">
                        <p className="atlantis-stat-label">{item.label}</p>
                        <h3 className="atlantis-stat-value">{item.value}</h3>
                        <span className="atlantis-stat-delta">{item.delta}</span>
                    </Card>
                ))}
            </div>

            <div className="mt-4 grid gap-4 lg:grid-cols-2">
                <Card title="Revenue Progress" className="atlantis-card">
                    <p className="mb-3 text-sm text-slate-500">Monthly objective completion</p>
                    <ProgressBar value={72} showValue={false} style={{ height: '0.8rem' }} />
                </Card>
                <Card title="Activity" className="atlantis-card">
                    <ul className="space-y-3 text-sm text-slate-600">
                        <li>New order from Olivia Martin</li>
                        <li>Server backup completed successfully</li>
                        <li>3 pending approvals in workflow queue</li>
                    </ul>
                </Card>
            </div>
        </>
    );
}
