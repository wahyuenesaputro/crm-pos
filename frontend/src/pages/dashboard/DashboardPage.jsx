import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { BadgeDollarSign, ShoppingBag, Users, AlertTriangle, Calendar } from 'lucide-react';
import StatCard from './components/StatCard';
import api from '../../lib/api';

export default function DashboardPage() {
    const [date] = useState(new Date().toISOString().split('T')[0]);

    const { data: dailySales } = useQuery({
        queryKey: ['daily-sales', date],
        queryFn: async () => {
            const res = await api.get('/reports/daily-sales', { params: { date } });
            return res.data.data;
        }
    });

    const { data: inventory } = useQuery({
        queryKey: ['inventory-report'],
        queryFn: async () => {
            const res = await api.get('/reports/inventory');
            return res.data.data;
        }
    });

    return (
        <div className="space-y-6">
            <div className="flex justify-between items-center">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p className="text-gray-500">Overview for {date}</p>
                </div>
                <div className="flex items-center space-x-2 bg-white px-3 py-2 rounded-md border border-gray-200">
                    <Calendar className="w-4 h-4 text-gray-500" />
                    <span className="text-sm font-medium">{date}</span>
                </div>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                    title="Total Revenue"
                    value={`Rp ${(dailySales?.total_revenue || 0).toLocaleString('id-ID')}`}
                    icon={BadgeDollarSign}
                    color="green"
                    trend="up"
                    trendValue="+12%"
                />
                <StatCard
                    title="Transactions"
                    value={dailySales?.total_transactions || 0}
                    icon={ShoppingBag}
                    color="blue"
                />
                <StatCard
                    title="Low Stock Items"
                    value={inventory?.low_stock_count || 0}
                    icon={AlertTriangle}
                    color="orange"
                    trend={inventory?.low_stock_count > 0 ? 'down' : 'up'}
                    trendValue="Needs Attention"
                />
                <StatCard
                    title="Total Stock Value"
                    value={`Rp ${(inventory?.total_stock_value || 0).toLocaleString('id-ID')}`}
                    icon={Users}
                    color="purple"
                />
            </div>

            {/* Recent Activity / Charts */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                    <h3 className="font-bold text-gray-900 mb-4">Payment Methods</h3>
                    <div className="space-y-4">
                        {Object.entries(dailySales?.payment_breakdown || {}).map(([method, amount]) => (
                            <div key={method} className="flex items-center justify-between">
                                <span className="capitalize text-gray-600">{method}</span>
                                <div className="flex-1 mx-4 h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div
                                        className="h-full bg-primary"
                                        style={{ width: `${(amount / (dailySales?.total_revenue || 1)) * 100}%` }}
                                    />
                                </div>
                                <span className="font-medium">Rp {amount.toLocaleString('id-ID')}</span>
                            </div>
                        ))}
                        {(!dailySales?.payment_breakdown || Object.keys(dailySales?.payment_breakdown).length === 0) && (
                            <p className="text-gray-400 text-center py-4">No payments recorded today</p>
                        )}
                    </div>
                </div>

                <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                    <h3 className="font-bold text-gray-900 mb-4">Inventory Status</h3>
                    <div className="space-y-4">
                        {inventory?.items?.slice(0, 5).map((item) => (
                            <div key={item.sku} className="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
                                <div>
                                    <p className="font-medium text-gray-900">{item.product_name}</p>
                                    <p className="text-xs text-gray-500">{item.sku}</p>
                                </div>
                                <div className="text-right">
                                    <span className={`inline-flex px-2 py-1 rounded-full text-xs font-medium ${item.stock_qty <= 10 ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`}>
                                        {item.stock_qty} Units
                                    </span>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}
