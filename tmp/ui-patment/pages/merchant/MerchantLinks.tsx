import React from 'react';
import { MOCK_LINKS } from '../../constants';
import { Button } from '../../components/Button';
import { ExternalLink, Copy, Plus } from 'lucide-react';

export const MerchantLinks: React.FC = () => {
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">Payment Links</h1>
        <Button>
          <Plus className="w-4 h-4 mr-2" /> Create Link
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {MOCK_LINKS.map((link) => (
          <div key={link.id} className="bg-white overflow-hidden shadow rounded-lg divide-y divide-gray-200 border border-gray-100">
            <div className="px-4 py-5 sm:px-6">
              <div className="flex justify-between items-center">
                 <h3 className="text-lg font-medium text-gray-900 truncate">{link.title}</h3>
                 <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ${link.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                    {link.active ? 'Active' : 'Archived'}
                 </span>
              </div>
              <p className="mt-1 text-2xl font-semibold text-indigo-600">
                {link.amount ? `$${link.amount.toFixed(2)}` : 'Custom Amount'}
                <span className="text-sm font-normal text-gray-500 ml-1">{link.currency}</span>
              </p>
            </div>
            <div className="px-4 py-4 sm:px-6">
              <div className="flex justify-between text-sm text-gray-500 mb-2">
                <span>Views: {link.views}</span>
                <span>Sales: {link.sales}</span>
              </div>
              <div className="bg-gray-50 p-2 rounded flex justify-between items-center text-xs text-gray-500 break-all">
                <span className="truncate mr-2">{link.url}</span>
                <button className="text-indigo-600 hover:text-indigo-900">
                  <Copy className="w-4 h-4" />
                </button>
              </div>
            </div>
            <div className="px-4 py-4 sm:px-6 bg-gray-50 flex justify-end space-x-2">
               <Button variant="secondary" size="sm">Edit</Button>
               <Button variant="outline" size="sm">
                 <ExternalLink className="w-3 h-3" />
               </Button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};