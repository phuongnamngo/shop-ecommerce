<?php

namespace App\Http\Controllers\Api\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Identity\AdminCustomerResource;
use App\Models\Customer;
use App\Support\ApiResponse;
use App\Support\CatalogPaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::query()->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], strtolower((string) $request->string('q')));
            $query->where(function ($q) use ($escaped): void {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.$escaped.'%'])
                    ->orWhereRaw('LOWER(email) LIKE ?', ['%'.$escaped.'%'])
                    ->orWhereRaw('LOWER(COALESCE(phone, \'\')) LIKE ?', ['%'.$escaped.'%']);
            });
        }

        $paginator = $query->paginate(CatalogPaginator::perPage($request));

        return ApiResponse::success(
            AdminCustomerResource::collection($paginator->getCollection())->resolve(),
            CatalogPaginator::meta($paginator),
        );
    }

    public function show(int $id): JsonResponse
    {
        $customer = Customer::query()->with('addresses')->findOrFail($id);

        return ApiResponse::success(AdminCustomerResource::make($customer)->resolve());
    }
}
