<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Audit\ListLoginLogsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuditController
{
    public function logins(Request $request, ListLoginLogsUseCase $useCase): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 15), 50);

        $filters = [
            'search' => (string) $request->query('search', ''),
            'status' => (string) $request->query('status', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];

        $result = $useCase->execute($page, $perPage, $filters);

        return response()->json($result);
    }
}
