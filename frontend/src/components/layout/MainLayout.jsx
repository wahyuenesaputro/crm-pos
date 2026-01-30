import { useState, useEffect } from 'react';
import { Outlet, NavLink, useNavigate, useLocation } from 'react-router-dom';
import { LayoutDashboard, ShoppingCart, Package, Users, FileBarChart, Settings, LogOut, Menu, ChevronLeft, ChevronRight, Coffee, Building2 } from 'lucide-react';
import { cn } from '../../lib/utils';
import api from '../../lib/api';

export default function MainLayout() {
    const navigate = useNavigate();
    const location = useLocation();
    const [isCollapsed, setIsCollapsed] = useState(false);
    const [isMobileOpen, setMobileOpen] = useState(false);
    const user = JSON.parse(localStorage.getItem('user') || '{}');

    // Auto-collapse on mobile
    useEffect(() => {
        const handleResize = () => {
            if (window.innerWidth < 1024) {
                setIsCollapsed(true);
            }
        };
        handleResize();
        window.addEventListener('resize', handleResize);
        return () => window.removeEventListener('resize', handleResize);
    }, []);

    // Close mobile sidebar on route change
    useEffect(() => {
        setMobileOpen(false);
    }, [location.pathname]);

    const handleLogout = async () => {
        try {
            await api.post('/auth/logout');
        } catch (e) {
            console.error(e);
        } finally {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            navigate('/login');
        }
    };

    const menuItems = [
        { icon: LayoutDashboard, label: 'Dashboard', path: '/dashboard' },
        { icon: ShoppingCart, label: 'POS Cashier', path: '/pos' },
        { icon: Package, label: 'Products', path: '/products' },
        { icon: Users, label: 'Customers', path: '/customers' },
        { icon: FileBarChart, label: 'Reports', path: '/reports' },
        { icon: Building2, label: 'Branches', path: '/branches' },
        { icon: Settings, label: 'Settings', path: '/settings' },
    ];

    // POS route gets minimal layout
    const isPosRoute = location.pathname === '/pos';

    return (
        <div className="flex h-screen bg-gray-100 overflow-hidden">
            {/* Desktop Sidebar */}
            <aside
                className={cn(
                    "hidden lg:flex flex-col bg-white border-r border-gray-200 transition-all duration-300 ease-in-out",
                    isCollapsed ? "w-16" : "w-64"
                )}
            >
                <div className={cn("h-16 flex items-center border-b border-gray-100", isCollapsed ? "justify-center px-2" : "px-6")}>
                    <Coffee className="w-6 h-6 text-primary flex-shrink-0" />
                    {!isCollapsed && <span className="font-bold text-lg text-gray-900 ml-2 truncate">Kopi Kuy</span>}
                </div>

                <div className="flex-1 overflow-y-auto py-4 px-2 space-y-1">
                    {menuItems.map((item) => (
                        <NavLink
                            key={item.path}
                            to={item.path}
                            title={item.label}
                            className={({ isActive }) =>
                                cn(
                                    "flex items-center rounded-lg transition-colors group",
                                    isCollapsed ? "justify-center p-3" : "px-3 py-2",
                                    isActive
                                        ? "bg-primary/10 text-primary"
                                        : "text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                                )
                            }
                        >
                            <item.icon className="w-5 h-5 flex-shrink-0" />
                            {!isCollapsed && <span className="ml-3 text-sm font-medium truncate">{item.label}</span>}
                        </NavLink>
                    ))}
                </div>

                {/* User Info */}
                <div className={cn("border-t border-gray-100", isCollapsed ? "p-2" : "p-4")}>
                    {!isCollapsed && (
                        <div className="flex items-center mb-4 px-2">
                            <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold flex-shrink-0">
                                {user.full_name?.charAt(0) || 'U'}
                            </div>
                            <div className="flex-1 min-w-0 ml-3">
                                <p className="text-sm font-medium text-gray-900 truncate">{user.full_name}</p>
                                <p className="text-xs text-gray-500 truncate capitalize">{user.roles?.[0]}</p>
                            </div>
                        </div>
                    )}
                    <button
                        onClick={handleLogout}
                        title="Logout"
                        className={cn(
                            "flex items-center text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors",
                            isCollapsed ? "justify-center p-3 w-full" : "px-3 py-2 w-full"
                        )}
                    >
                        <LogOut className="w-5 h-5 flex-shrink-0" />
                        {!isCollapsed && <span className="ml-3">Logout</span>}
                    </button>
                </div>

                {/* Collapse Toggle */}
                <button
                    onClick={() => setIsCollapsed(!isCollapsed)}
                    className="h-10 border-t border-gray-100 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-50 transition-colors"
                    title={isCollapsed ? "Expand sidebar" : "Collapse sidebar"}
                >
                    {isCollapsed ? <ChevronRight className="w-4 h-4" /> : <ChevronLeft className="w-4 h-4" />}
                </button>
            </aside>

            {/* Mobile Sidebar Overlay */}
            {isMobileOpen && (
                <div
                    className="fixed inset-0 bg-gray-600/50 z-40 lg:hidden"
                    onClick={() => setMobileOpen(false)}
                />
            )}

            {/* Mobile Sidebar */}
            <aside
                className={cn(
                    "fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-200 lg:hidden",
                    isMobileOpen ? "translate-x-0" : "-translate-x-full"
                )}
            >
                <div className="h-16 flex items-center px-6 border-b border-gray-100">
                    <Coffee className="w-6 h-6 text-primary" />
                    <span className="font-bold text-lg text-gray-900 ml-2">Kopi Kuy</span>
                </div>

                <div className="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                    {menuItems.map((item) => (
                        <NavLink
                            key={item.path}
                            to={item.path}
                            className={({ isActive }) =>
                                cn(
                                    "flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors",
                                    isActive
                                        ? "bg-primary/10 text-primary"
                                        : "text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                                )
                            }
                        >
                            <item.icon className="w-5 h-5 mr-3" />
                            {item.label}
                        </NavLink>
                    ))}
                </div>

                <div className="p-4 border-t border-gray-100">
                    <div className="flex items-center mb-4 px-2">
                        <div className="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold">
                            {user.full_name?.charAt(0) || 'U'}
                        </div>
                        <div className="flex-1 min-w-0 ml-3">
                            <p className="text-sm font-medium text-gray-900 truncate">{user.full_name}</p>
                            <p className="text-xs text-gray-500 truncate capitalize">{user.roles?.[0]}</p>
                        </div>
                    </div>
                    <button
                        onClick={handleLogout}
                        className="w-full flex items-center px-3 py-2 text-sm font-medium text-red-600 rounded-lg hover:bg-red-50 transition-colors"
                    >
                        <LogOut className="w-5 h-5 mr-3" />
                        Logout
                    </button>
                </div>
            </aside>

            {/* Main Content */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                {/* Mobile Header */}
                <header className="lg:hidden h-14 bg-white border-b border-gray-200 flex items-center px-4 justify-between flex-shrink-0">
                    <div className="flex items-center">
                        <button
                            className="p-2 -ml-2 rounded-lg hover:bg-gray-100"
                            onClick={() => setMobileOpen(!isMobileOpen)}
                        >
                            <Menu className="w-5 h-5" />
                        </button>
                        <span className="font-bold text-gray-900 ml-2">Kopi Kuy</span>
                    </div>
                </header>

                <main className={cn("flex-1 overflow-y-auto", isPosRoute ? "p-0" : "p-4 md:p-6")}>
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
