import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import type { AuthUser } from '@hris/shared-types';
import { api, secureTokenStorage } from './apiClient';

interface AuthContextValue {
  user: AuthUser | null;
  loading: boolean;
  login: (email: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    secureTokenStorage
      .getToken()
      .then((token) => (token ? api.me() : Promise.reject()))
      .then(setUser)
      .catch(() => setUser(null))
      .finally(() => setLoading(false));
  }, []);

  async function login(email: string, password: string) {
    const loggedIn = await api.mobileLogin(email, password, 'mobile-device');
    setUser(loggedIn);
  }

  async function logout() {
    await api.logout();
    setUser(null);
  }

  return <AuthContext.Provider value={{ user, loading, login, logout }}>{children}</AuthContext.Provider>;
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
