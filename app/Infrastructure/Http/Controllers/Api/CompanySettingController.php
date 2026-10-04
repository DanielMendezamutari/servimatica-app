<?php

namespace App\Infrastructure\Http\Controllers\Api;

use App\Application\Company\GetCompanySettingUseCase;
use App\Application\Company\UpdateCompanySettingUseCase;
use App\Infrastructure\Http\Requests\UpdateCompanySettingRequest;
use Illuminate\Http\JsonResponse;

final class CompanySettingController
{
    public function show(GetCompanySettingUseCase $useCase): JsonResponse
    {
        $setting = $useCase->execute();

        return response()->json($setting->toArray());
    }

    public function update(UpdateCompanySettingRequest $request, UpdateCompanySettingUseCase $useCase): JsonResponse
    {
        $data = $request->validated();
        $logoFile = $request->file('logo');

        unset($data['logo']);

        $setting = $useCase->execute($data, $logoFile);

        return response()->json([
            'message' => 'Información de la empresa actualizada exitosamente.',
            'data' => $setting->toArray(),
        ]);
    }

    public function publicInfo(\App\Application\Company\GetPublicCompanyInfoUseCase $useCase): JsonResponse
    {
        $info = $useCase->execute();

        return response()->json($info);
    }
}
