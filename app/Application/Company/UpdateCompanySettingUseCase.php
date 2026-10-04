<?php

namespace App\Application\Company;

use App\Domain\Company\CompanySetting;
use App\Domain\Company\CompanySettingRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class UpdateCompanySettingUseCase
{
    public function __construct(
        private readonly CompanySettingRepositoryInterface $repository
    ) {}

    public function execute(array $data, ?UploadedFile $logoFile = null): CompanySetting
    {
        if ($logoFile !== null && $logoFile->isValid()) {
            $path = Storage::disk('public')->putFile('company', $logoFile);
            $data['logo_path'] = $path;
        }

        return $this->repository->save($data);
    }
}
