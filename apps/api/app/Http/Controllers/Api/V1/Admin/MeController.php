<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $admin = $request->user('admin');

        return response()->json([
            'data' => [
                'id' => $admin->id,
                'code' => $admin->code,
                'name' => $admin->name,
                'email' => $admin->email,
                'status' => $admin->status,
                'roles' => $admin->getRoleNames(),
            ],
        ]);
    }
}
