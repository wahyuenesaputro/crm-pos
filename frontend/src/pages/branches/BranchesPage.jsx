import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2, Building2, MapPin, Phone, Loader2, X } from 'lucide-react';
import api from '../../lib/api';

export default function BranchesPage() {
    const queryClient = useQueryClient();
    const [isModalOpen, setModalOpen] = useState(false);
    const [editingBranch, setEditingBranch] = useState(null);

    const { data: branches = [], isLoading, error, refetch } = useQuery({
        queryKey: ['branches'],
        queryFn: async () => {
            try {
                const response = await api.get('/branches');
                return response.data.data || [];
            } catch (err) {
                // If no endpoint exists, return empty for now
                console.error('Branches endpoint not available:', err);
                return [];
            }
        }
    });

    const deleteMutation = useMutation({
        mutationFn: async (id) => {
            await api.delete(`/branches/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries(['branches']);
        },
        onError: (err) => {
            alert('Delete failed: ' + (err.response?.data?.message || err.message));
        }
    });

    const handleDelete = (branch) => {
        if (confirm(`Are you sure you want to delete "${branch.name}"?`)) {
            deleteMutation.mutate(branch.id);
        }
    };

    return (
        <div className="space-y-6">
            <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Branches</h1>
                    <p className="text-gray-500 dark:text-gray-400">Manage your store locations</p>
                </div>
                <button
                    onClick={() => { setEditingBranch(null); setModalOpen(true); }}
                    className="flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors"
                >
                    <Plus className="w-4 h-4 mr-2" />
                    Add Branch
                </button>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {isLoading ? (
                    <div className="col-span-full flex items-center justify-center h-64">
                        <Loader2 className="w-8 h-8 animate-spin text-gray-400" />
                    </div>
                ) : error ? (
                    <div className="col-span-full text-center text-red-500 py-10">
                        Failed to load branches. <button onClick={() => refetch()} className="underline">Retry</button>
                    </div>
                ) : branches.length === 0 ? (
                    <div className="col-span-full text-center py-16 bg-white dark:bg-gray-800 rounded-lg border border-gray-100 dark:border-gray-700">
                        <Building2 className="w-12 h-12 text-gray-300 mx-auto mb-4" />
                        <p className="text-gray-500 dark:text-gray-400">No branches configured yet.</p>
                        <p className="text-sm text-gray-400 mt-2">Branches are managed via the database seeder or admin panel.</p>
                    </div>
                ) : (
                    branches.map((branch) => (
                        <div
                            key={branch.id}
                            className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-5 hover:shadow-md transition-shadow"
                        >
                            <div className="flex justify-between items-start mb-4">
                                <div className="flex items-center">
                                    <div className="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center mr-3">
                                        <Building2 className="w-5 h-5 text-primary" />
                                    </div>
                                    <div>
                                        <h3 className="font-semibold text-gray-900 dark:text-white">{branch.name}</h3>
                                        <span className={`text-xs px-2 py-0.5 rounded-full ${branch.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'}`}>
                                            {branch.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                    </div>
                                </div>
                                <div className="flex gap-1">
                                    <button
                                        onClick={() => { setEditingBranch(branch); setModalOpen(true); }}
                                        className="p-1.5 text-gray-400 hover:text-primary hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                                    >
                                        <Pencil className="w-4 h-4" />
                                    </button>
                                    <button
                                        onClick={() => handleDelete(branch)}
                                        className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
                                    >
                                        <Trash2 className="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            <div className="space-y-2 text-sm">
                                {branch.address && (
                                    <div className="flex items-start text-gray-500 dark:text-gray-400">
                                        <MapPin className="w-4 h-4 mr-2 mt-0.5 flex-shrink-0" />
                                        <span>{branch.address}</span>
                                    </div>
                                )}
                                {branch.phone && (
                                    <div className="flex items-center text-gray-500 dark:text-gray-400">
                                        <Phone className="w-4 h-4 mr-2" />
                                        <span>{branch.phone}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    ))
                )}
            </div>

            {/* Branch Form Modal */}
            {isModalOpen && (
                <BranchFormModal
                    branch={editingBranch}
                    onClose={() => setModalOpen(false)}
                    onSuccess={() => {
                        setModalOpen(false);
                        queryClient.invalidateQueries(['branches']);
                    }}
                />
            )}
        </div>
    );
}

function BranchFormModal({ branch, onClose, onSuccess }) {
    const [formData, setFormData] = useState({
        name: branch?.name || '',
        address: branch?.address || '',
        phone: branch?.phone || '',
        is_active: branch?.is_active ?? true,
    });
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setIsSubmitting(true);
        setError('');

        try {
            if (branch) {
                await api.put(`/branches/${branch.id}`, formData);
            } else {
                await api.post('/branches', formData);
            }
            onSuccess();
        } catch (err) {
            setError(err.response?.data?.message || 'Failed to save branch');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6">
                <div className="flex justify-between items-center mb-4">
                    <h2 className="text-lg font-bold text-gray-900 dark:text-white">{branch ? 'Edit Branch' : 'Add Branch'}</h2>
                    <button onClick={onClose} className="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                        <X className="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                {error && <div className="bg-red-50 dark:bg-red-900/20 text-red-600 p-3 rounded mb-4 text-sm">{error}</div>}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Branch Name</label>
                        <input
                            type="text"
                            required
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            value={formData.name}
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                        <textarea
                            rows={2}
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            value={formData.address}
                            onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                        />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                        <input
                            type="text"
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            value={formData.phone}
                            onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                        />
                    </div>

                    <div className="flex items-center gap-3">
                        <input
                            type="checkbox"
                            id="is_active"
                            checked={formData.is_active}
                            onChange={(e) => setFormData({ ...formData, is_active: e.target.checked })}
                            className="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                        />
                        <label htmlFor="is_active" className="text-sm text-gray-700 dark:text-gray-300">Active</label>
                    </div>

                    <div className="flex justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                        <button type="button" onClick={onClose} className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300">
                            Cancel
                        </button>
                        <button
                            type="submit"
                            disabled={isSubmitting}
                            className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50"
                        >
                            {isSubmitting ? 'Saving...' : 'Save Branch'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
