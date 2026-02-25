import { UserRole, PaymentStatus, WithdrawalStatus, User, Payment, Withdrawal, PaymentLink, GatewayConfig } from './types';

export const CURRENT_USER_ADMIN: User = {
  id: 'u_admin_001',
  name: 'Super Admin',
  email: 'admin@payflow.com',
  role: UserRole.ADMIN,
  kycVerified: true,
  banned: false,
  balance: 0,
};

export const CURRENT_USER_MERCHANT: User = {
  id: 'u_merch_001',
  name: 'John Doe',
  businessName: 'Doe Electronics',
  email: 'john@doe-electronics.com',
  role: UserRole.MERCHANT,
  kycVerified: true,
  banned: false,
  balance: 12450.50,
};

export const MOCK_PAYMENTS: Payment[] = [
  { id: 'pay_001', amount: 120.00, currency: 'USD', status: PaymentStatus.SUCCESS, customerEmail: 'alice@example.com', merchantName: 'Doe Electronics', method: 'Stripe', createdAt: '2023-10-25', fees: 3.50, net: 116.50 },
  { id: 'pay_002', amount: 45.00, currency: 'USD', status: PaymentStatus.PENDING, customerEmail: 'bob@example.com', merchantName: 'Doe Electronics', method: 'PayPal', createdAt: '2023-10-26', fees: 1.20, net: 43.80 },
  { id: 'pay_003', amount: 99.00, currency: 'USD', status: PaymentStatus.FAILED, customerEmail: 'charlie@example.com', merchantName: 'Smith Co', method: 'Card', createdAt: '2023-10-26', fees: 0, net: 0 },
  { id: 'pay_004', amount: 250.00, currency: 'USD', status: PaymentStatus.REFUNDED, customerEmail: 'dave@example.com', merchantName: 'Doe Electronics', method: 'Stripe', createdAt: '2023-10-24', fees: 5.00, net: 245.00 },
  { id: 'pay_005', amount: 15.00, currency: 'USD', status: PaymentStatus.SUCCESS, customerEmail: 'eve@example.com', merchantName: 'Smith Co', method: 'Google Pay', createdAt: '2023-10-27', fees: 0.50, net: 14.50 },
];

export const MOCK_WITHDRAWALS: Withdrawal[] = [
  { id: 'wd_001', amount: 5000.00, method: 'Bank Transfer', status: WithdrawalStatus.PENDING, requestedAt: '2023-10-27', merchantName: 'Doe Electronics', accountDetails: 'GB82WEST...' },
  { id: 'wd_002', amount: 1200.00, method: 'PayPal', status: WithdrawalStatus.APPROVED, requestedAt: '2023-10-20', merchantName: 'Smith Co', accountDetails: 'smith@co.com' },
];

export const MOCK_USERS: User[] = [
  CURRENT_USER_MERCHANT,
  { id: 'u_merch_002', name: 'Jane Smith', businessName: 'Smith Co', email: 'jane@smith.com', role: UserRole.MERCHANT, kycVerified: false, banned: false, balance: 450.00 },
  { id: 'u_merch_003', name: 'Bad Actor', businessName: 'Scam Ltd', email: 'scam@ltd.com', role: UserRole.MERCHANT, kycVerified: false, banned: true, balance: 0 },
];

export const MOCK_LINKS: PaymentLink[] = [
  { id: 'lnk_001', title: 'Consultation Fee', amount: 150, currency: 'USD', url: 'https://payflow.com/pl/Consultation', active: true, views: 45, sales: 12 },
  { id: 'lnk_002', title: 'Donation', amount: null, currency: 'USD', url: 'https://payflow.com/pl/donate', active: true, views: 120, sales: 5 },
];

export const MOCK_GATEWAYS: GatewayConfig[] = [
  { id: 'gw_stripe', name: 'Stripe', enabled: true, mode: 'live' },
  { id: 'gw_paypal', name: 'PayPal', enabled: true, mode: 'test' },
  { id: 'gw_airwallex', name: 'Airwallex', enabled: false, mode: 'test' },
];
