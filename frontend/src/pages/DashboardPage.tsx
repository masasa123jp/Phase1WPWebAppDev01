import { useQuery } from '@tanstack/react-query';
import { apiClient } from '../api/client';

export default function DashboardPage() {
  const { data } = useQuery({
    queryKey: ['me'],
    queryFn: () => apiClient.get('/me').then((r) => r.data),
  });

  return (
    <section>
      <h2>My Dashboard</h2>
      {data ? (
        <>
          <p>Name: {data.name}</p>
          <p>Email: {data.email}</p>
          <p>Roles: {data.roles.join(', ')}</p>
        </>
      ) : (
        <p>Loading…</p>
      )}
    </section>
  );
}
