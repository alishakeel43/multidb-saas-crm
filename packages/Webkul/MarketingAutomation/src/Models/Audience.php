<?php

namespace Webkul\MarketingAutomation\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\MarketingAutomation\Contracts\Audience as AudienceContract;

class Audience extends Model implements AudienceContract
{
    protected $table = 'ma_audiences';

    protected $fillable = [
        'name',
        'rules',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
        ];
    }
}
