import { Plus_Jakarta_Sans } from "next/font/google";

import { AdminQueryProvider } from "@/components/admin/admin-query-provider";

const jakarta = Plus_Jakarta_Sans({
  subsets: ["latin"],
  variable: "--font-admin-sans",
});

export default function AdminRootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className={`${jakarta.variable} admin-app min-h-screen`}>
      <AdminQueryProvider>{children}</AdminQueryProvider>
    </div>
  );
}
