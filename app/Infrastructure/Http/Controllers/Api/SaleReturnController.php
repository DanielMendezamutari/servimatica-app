<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Sale\CheckSaleWarrantyUseCase;
use App\Application\Sale\ProcessSaleReturnUseCase;
use App\Domain\Sale\SaleReturnRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Infrastructure\Http\Requests\CreateSaleReturnRequest;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleReturnController extends Controller
{
    public function __construct(
        private readonly SaleReturnRepositoryInterface $saleReturnRepository
    ) {}

    public function warrantyCheck(int $id, CheckSaleWarrantyUseCase $useCase): JsonResponse
    {
        try {
            $data = $useCase->execute($id);

            return response()->json([
                'data' => $data,
            ]);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    public function store(int $id, CreateSaleReturnRequest $request, ProcessSaleReturnUseCase $useCase): JsonResponse
    {
        $user = $request->user();
        $userRole = $user->role ?? 'seller';

        try {
            $saleReturn = $useCase->execute($id, $request->validated(), (int) $user->id, (string) $userRole);

            return response()->json([
                'message' => 'Devolución procesada exitosamente.',
                'data' => $saleReturn->toArray(),
            ], 201);
        } catch (DomainException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);
        $resolution = $request->query('resolution');
        $search = $request->query('search');

        $result = $this->saleReturnRepository->list($page, $perPage, $resolution, $search);

        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        $return = $this->saleReturnRepository->findById($id);
        if ($return === null) {
            return response()->json(['message' => 'Devolución no encontrada.'], 404);
        }

        return response()->json(['data' => $return->toArray()]);
    }

    public function receipt(
        int $id,
        Request $request,
        \App\Domain\Company\CompanySettingRepositoryInterface $companyRepo
    ): \Illuminate\Http\Response|JsonResponse {
        $return = $this->saleReturnRepository->findById($id);

        if ($return === null) {
            return response()->json(['message' => 'Devolución no encontrada.'], 404);
        }

        $company = $companyRepo->get();
        $format = $request->query('format', 'thermal');

        return response()->view('sales.return_receipt', [
            'return' => $return,
            'company' => $company,
            'format' => $format,
        ]);
    }
}
