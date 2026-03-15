import React, { useEffect, useState } from 'react';
import { UserRole } from './types';
import { Sidebar } from './components/Sidebar';
import { Menu, X } from 'lucide-react';
import { Login } from './pages/Login';
import { AdminDashboard } from './pages/admin/AdminDashboard';
import { AdminUsers } from './pages/admin/AdminUsers';
import { AdminSettings } from './pages/admin/AdminSettings';
import { MerchantDashboard } from './pages/merchant/MerchantDashboard';
import { MerchantLinks } from './pages/merchant/MerchantLinks';
import { MerchantDevelopers } from './pages/merchant/MerchantDevelopers';
import { SharedPayments } from './pages/SharedPayments';
import { SharedWithdrawals } from './pages/SharedWithdrawals';

const App: React.FC = () => {
  const [isAuthenticated, setIsAuthenticated] = useState(false);
  const [userRole, setUserRole] = useState<UserRole | null>(null);
  const [currentPath, setCurrentPath] = useState('');
  const [isMobileSidebarOpen, setIsMobileSidebarOpen] = useState(false);

  const handleLogin = (role: UserRole) => {
    setIsAuthenticated(true);
    setUserRole(role);
    setCurrentPath(role === UserRole.ADMIN ? '/admin/dashboard' : '/merchant/dashboard');
    setIsMobileSidebarOpen(false);
  };

  const handleLogout = () => {
    setIsAuthenticated(false);
    setUserRole(null);
    setCurrentPath('');
    setIsMobileSidebarOpen(false);
  };

  const handleNavigate = (path: string) => {
    setCurrentPath(path);
    setIsMobileSidebarOpen(false);
  };

  useEffect(() => {
    if (!isMobileSidebarOpen) return;

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setIsMobileSidebarOpen(false);
      }
    };

    window.addEventListener('keydown', onKeyDown);
    return () => window.removeEventListener('keydown', onKeyDown);
  }, [isMobileSidebarOpen]);

  const renderContent = () => {
    if (!isAuthenticated) return <Login onLogin={handleLogin} />;

    switch (currentPath) {
      // Admin Routes
      case '/admin/dashboard': return <AdminDashboard />;
      case '/admin/users': return <AdminUsers />;
      case '/admin/payments': return <SharedPayments role={UserRole.ADMIN} />;
      case '/admin/withdrawals': return <SharedWithdrawals role={UserRole.ADMIN} />;
      case '/admin/settings': return <AdminSettings />;
      
      // Merchant Routes
      case '/merchant/dashboard': return <MerchantDashboard />;
      case '/merchant/payments': return <SharedPayments role={UserRole.MERCHANT} />;
      case '/merchant/links': return <MerchantLinks />;
      case '/merchant/withdrawals': return <SharedWithdrawals role={UserRole.MERCHANT} />;
      case '/merchant/developers': return <MerchantDevelopers />;
      
      // Default Fallback
      default: return (
        <div className="flex flex-col items-center justify-center h-full text-gray-500">
          <h2 className="text-xl font-semibold">Page Under Construction</h2>
          <p className="mt-2">Path: {currentPath}</p>
        </div>
      );
    }
  };

  if (!isAuthenticated) {
    return <Login onLogin={handleLogin} />;
  }

  return (
    <div className="flex h-screen overflow-hidden bg-slate-50 font-sans">
      {/* Sidebar */}
      <Sidebar 
        role={userRole!} 
        currentPath={currentPath} 
        onNavigate={handleNavigate} 
        onLogout={handleLogout}
        isMobileOpen={isMobileSidebarOpen}
        onCloseMobile={() => setIsMobileSidebarOpen(false)}
      />

      {/* Main Content Area */}
      <div className="flex-1 flex flex-col overflow-hidden">
        <header className="bg-white shadow-sm h-16 flex items-center justify-between px-4 md:px-6 z-10">
          {/* Header content like Search or Profile Dropdown could go here */}
          <div className="flex items-center">
            <button
              type="button"
              onClick={() => setIsMobileSidebarOpen((prev) => !prev)}
              className="mr-3 inline-flex h-9 w-9 items-center justify-center rounded-md border border-slate-200 text-slate-700 md:hidden"
              aria-label="Toggle menu"
            >
              {isMobileSidebarOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
            </button>
            <h2 className="text-gray-500 text-sm font-medium">Welcome back, {userRole === UserRole.ADMIN ? 'Administrator' : 'Merchant'}</h2>
          </div>
          <div className="flex items-center space-x-4">
             <div className="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
               {userRole === UserRole.ADMIN ? 'A' : 'M'}
             </div>
          </div>
        </header>

        <main className="flex-1 overflow-y-auto p-4 md:p-6">
          <div className="max-w-7xl mx-auto">
             {renderContent()}
          </div>
        </main>
      </div>
    </div>
  );
};

export default App;
