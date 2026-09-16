<?php

namespace App\Http\Controllers\Api\V1\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use Dedoc\Scramble\Attributes\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function __construct(private readonly DashboardMetricsService $metrics) {}

    #[Response(
        200,
        'Dashboard workbook.',
        mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        type: 'string',
        format: 'binary',
    )]
    public function __invoke(): StreamedResponse
    {
        $snapshot = $this->metrics->snapshot();
        $filename = 'dashboard-'.now((string) config('app.timezone'))->toDateString().'.xlsx';

        $spreadsheet = new Spreadsheet;
        $daily = $spreadsheet->getActiveSheet();
        $daily->setTitle('Daily');
        $daily->fromArray(['Date', 'Order count', 'Revenue'], null, 'A1');
        foreach ($snapshot['revenue_series'] as $index => $point) {
            $row = $index + 2;
            $daily->setCellValue('A'.$row, $point['date']);
            $daily->setCellValue('B'.$row, $point['order_count']);
            $daily->setCellValueExplicit('C'.$row, $point['revenue'], DataType::TYPE_STRING);
        }

        $top = $spreadsheet->createSheet();
        $top->setTitle('Top SKU');
        $top->fromArray(['SKU', 'Name', 'Qty', 'Revenue'], null, 'A1');
        foreach ($snapshot['top_skus'] as $index => $sku) {
            $row = $index + 2;
            $top->setCellValue('A'.$row, $sku['sku']);
            $top->setCellValue('B'.$row, $sku['name']);
            $top->setCellValue('C'.$row, $sku['qty']);
            $top->setCellValueExplicit('D'.$row, $sku['revenue'], DataType::TYPE_STRING);
        }

        $low = $spreadsheet->createSheet();
        $low->setTitle('Low stock');
        $low->fromArray(['SKU', 'Name', 'Warehouse code', 'Warehouse name', 'Available qty'], null, 'A1');
        foreach ($snapshot['low_stock'] as $index => $item) {
            $row = $index + 2;
            $low->setCellValue('A'.$row, $item['sku']);
            $low->setCellValue('B'.$row, $item['name']);
            $low->setCellValue('C'.$row, $item['warehouse_code']);
            $low->setCellValue('D'.$row, $item['warehouse_name']);
            $low->setCellValue('E'.$row, $item['available_qty']);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
