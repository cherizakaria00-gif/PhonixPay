import React, { useState } from 'react';
import { Button } from '../../components/Button';
import { Copy, Eye, EyeOff, Download } from 'lucide-react';

export const MerchantDevelopers: React.FC = () => {
  const [showSecret, setShowSecret] = useState(false);

  return (
    <div className="space-y-6">
      <h1 className="text-2xl font-bold text-gray-900">Developers</h1>

      {/* API Keys Section */}
      <div className="bg-white shadow rounded-lg overflow-hidden">
        <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 className="text-lg leading-6 font-medium text-gray-900">API Credentials</h3>
          <p className="mt-1 max-w-2xl text-sm text-gray-500">Use these keys to authenticate your API requests.</p>
        </div>
        <div className="px-4 py-5 sm:p-6 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700">Public Key</label>
            <div className="mt-1 flex rounded-md shadow-sm">
              <input 
                type="text" 
                readOnly 
                value="pk_test_51MzQ2lE3X4yZ9k12345" 
                className="flex-1 block w-full rounded-none rounded-l-md border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500"
              />
              <button className="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 hover:bg-gray-100">
                <Copy className="h-4 w-4" />
              </button>
            </div>
          </div>
          
          <div>
            <label className="block text-sm font-medium text-gray-700">Secret Key</label>
            <div className="mt-1 flex rounded-md shadow-sm">
              <input 
                type={showSecret ? "text" : "password"}
                readOnly 
                value="sk_test_51MzQ2lE3X4yZ9k98765SECRET" 
                className="flex-1 block w-full rounded-none rounded-l-md border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500"
              />
              <button 
                onClick={() => setShowSecret(!showSecret)}
                className="inline-flex items-center px-3 border border-l-0 border-gray-300 bg-gray-50 text-gray-500 hover:bg-gray-100"
              >
                {showSecret ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
              </button>
              <button className="inline-flex items-center px-3 rounded-r-md border border-l-0 border-gray-300 bg-gray-50 text-gray-500 hover:bg-gray-100">
                <Copy className="h-4 w-4" />
              </button>
            </div>
            <p className="mt-2 text-xs text-red-500">Keep your secret key safe. Do not share it in client-side code.</p>
          </div>
          
          <div className="pt-4 flex justify-end">
             <Button variant="secondary" size="sm">Roll Keys</Button>
          </div>
        </div>
      </div>

      {/* WooCommerce Plugin */}
      <div className="bg-white shadow rounded-lg overflow-hidden">
        <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 className="text-lg leading-6 font-medium text-gray-900">WooCommerce Plugin</h3>
          <p className="mt-1 max-w-2xl text-sm text-gray-500">Easily integrate PayFlow into your WordPress store.</p>
        </div>
        <div className="px-4 py-5 sm:p-6">
           <div className="flex items-center justify-between">
              <div>
                  <h4 className="text-sm font-bold text-gray-900">PayFlow for WooCommerce v1.2.0</h4>
                  <p className="text-sm text-gray-500 mt-1">
                      1. Download the ZIP file.<br/>
                      2. Go to WordPress Admin &gt; Plugins &gt; Add New &gt; Upload.<br/>
                      3. Activate and enter your API Keys.
                  </p>
              </div>
              <Button>
                 <Download className="w-4 h-4 mr-2" /> Download Plugin
              </Button>
           </div>
        </div>
      </div>
      
       {/* Webhooks */}
       <div className="bg-white shadow rounded-lg overflow-hidden">
        <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
          <h3 className="text-lg leading-6 font-medium text-gray-900">Webhooks</h3>
        </div>
        <div className="px-4 py-5 sm:p-6">
            <div className="rounded-md bg-yellow-50 p-4 mb-4">
              <div className="flex">
                <div className="ml-3">
                  <h3 className="text-sm font-medium text-yellow-800">No endpoints configured</h3>
                  <div className="mt-2 text-sm text-yellow-700">
                    <p>You haven't set up any webhook endpoints to receive payment events.</p>
                  </div>
                </div>
              </div>
            </div>
            <Button variant="secondary">Add Endpoint</Button>
        </div>
      </div>
    </div>
  );
};