import { StrictMode, Suspense } from 'react';
import { createBrowserRouter, RouterProvider } from 'react-router-dom';
import { AuthProvider } from '@/context/AuthProvider';
import Dashboard from '@/pages/dashboard/ReportsPage';
import NotFound from '@/pages/NotFound';

const router = createBrowserRouter([
  { path: '/', element: <Dashboard /> },
  { path: '*', element: <NotFound /> }
]);

export default function App() {
  return (
    <StrictMode>
      <AuthProvider>
        <Suspense fallback={<div className="p-8 text-center">Loading…</div>}>
          <RouterProvider router={router} />
        </Suspense>
      </AuthProvider>
    </StrictMode>
  );
}
