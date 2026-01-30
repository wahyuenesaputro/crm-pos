import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { BadgeDollarSign, ShoppingBag, Users, TrendingUp, Calendar, Download, FileText, Loader2 } from 'lucide-react';
import api from '../../lib/api';

export default function ReportsPage() {
    const [date, setDate] = useState(new Date().toISOString().split('T')[0]);

    const { data: dailySales, isLoading: loadingSales } = useQuery({
        queryKey: ['daily-sales', date],
        queryFn: async () => {
            const res = await api.get('/reports/daily-sales', { params: { date } });
            return res.data.data;
        }
    });

    const { data: inventory, isLoading: loadingInventory } = useQuery({
        queryKey: ['inventory-report'],
        queryFn: async () => {
            const res = await api.get('/reports/inventory');
            return res.data.data;
        }
    });

    const { data: bestSellers, isLoading: loadingBestSellers } = useQuery({
        queryKey: ['best-sellers', date],
        queryFn: async () => {
            const res = await api.get('/reports/best-sellers', { params: { date_from: date, limit: 10 } });
            return res.data.data || [];
        }
    });

    const exportCSV = (data, filename) => {
        if (!data || data.length === 0) return;
        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(','),
            ...data.map(row => headers.map(h => JSON.stringify(row[h] ?? '')).join(','))
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `${filename}_${date}.csv`;
        link.click();
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Reports & Analytics</h1>
                    <p className="text-gray-500">Business insights and performance metrics</p>
                </div>
                <div className="flex items-center space-x-2 bg-white px-3 py-2 rounded-lg border border-gray-200">
                    <Calendar className="w-4 h-4 text-gray-500" />
                    <input
                        type="date"
                        value={date}
                        onChange={(e) => setDate(e.target.value)}
                        className="border-none focus:outline-none text-sm"
                    />
                </div>
            </div>

            {/* Stats Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <StatCard
                    title="Total Revenue"
                    value={`Rp ${(dailySales?.total_revenue || 0).toLocaleString('id-ID')}`}
                    icon={BadgeDollarSign}
                    color="green"
                    loading={loadingSales}
                />
                <StatCard
                    title="Transactions"
                    value={dailySales?.total_transactions || 0}
                    icon={ShoppingBag}
                    color="blue"
                    loading={loadingSales}
                />
                <StatCard
                    title="Net Revenue"
                    value={`Rp ${(dailySales?.net_revenue || 0).toLocaleString('id-ID')}`}
                    icon={TrendingUp}
                    color="purple"
                    loading={loadingSales}
                />
                <StatCard
                    title="Low Stock Items"
                    value={inventory?.low_stock_count || 0}
                    icon={Users}
                    color="orange"
                    loading={loadingInventory}
                />
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Payment Breakdown */}
                <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="font-bold text-gray-900">Payment Methods</h3>
                    </div>
                    {loadingSales ? (
                        <div className="flex justify-center py-8"><Loader2 className="w-6 h-6 animate-spin text-gray-400" /></div>
                    ) : (
                        <div className="space-y-4">
                            {Object.entries(dailySales?.payment_breakdown || {}).map(([method, amount]) => (
                                <div key={method} className="flex items-center justify-between">
                                    <span className="capitalize text-gray-600">{method}</span>
                                    <div className="flex-1 mx-4 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div
                                            className="h-full bg-primary"
                                            style={{ width: `${Math.min(100, (amount / (dailySales?.total_revenue || 1)) * 100)}%` }}
                                        />
                                    </div>
                                    <span className="font-medium">Rp {parseInt(amount).toLocaleString('id-ID')}</span>
                                </div>
                            ))}
                            {(!dailySales?.payment_breakdown || Object.keys(dailySales?.payment_breakdown).length === 0) && (
                                <p className="text-gray-400 text-center py-4">No payments recorded for this date</p>
                            )}
                        </div>
                    )}
                </div>

                {/* Best Sellers */}
                <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                    <div className="flex justify-between items-center mb-4">
                        <h3 className="font-bold text-gray-900">Best Selling Products</h3>
                        <button
                            onClick={() => exportCSV(bestSellers, 'best_sellers')}
                            className="text-sm text-primary hover:underline flex items-center"
                        >
                            <Download className="w-4 h-4 mr-1" />
                            Export
                        </button>
                    </div>
                    {loadingBestSellers ? (
                        <div className="flex justify-center py-8"><Loader2 className="w-6 h-6 animate-spin text-gray-400" /></div>
                    ) : bestSellers && bestSellers.length > 0 ? (
                        <div className="space-y-3">
                            {bestSellers.slice(0, 5).map((item, idx) => (
                                <div key={idx} className="flex justify-between items-center py-2 border-b border-gray-50 last:border-0">
                                    <div className="flex items-center">
                                        <span className="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-xs font-bold text-gray-600 mr-3">
                                            {idx + 1}
                                        </span>
                                        <span className="font-medium text-gray-900">{item.product_name}</span>
                                    </div>
                                    <span className="text-sm text-gray-500">{item.total_qty} sold</span>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <p className="text-gray-400 text-center py-4">No sales data for this date</p>
                    )}
                </div>
            </div>

            {/* Inventory Status */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
                <div className="flex justify-between items-center mb-4">
                    <h3 className="font-bold text-gray-900">Inventory Status</h3>
                    <button
                        onClick={() => exportCSV(inventory?.items || [], 'inventory')}
                        className="text-sm text-primary hover:underline flex items-center"
                    >
                        <Download className="w-4 h-4 mr-1" />
                        Export CSV
                    </button>
                </div>
                {loadingInventory ? (
                    <div className="flex justify-center py-8"><Loader2 className="w-6 h-6 animate-spin text-gray-400" /></div>
                ) : (
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                                    <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">SKU</th>
                                    <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                                    <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Value</th>
                                    <th className="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {(inventory?.items || []).slice(0, 10).map((item, idx) => (
                                    <tr key={idx} className="hover:bg-gray-50">
                                        <td className="px-4 py-2 text-sm text-gray-900">{item.product_name}</td>
                                        <td className="px-4 py-2 text-sm text-gray-500">{item.sku}</td>
                                        <td className="px-4 py-2 text-sm text-right">{item.stock_qty}</td>
                                        <td className="px-4 py-2 text-sm text-right">Rp {parseInt(item.stock_value || 0).toLocaleString('id-ID')}</td>
                                        <td className="px-4 py-2 text-center">
                                            <span className={`px-2 py-1 text-xs font-medium rounded-full ${item.stock_qty <= (item.min_stock || 10) ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'}`}>
                                                {item.stock_qty <= (item.min_stock || 10) ? 'Low Stock' : 'OK'}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </div>
    );
}

function StatCard({ title, value, icon: Icon, color, loading }) {
    const colorClasses = {
        blue: 'bg-blue-50 text-blue-600',
        green: 'bg-green-50 text-green-600',
        orange: 'bg-orange-50 text-orange-600',
        purple: 'bg-purple-50 text-purple-600',
    };

    return (
        <div className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <div className="flex items-center justify-between mb-4">
                <div className={`p-3 rounded-full ${colorClasses[color]}`}>
                    <Icon className="w-6 h-6" />
                </div>
            </div>
            <div>
                <p className="text-sm font-medium text-gray-500">{title}</p>
                {loading ? (
                    <div className="h-8 flex items-center"><Loader2 className="w-5 h-5 animate-spin text-gray-300" /></div>
                ) : (
                    <h3 className="text-2xl font-bold text-gray-900 mt-1">{value}</h3>
                )}
            </div>
        </div>
    );
}
