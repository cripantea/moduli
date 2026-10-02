import { AxiosInstance } from 'axios'

declare global {
  interface Window {
    axios: AxiosInstance
  }
}

export interface Tenant {
  id: number
  name: string
  plan: 'trial' | 'pro' | 'enterprise'
  is_active: boolean
}

export interface User {
  id: number
  name: string
  email: string
  role: 'superadmin' | 'admin' | 'user'
  tenant_id: number | null
  tenant?: Tenant
  is_active: boolean
  email_verified_at?: string
}

export type PageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  auth: {
    user: User
  }
  flash: {
    success?: string
    error?: string
  }
}
