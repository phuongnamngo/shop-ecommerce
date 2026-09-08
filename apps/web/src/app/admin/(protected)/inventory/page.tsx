import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

export default function AdminInventoryStubPage() {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Inventory</CardTitle>
        <CardDescription>Tồn kho tối thiểu — sắp có.</CardDescription>
      </CardHeader>
      <CardContent className="text-sm text-muted-foreground">
        Coming soon
      </CardContent>
    </Card>
  );
}
