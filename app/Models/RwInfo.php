<?php

namespace App\Models;

use App\Traits\HasAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RwInfo extends Model
{
    use HasAudit, SoftDeletes;

    protected $table = 'rw_info';

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, $default = null)
    {
        $info = self::where('key', $key)->first();
        return $info ? $info->value : $default;
    }

    public static function setValue(string $key, $value): self
    {
        return self::updateOrCreate(
            ['key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );
    }

    public static function getAllAsArray(): array
    {
        $result = [];
        foreach (self::all() as $info) {
            $decoded = json_decode($info->value, true);
            $result[$info->key] = $decoded !== null ? $decoded : $info->value;
        }
        return $result;
    }
}
