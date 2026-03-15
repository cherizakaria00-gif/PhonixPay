import React, { useEffect, useState } from 'react';
import { AreaChart, Area, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts';
import { DollarSign, ArrowUpRight, Link, CreditCard } from 'lucide-react';

const data = [
  { name: 'Mon', sales: 400 },
  { name: 'Tue', sales: 300 },
  { name: 'Wed', sales: 550 },
  { name: 'Thu', sales: 450 },
  { name: 'Fri', sales: 600 },
  { name: 'Sat', sales: 800 },
  { name: 'Sun', sales: 750 },
];

const MOBILE_BREAKPOINT = 640;

const useIsMobile = () => {
  const [isMobile, setIsMobile] = useState(() => window.innerWidth < MOBILE_BREAKPOINT);

  useEffect(() => {
    const onResize = () => setIsMobile(window.innerWidth < MOBILE_BREAKPOINT);
    window.addEventListener('resize', onResize);
    return () => window.removeEventListener('resize', onResize);
  }, []);

  return isMobile;
};

const StatCard = ({ title, value, icon: Icon, color }: any) => (
  <div className="bg-white overflow-hidden shadow rounded-lg p-4 sm:p-5">
    <div className="flex items-center gap-3 sm:gap-0">
      <div className={`flex-shrink-0 rounded-md p-3 ${color}`}>
        <Icon className="h-5 w-5 sm:h-6 sm:w-6 text-white" />
      </div>
      <div className="sm:ml-5 w-0 flex-1">
        <dl>
          <dt className="text-sm font-medium text-gray-500 truncate">{title}</dt>
          <dd>
            <div className="text-base sm:text-lg font-medium text-gray-900">{value}</div>
          </dd>
        </dl>
      </div>
    </div>
  </div>
);

export const MerchantDashboard: React.FC = () => {
  const isMobile = useIsMobile();

  return (
    <div className="space-y-4 sm:space-y-6">
      <div className="flex flex-col gap-1 sm:flex-row sm:justify-between sm:items-center">
        <h1 className="text-xl sm:text-2xl font-bold text-gray-900">Dashboard</h1>
        <span className="text-sm text-gray-500">Last 7 Days</span>
      </div>
      
      <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <StatCard title="Available Balance" value="$12,450.50" icon={DollarSign} color="bg-emerald-500" />
        <StatCard title="Total Volume" value="$45,231.89" icon={CreditCard} color="bg-indigo-500" />
        <StatCard title="Pending Withdrawals" value="$1,200.00" icon={ArrowUpRight} color="bg-orange-500" />
        <StatCard title="Active Links" value="8" icon={Link} color="bg-blue-500" />
      </div>

      <div className="bg-white shadow rounded-lg p-4 sm:p-6">
        <h3 className="text-base sm:text-lg leading-6 font-medium text-gray-900 mb-3 sm:mb-4">Sales Performance</h3>
        <div className="h-64 sm:h-72 -ml-2 sm:ml-0">
          <ResponsiveContainer width="100%" height="100%">
            <AreaChart data={data} margin={{ top: 10, right: isMobile ? 8 : 16, left: isMobile ? -16 : 0, bottom: 0 }}>
              <defs>
                <linearGradient id="colorSales" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stopColor="#10b981" stopOpacity={0.8}/>
                  <stop offset="95%" stopColor="#10b981" stopOpacity={0}/>
                </linearGradient>
              </defs>
              <XAxis dataKey="name" tick={{ fontSize: isMobile ? 11 : 12 }} />
              <YAxis hide={isMobile} tick={{ fontSize: 12 }} />
              <CartesianGrid strokeDasharray="3 3" />
              <Tooltip formatter={(value) => [`$${value}`, 'Sales']} />
              <Area type="monotone" dataKey="sales" stroke="#10b981" fillOpacity={1} fill="url(#colorSales)" />
            </AreaChart>
          </ResponsiveContainer>
        </div>
      </div>
    </div>
  );
};
