import React from 'react';
import { redirect } from 'next/navigation';
import { getSessionUser } from '@/lib/auth';
import { AdminDashboardClient } from '@/components/AdminDashboardClient';

export const dynamic = 'force-dynamic';

export default async function AdminPage() {
  const user = await getSessionUser();
  if (!user) {
    redirect('/admin/login');
  }
  return <AdminDashboardClient />;
}
