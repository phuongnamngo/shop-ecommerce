import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

export default function AdminOrdersStubPage() {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Orders</CardTitle>
        <CardDescription>List / detail / status / shipment — sắp có.</CardDescription>
      </CardHeader>
      <CardContent className="text-sm text-muted-foreground">
        Coming soon
      </CardContent>
    </Card>
  );
}
