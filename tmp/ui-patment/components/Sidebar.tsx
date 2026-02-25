import React from 'react';
import { NavItem, UserRole } from '../types';
import { 
  LayoutDashboard, 
  Users, 
  CreditCard, 
  ArrowUpRight, 
  Settings, 
  LifeBuoy, 
  LogOut, 
  Link, 
  Code2 
} from 'lucide-react';

interface SidebarProps {
  role: UserRole;
  currentPath: string;
  onNavigate: (path: string) => void;
  onLogout: () => void;
}

export const Sidebar: React.FC<SidebarProps> = ({ role, currentPath, onNavigate, onLogout }) => {
  const adminNav: NavItem[] = [
    { label: 'Dashboard', path: '/admin/dashboard', icon: LayoutDashboard },
    { label: 'Users & KYC', path: '/admin/users', icon: Users },
    { label: 'Payments', path: '/admin/payments', icon: CreditCard },
    { label: 'Withdrawals', path: '/admin/withdrawals', icon: ArrowUpRight },
    { label: 'System Settings', path: '/admin/settings', icon: Settings },
    { label: 'Support', path: '/admin/support', icon: LifeBuoy },
  ];

  const merchantNav: NavItem[] = [
    { label: 'Dashboard', path: '/merchant/dashboard', icon: LayoutDashboard },
    { label: 'Payments', path: '/merchant/payments', icon: CreditCard },
    { label: 'Payment Links', path: '/merchant/links', icon: Link },
    { label: 'Withdrawals', path: '/merchant/withdrawals', icon: ArrowUpRight },
    { label: 'Developers', path: '/merchant/developers', icon: Code2 },
    { label: 'Settings', path: '/merchant/settings', icon: Settings },
  ];

  const items = role === UserRole.ADMIN ? adminNav : merchantNav;

  return (
    <div className="flex flex-col w-64 bg-slate-900 h-screen text-white sticky top-0">
      <div className="flex items-center justify-center h-16 border-b border-slate-700">
        <h1 className="text-xl font-bold bg-gradient-to-r from-indigo-400 to-cyan-400 bg-clip-text text-transparent">
          PayFlow
        </h1>
        <span className="ml-2 text-xs uppercase bg-slate-700 px-1 rounded text-slate-300">{role === UserRole.ADMIN ? 'Admin' : 'Merch'}</span>
      </div>
      
      <nav className="flex-1 px-2 py-4 space-y-1">
        {items.map((item) => {
          const isActive = currentPath === item.path;
          const Icon = item.icon;
          return (
            <button
              key={item.path}
              onClick={() => onNavigate(item.path)}
              className={`w-full group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors ${
                isActive 
                  ? 'bg-slate-800 text-white' 
                  : 'text-slate-300 hover:bg-slate-800 hover:text-white'
              }`}
            >
              <Icon className={`mr-3 h-5 w-5 flex-shrink-0 ${isActive ? 'text-indigo-400' : 'text-slate-400'}`} />
              {item.label}
            </button>
          );
        })}
      </nav>

      <div className="p-4 border-t border-slate-700">
        <button
          onClick={onLogout}
          className="w-full flex items-center px-2 py-2 text-sm font-medium text-red-400 rounded-md hover:bg-slate-800 transition-colors"
        >
          <LogOut className="mr-3 h-5 w-5" />
          Sign Out
        </button>
      </div>
    </div>
  );
};