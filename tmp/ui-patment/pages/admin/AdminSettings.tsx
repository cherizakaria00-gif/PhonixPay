import React, { useState } from 'react';
import { MOCK_GATEWAYS } from '../../constants';
import { Button } from '../../components/Button';
import { Save, RefreshCw } from 'lucide-react';

export const AdminSettings: React.FC = () => {
  const [activeTab, setActiveTab] = useState('general');

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">System Settings</h1>

      <div className="border-b border-gray-200">
        <nav className="-mb-px flex space-x-8">
          {['general', 'gateways', 'notifications', 'seo'].map((tab) => (
            <button
              key={tab}
              onClick={() => setActiveTab(tab)}
              className={`${
                activeTab === tab
                  ? 'border-indigo-500 text-indigo-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm capitalize`}
            >
              {tab}
            </button>
          ))}
        </nav>
      </div>

      {activeTab === 'general' && (
        <div className="bg-white shadow rounded-lg p-6 space-y-6 max-w-2xl">
          <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <div className="sm:col-span-4">
              <label className="block text-sm font-medium text-gray-700">Site Title</label>
              <input type="text" defaultValue="PayFlow Gateway" className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
            </div>

            <div className="sm:col-span-3">
              <label className="block text-sm font-medium text-gray-700">Default Currency</label>
              <select className="mt-1 block w-full bg-white border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                <option>USD</option>
                <option>EUR</option>
                <option>GBP</option>
              </select>
            </div>

            <div className="sm:col-span-6">
              <h4 className="text-sm font-medium text-gray-900 mt-4 mb-2">Global Fees</h4>
              <div className="grid grid-cols-2 gap-4">
                 <div>
                    <label className="block text-xs text-gray-500">Fixed Charge ($)</label>
                    <input type="number" defaultValue="0.30" className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                 </div>
                 <div>
                    <label className="block text-xs text-gray-500">Percent Charge (%)</label>
                    <input type="number" defaultValue="2.9" className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" />
                 </div>
              </div>
            </div>
          </div>
          <div className="flex justify-end">
            <Button>
              <Save className="w-4 h-4 mr-2" /> Save Changes
            </Button>
          </div>
        </div>
      )}

      {activeTab === 'gateways' && (
        <div className="space-y-4">
          <p className="text-sm text-gray-500">Manage payment providers. Enable/Disable or configure keys.</p>
          <div className="bg-white shadow overflow-hidden sm:rounded-md">
            <ul className="divide-y divide-gray-200">
              {MOCK_GATEWAYS.map((gateway) => (
                <li key={gateway.id} className="px-4 py-4 sm:px-6 flex items-center justify-between hover:bg-gray-50">
                   <div className="flex items-center">
                     <div className={`h-2.5 w-2.5 rounded-full mr-3 ${gateway.enabled ? 'bg-green-400' : 'bg-gray-300'}`} />
                     <div>
                       <p className="text-sm font-medium text-indigo-600 truncate">{gateway.name}</p>
                       <p className="flex items-center text-sm text-gray-500">
                         Mode: <span className="uppercase ml-1 font-semibold">{gateway.mode}</span>
                       </p>
                     </div>
                   </div>
                   <div className="flex space-x-2">
                     <Button variant="secondary" size="sm">Configure</Button>
                     <Button variant={gateway.enabled ? 'danger' : 'primary'} size="sm">
                       {gateway.enabled ? 'Disable' : 'Enable'}
                     </Button>
                   </div>
                </li>
              ))}
            </ul>
          </div>
          <div className="flex justify-end mt-4">
            <Button variant="outline"><RefreshCw className="w-4 h-4 mr-2"/> Sync Providers</Button>
          </div>
        </div>
      )}
    </div>
  );
};