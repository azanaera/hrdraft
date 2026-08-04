import * as SecureStore from 'expo-secure-store';
import Constants from 'expo-constants';
import { ApiClient, type TokenStorage } from '@hris/api-client';

const TOKEN_KEY = 'hris_auth_token';

// Mobile can't share cookies with the API origin, so auth uses a bearer
// token persisted in the OS-level secure keychain rather than a session cookie.
const secureTokenStorage: TokenStorage = {
  getToken: () => SecureStore.getItemAsync(TOKEN_KEY),
  setToken: (token) =>
    token ? SecureStore.setItemAsync(TOKEN_KEY, token) : SecureStore.deleteItemAsync(TOKEN_KEY),
};

const baseUrl = (Constants.expoConfig?.extra?.apiBaseUrl as string | undefined) ?? 'http://localhost:8000/api';

export const api = new ApiClient({
  baseUrl,
  tokenStorage: secureTokenStorage,
  mode: 'bearer',
});

export { secureTokenStorage };
