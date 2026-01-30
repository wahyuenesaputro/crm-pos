import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Save, Loader2, Store, Palette, Bell, Shield, Moon, Sun, Check, X, Lock } from 'lucide-react';
import api from '../../lib/api';

// Theme context helper
const getTheme = () => localStorage.getItem('theme') || 'light';
const setTheme = (theme) => {
    localStorage.setItem('theme', theme);
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
};

// Notification settings helper
const getNotificationSettings = () => {
    try {
        return JSON.parse(localStorage.getItem('notifications') || '{"lowStock":true,"dailyReport":false}');
    } catch {
        return { lowStock: true, dailyReport: false };
    }
};

export default function SettingsPage() {
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    const [activeTab, setActiveTab] = useState('store');
    const [saving, setSaving] = useState(false);
    const [saveMessage, setSaveMessage] = useState('');
    
    // State untuk Modal Ganti Password
    const [isPasswordModalOpen, setIsPasswordModalOpen] = useState(false);

    // Dark mode state
    const [isDarkMode, setIsDarkMode] = useState(getTheme() === 'dark');

    // Notification settings
    const [notifications, setNotifications] = useState(getNotificationSettings());

    // Store settings
    const [storeSettings, setStoreSettings] = useState(() => {
        try {
            return JSON.parse(localStorage.getItem('storeSettings') || 'null') || {
                name: 'Kopi Kuy Coffee Shop',
                address: 'Jl. Sudirman No. 123, Jakarta',
                phone: '021-5551234',
                email: 'hq@kopikuy.com',
                tax_rate: 11,
                currency: 'IDR',
            };
        } catch {
            return {
                name: 'Kopi Kuy Coffee Shop',
                address: 'Jl. Sudirman No. 123, Jakarta',
                phone: '021-5551234',
                email: 'hq@kopikuy.com',
                tax_rate: 11,
                currency: 'IDR',
            };
        }
    });

    // Apply theme on mount
    useEffect(() => {
        setTheme(isDarkMode ? 'dark' : 'light');
    }, []);

    const handleDarkModeToggle = () => {
        const newMode = !isDarkMode;
        setIsDarkMode(newMode);
        setTheme(newMode ? 'dark' : 'light');
    };

    const handleNotificationToggle = (key) => {
        const newSettings = { ...notifications, [key]: !notifications[key] };
        setNotifications(newSettings);
        localStorage.setItem('notifications', JSON.stringify(newSettings));
    };

    const handleSave = async () => {
        setSaving(true);
        setSaveMessage('');
        try {
            localStorage.setItem('storeSettings', JSON.stringify(storeSettings));
            // await api.put('/settings/store', storeSettings); // Uncomment jika API store sudah ada
            setSaveMessage('Settings saved successfully!');
            setTimeout(() => setSaveMessage(''), 3000);
        } catch (err) {
            setSaveMessage('Failed to save settings');
        } finally {
            setSaving(false);
        }
    };

    const tabs = [
        { id: 'store', label: 'Store Info', icon: Store },
        { id: 'appearance', label: 'Appearance', icon: Palette },
        { id: 'notifications', label: 'Notifications', icon: Bell },
        { id: 'security', label: 'Security', icon: Shield },
    ];

    const Toggle = ({ enabled, onToggle }) => (
        <button
            onClick={onToggle}
            className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${enabled ? 'bg-primary' : 'bg-gray-300'}`}
        >
            <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${enabled ? 'translate-x-6' : 'translate-x-1'}`} />
        </button>
    );

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
                <p className="text-gray-500 dark:text-gray-400">Manage your store configuration</p>
            </div>

            <div className="flex flex-col lg:flex-row gap-6">
                {/* Sidebar Tabs */}
                <div className="lg:w-64 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-2">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`w-full flex items-center px-4 py-3 text-left rounded-lg transition-colors ${activeTab === tab.id
                                    ? 'bg-primary/10 text-primary'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                                }`}
                        >
                            <tab.icon className="w-5 h-5 mr-3" />
                            {tab.label}
                        </button>
                    ))}
                </div>

                {/* Content */}
                <div className="flex-1 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                    {activeTab === 'store' && (
                        <div className="space-y-6">
                            <h2 className="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 text-gray-900 dark:text-white">Store Information</h2>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Store Name</label>
                                    <input
                                        type="text"
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        value={storeSettings.name}
                                        onChange={(e) => setStoreSettings({ ...storeSettings, name: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                    <input
                                        type="email"
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        value={storeSettings.email}
                                        onChange={(e) => setStoreSettings({ ...storeSettings, email: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                                    <input
                                        type="text"
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        value={storeSettings.phone}
                                        onChange={(e) => setStoreSettings({ ...storeSettings, phone: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Currency</label>
                                    <select
                                        className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                        value={storeSettings.currency}
                                        onChange={(e) => setStoreSettings({ ...storeSettings, currency: e.target.value })}
                                    >
                                        <option value="IDR">IDR - Indonesian Rupiah</option>
                                        <option value="USD">USD - US Dollar</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Address</label>
                                <textarea
                                    rows={2}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    value={storeSettings.address}
                                    onChange={(e) => setStoreSettings({ ...storeSettings, address: e.target.value })}
                                />
                            </div>
                            <div className="w-48">
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tax Rate (%)</label>
                                <input
                                    type="number"
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    value={storeSettings.tax_rate}
                                    onChange={(e) => setStoreSettings({ ...storeSettings, tax_rate: e.target.value })}
                                />
                            </div>
                        </div>
                    )}

                    {activeTab === 'appearance' && (
                        <div className="space-y-6">
                            <h2 className="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 text-gray-900 dark:text-white">Appearance</h2>
                            <div className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div className="flex items-center">
                                    {isDarkMode ? <Moon className="w-5 h-5 mr-3 text-indigo-500" /> : <Sun className="w-5 h-5 mr-3 text-yellow-500" />}
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">Dark Mode</p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">{isDarkMode ? 'Currently using dark theme' : 'Switch to dark theme'}</p>
                                    </div>
                                </div>
                                <Toggle enabled={isDarkMode} onToggle={handleDarkModeToggle} />
                            </div>
                            <div className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <p className="font-medium text-gray-900 dark:text-white mb-3">Color Theme</p>
                                <div className="flex gap-3">
                                    {['#7c3aed', '#10b981', '#f59e0b', '#ef4444', '#3b82f6'].map((color) => (
                                        <button
                                            key={color}
                                            className="w-8 h-8 rounded-full border-2 border-white shadow-sm hover:scale-110 transition-transform"
                                            style={{ backgroundColor: color }}
                                            title={color}
                                        />
                                    ))}
                                </div>
                                <p className="text-xs text-gray-400 mt-2">Theme colors coming in future update</p>
                            </div>
                        </div>
                    )}

                    {activeTab === 'notifications' && (
                        <div className="space-y-6">
                            <h2 className="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 text-gray-900 dark:text-white">Notifications</h2>
                            <div className="space-y-4">
                                <div className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">Low Stock Alerts</p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Get notified when stock is low</p>
                                    </div>
                                    <Toggle enabled={notifications.lowStock} onToggle={() => handleNotificationToggle('lowStock')} />
                                </div>
                                <div className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <p className="font-medium text-gray-900 dark:text-white">Daily Sales Report</p>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Receive daily summary via email</p>
                                    </div>
                                    <Toggle enabled={notifications.dailyReport} onToggle={() => handleNotificationToggle('dailyReport')} />
                                </div>
                            </div>
                        </div>
                    )}

                    {activeTab === 'security' && (
                        <div className="space-y-6">
                            <h2 className="text-lg font-bold border-b border-gray-100 dark:border-gray-700 pb-2 text-gray-900 dark:text-white">Security</h2>
                            <div className="space-y-4">
                                <div className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <p className="font-medium mb-3 text-gray-900 dark:text-white">Current Session</p>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <span className="text-gray-500 dark:text-gray-400">Username</span>
                                            <span className="font-medium text-gray-900 dark:text-white">{user.username}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span className="text-gray-500 dark:text-gray-400">Role</span>
                                            <span className="font-medium text-gray-900 dark:text-white capitalize">{user.roles?.join(', ')}</span>
                                        </div>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <button 
                                        onClick={() => setIsPasswordModalOpen(true)}
                                        className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm text-gray-700 dark:text-gray-300 flex items-center gap-2"
                                    >
                                        <Lock className="w-4 h-4" />
                                        Change Password
                                    </button>
                                    <button className="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 text-sm dark:bg-red-900/10 dark:border-red-900">
                                        Logout All Devices
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Save Button for Store Settings */}
                    {activeTab === 'store' && (
                        <div className="mt-8 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center gap-4">
                            <button
                                onClick={handleSave}
                                disabled={saving}
                                className="flex items-center px-6 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50"
                            >
                                {saving ? <Loader2 className="w-4 h-4 mr-2 animate-spin" /> : <Save className="w-4 h-4 mr-2" />}
                                Save Changes
                            </button>
                            {saveMessage && (
                                <span className={`text-sm flex items-center ${saveMessage.includes('Failed') ? 'text-red-600' : 'text-green-600'}`}>
                                    {!saveMessage.includes('Failed') && <Check className="w-4 h-4 mr-1" />}
                                    {saveMessage}
                                </span>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Modal Ganti Password */}
            {isPasswordModalOpen && (
                <ChangePasswordModal 
                    onClose={() => setIsPasswordModalOpen(false)} 
                />
            )}
        </div>
    );
}

// Komponen Modal Terpisah (Tinggal copy paste ini di dalam file yang sama)
function ChangePasswordModal({ onClose }) {
    const [formData, setFormData] = useState({
        current_password: '',
        new_password: '',
        confirm_password: ''
    });
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState({ type: '', text: '' });

    const handleSubmit = async (e) => {
        e.preventDefault();
        setMessage({ type: '', text: '' });

        if (formData.new_password !== formData.confirm_password) {
            setMessage({ type: 'error', text: 'New passwords do not match' });
            return;
        }

        if (formData.new_password.length < 8) {
            setMessage({ type: 'error', text: 'Password must be at least 8 characters' });
            return;
        }

        setLoading(true);
        try {
            // Sesuai dengan route di AuthController kamu
            await api.post('/auth/change-password', formData);
            setMessage({ type: 'success', text: 'Password changed successfully!' });
            
            // Reset form dan tutup modal setelah sukses
            setTimeout(() => {
                onClose();
            }, 1500);
        } catch (err) {
            setMessage({ 
                type: 'error', 
                text: err.response?.data?.message || 'Failed to change password' 
            });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div className="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md">
                <div className="flex justify-between items-center p-6 border-b border-gray-100 dark:border-gray-700">
                    <h3 className="text-lg font-bold text-gray-900 dark:text-white">Change Password</h3>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <X className="w-5 h-5" />
                    </button>
                </div>
                
                <form onSubmit={handleSubmit} className="p-6 space-y-4">
                    {message.text && (
                        <div className={`p-3 rounded-lg text-sm ${message.type === 'success' ? 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400'}`}>
                            {message.text}
                        </div>
                    )}

                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Current Password</label>
                        <input 
                            type="password"
                            required
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            value={formData.current_password}
                            onChange={(e) => setFormData({...formData, current_password: e.target.value})}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">New Password</label>
                        <input 
                            type="password"
                            required
                            minLength={8}
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            value={formData.new_password}
                            onChange={(e) => setFormData({...formData, new_password: e.target.value})}
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Confirm New Password</label>
                        <input 
                            type="password"
                            required
                            minLength={8}
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-primary focus:border-primary bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            value={formData.confirm_password}
                            onChange={(e) => setFormData({...formData, confirm_password: e.target.value})}
                        />
                    </div>

                    <div className="flex justify-end gap-3 pt-2">
                        <button 
                            type="button" 
                            onClick={onClose}
                            className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            disabled={loading}
                            className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 disabled:opacity-50 flex items-center"
                        >
                            {loading && <Loader2 className="w-4 h-4 mr-2 animate-spin" />}
                            Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}