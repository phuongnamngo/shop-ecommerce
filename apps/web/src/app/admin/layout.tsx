import { AdminQueryProvider } from "@/components/admin/admin-query-provider";

export default function AdminRootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return <AdminQueryProvider>{children}</AdminQueryProvider>;
}
