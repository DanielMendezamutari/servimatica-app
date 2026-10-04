<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Dashboard\GetDashboardSummaryUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController
{
    public function __construct(
        private readonly GetDashboardSummaryUseCase $getDashboardSummaryUseCase
    ) {
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user('api') ?? $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $period = $request->query('period', 'today');
        if (!in_array($period, ['today', 'this_week', 'this_month'], true)) {
            $period = 'today';
        }

        $data = $this->getDashboardSummaryUseCase->execute($user, $period);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
