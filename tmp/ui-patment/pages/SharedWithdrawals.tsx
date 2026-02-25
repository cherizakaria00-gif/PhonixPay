import React from 'react';
import { MOCK_WITHDRAWALS } from '../constants';
import { StatusBadge } from '../components/StatusBadge';
import { UserRole, WithdrawalStatus } from '../types';
import { Button } from '../components/Button';
import { Check, X } from 'lucide-react';

interface SharedWithdrawalsProps {
  role: UserRole;
}

export const SharedWithdrawals: React.FC<SharedWithdrawalsProps> = ({ role }) => {
  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">{role === UserRole.ADMIN ? 'Manage Withdrawals' : 'My Withdrawals'}</h1>
        {role === UserRole.MERCHANT && (
            <Button>Request Withdrawal</Button>
        )}
      </div>

      <div className="bg-white shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
              {role === UserRole.ADMIN && <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Merchant</th>}
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
              {role === UserRole.ADMIN && <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>}
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {MOCK_WITHDRAWALS.map((wd) => (
              <tr key={wd.id}>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{wd.id}</td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">${wd.amount.toFixed(2)}</td>
                {role === UserRole.ADMIN && <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{wd.merchantName}</td>}
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div>{wd.method}</div>
                    <div className="text-xs text-gray-400 max-w-[100px] truncate">{wd.accountDetails}</div>
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <StatusBadge status={wd.status} />
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{wd.requestedAt}</td>
                {role === UserRole.ADMIN && (
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        {wd.status === WithdrawalStatus.PENDING && (
                            <div className="flex space-x-2">
                                <button className="text-green-600 hover:text-green-900 p-1 bg-green-50 rounded">
                                    <Check className="w-4 h-4"/>
                                </button>
                                <button className="text-red-600 hover:text-red-900 p-1 bg-red-50 rounded">
                                    <X className="w-4 h-4"/>
                                </button>
                            </div>
                        )}
                    </td>
                )}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};