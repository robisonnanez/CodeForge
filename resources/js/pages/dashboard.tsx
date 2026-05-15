import { Head } from '@inertiajs/react';
import ReactECharts from 'echarts-for-react';
import { Card } from 'primereact/card';
import { kpiCards, monthlyRevenue, trafficSources } from '@/data/atlantis';

export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                {kpiCards.map((item) => (
                    <Card key={item.label} className="atlantis-card atlantis-dark-card">
                        <p className="atlantis-stat-label">{item.label}</p>
                        <h3 className="atlantis-stat-value">{item.value}</h3>
                        <span className="atlantis-stat-delta">{item.delta}</span>
                    </Card>
                ))}
            </div>

            <div className="mt-4 grid gap-4 xl:grid-cols-[2fr,1fr]">
                <Card title="Revenue Overview" className="atlantis-card atlantis-dark-card">
                    <ReactECharts
                        style={{ height: 300 }}
                        option={{
                            backgroundColor: 'transparent',
                            xAxis: {
                                type: 'category',
                                data: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                                axisLabel: { color: '#a8b1cf' },
                            },
                            yAxis: { type: 'value', axisLabel: { color: '#a8b1cf' } },
                            tooltip: { trigger: 'axis' },
                            grid: { top: 20, right: 20, left: 40, bottom: 30 },
                            series: [
                                {
                                    data: monthlyRevenue,
                                    type: 'line',
                                    smooth: true,
                                    areaStyle: { color: 'rgba(239,123,195,0.25)' },
                                    lineStyle: { color: '#ef7bc3', width: 3 },
                                    symbolSize: 6,
                                },
                            ],
                        }}
                    />
                </Card>

                <Card title="Traffic Sources" className="atlantis-card atlantis-dark-card">
                    <ReactECharts
                        style={{ height: 300 }}
                        option={{
                            tooltip: { trigger: 'item' },
                            legend: { bottom: 0, textStyle: { color: '#a8b1cf' } },
                            series: [
                                {
                                    type: 'pie',
                                    radius: ['45%', '70%'],
                                    itemStyle: { borderRadius: 8, borderColor: '#252b48', borderWidth: 2 },
                                    data: trafficSources,
                                },
                            ],
                        }}
                    />
                </Card>
            </div>
        </>
    );
}
