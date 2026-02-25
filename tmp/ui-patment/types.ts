export enum UserRole {
  ADMIN = 'ADMIN',
  MERCHANT = 'MERCHANT',
}

export enum PaymentStatus {
  SUCCESS = 'SUCCESS',
  PENDING = 'PENDING',
  FAILED = 'FAILED',
  REFUNDED = 'REFUNDED',
}

export enum WithdrawalStatus {
  PENDING = 'PENDING',
  APPROVED = 'APPROVED',
  REJECTED = 'REJECTED',
}

export interface User {
  id: string;
  name: string;
  email: string;
  role: UserRole;
  kycVerified: boolean;
  banned: boolean;
  balance: number;
  businessName?: string;
}

export interface Payment {
  id: string;
  amount: number;
  currency: string;
  status: PaymentStatus;
  customerEmail: string;
  merchantName: string;
  method: string; // 'card', 'paypal', etc.
  createdAt: string;
  fees: number;
  net: number;
}

export interface Withdrawal {
  id: string;
  amount: number;
  method: string;
  status: WithdrawalStatus;
  requestedAt: string;
  merchantName: string;
  accountDetails: string;
}

export interface PaymentLink {
  id: string;
  title: string;
  amount: number | null; // null means customer decides
  currency: string;
  url: string;
  active: boolean;
  views: number;
  sales: number;
}

export interface GatewayConfig {
  id: string;
  name: string;
  enabled: boolean;
  mode: 'test' | 'live';
}

export interface NavItem {
  label: string;
  path: string;
  icon: any;
}