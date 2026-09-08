import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

export default function AdminDashboardPage() {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Dashboard</CardTitle>
        <CardDescription>
          Số đơn và doanh thu ngày/tháng sẽ có ở change sau.
        </CardDescription>
      </CardHeader>
      <CardContent className="text-sm text-muted-foreground">
        Coming soon
      </CardContent>
    </Card>
  );
}
