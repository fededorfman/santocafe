// ===========================
// Tipos base — Santo Café Backoffice
// ===========================

export type UserRole = 'admin' | 'ops_manager';

export interface User {
  id: number;
  email: string;
  name: string;
  role: UserRole;
  createdAt: string;
  updatedAt: string;
}

export interface Product {
  id: number;
  sku: string;
  name: string;
  description: string;
  unitPrice: number;
  stockQuantity: number;
  reorderPoint: number;
  createdAt: string;
  updatedAt: string;
}

export type OrderStatus = 'pending' | 'preparing' | 'ready' | 'dispatched' | 'delivered' | 'cancelled';

export interface Order {
  id: number;
  customerId: number;
  customerName: string;
  status: OrderStatus;
  total: number;
  items: OrderItem[];
  deliveryAddress: string;
  notes?: string;
  createdAt: string;
  updatedAt: string;
}

export interface OrderItem {
  productId: number;
  productName: string;
  quantity: number;
  unitPrice: number;
  subtotal: number;
}

export type DeliveryStatus = 'scheduled' | 'in_transit' | 'completed' | 'failed';

export interface Delivery {
  id: number;
  orderId: number;
  driverId: number;
  status: DeliveryStatus;
  scheduledDate: string;
  completedAt?: string;
  route: DeliveryStop[];
}

export interface DeliveryStop {
  orderId: number;
  address: string;
  lat: number;
  lng: number;
  completed: boolean;
}

export interface StockMovement {
  id: number;
  productId: number;
  type: 'in' | 'out' | 'adjustment';
  quantity: number;
  reason: string;
  createdAt: string;
}
