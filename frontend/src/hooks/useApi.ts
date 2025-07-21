/******************************************************
 * useApi.ts – Axios + TanStack Query + Persistence
 *****************************************************/

import axios from 'axios';
import { QueryClient, QueryCache, useQuery, useMutation } from '@tanstack/react-query';
import { persistQueryClient } from '@tanstack/react-query-persist-client';
import { createSyncStoragePersister } from '@tanstack/query-sync-storage-persister';
import { useAuthStore } from '../store/useAuthStore';

/* ---------- 1. Axios クライアント -------------------------- */
export const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_BASE ?? '/wp-json/roro/v1',
  timeout: 8000,
});

apiClient.interceptors.request.use((config) => {
  const token = useAuthStore.getState().token;
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

/* ---------- 2. QueryClient + 永続化 ------------------------ */
export const queryClient = new QueryClient({
  queryCache: new QueryCache({
    onError: (error) => console.error('Query Error:', error),
  }),
});

const persister = createSyncStoragePersister({
  storage: window.indexedDB, // fallback → localStorage
  encode: (data) => data,
  decode: (data) => data,
});

persistQueryClient({
  queryClient,
  persister,
  maxAge: 1000 * 60 * 60 * 24, // 24h
});

/* ---------- 3. Hooks -------------------------------------- */

/** 近隣施設一覧 */
export const useFacilities = (lat: number, lng: number) =>
  useQuery({
    queryKey: ['facilities', lat, lng],
    queryFn: () =>
      apiClient.get('/facilities', { params: { lat, lng, radius: 3000 } }).then((r) => r.data),
    staleTime: 1000 * 60 * 5,
  });

/** レビュー投稿 */
export const useSubmitReview = () =>
  useMutation({
    mutationFn: (payload: { facility_id: number; rating: number; comment: string }) =>
      apiClient.post('/reviews', payload).then((r) => r.data),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['reviews'] }),
  });
