import React from 'react';
import { PaymentStatus, WithdrawalStatus } from '../types';

interface StatusBadgeProps {
  status: string;
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ status }) => {
  let colorClass = "bg-gray-100 text-gray-800";

  switch (status) {
    case PaymentStatus.SUCCESS:
    case WithdrawalStatus.APPROVED:
      colorClass = "bg-green-100 text-green-800";
      break;
    case PaymentStatus.PENDING:
    case WithdrawalStatus.PENDING:
      colorClass = "bg-yellow-100 text-yellow-800";
      break;
    case PaymentStatus.FAILED:
    case PaymentStatus.REFUNDED:
    case WithdrawalStatus.REJECTED:
      colorClass = "bg-red-100 text-red-800";
      break;
    case 'ACTIVE':
      colorClass = "bg-blue-100 text-blue-800";
      break;
    case 'BANNED':
      colorClass = "bg-red-900 text-white";
      break;
  }

  return (
    <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${colorClass}`}>
      {status}
    </span>
  );
};