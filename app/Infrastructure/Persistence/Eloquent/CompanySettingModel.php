<?php

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Model;

class CompanySettingModel extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'trade_name',
        'legal_name',
        'tax_id',
        'slogan',
        'branch_name',
        'city',
        'address',
        'mobile',
        'phone',
        'email',
        'logo_path',
        'default_quote_terms',
        'receipt_footer_message',
        'warranty_terms',
    ];
}
