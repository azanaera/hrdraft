import { ApiClient, cookieTokenStorage } from '@hris/api-client';

export const api = new ApiClient({
  baseUrl: '/api',
  tokenStorage: cookieTokenStorage,
  mode: 'cookie',
});
