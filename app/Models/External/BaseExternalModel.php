<?php

namespace App\Models\External;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

abstract class BaseExternalModel extends Model
{
    use SoftDeletes;

    /**
     * Default connection untuk external database
     * Bisa di-override dengan Model::on('rt1'), Model::on('rt2'), dll
     */
    protected $connection = 'rt9';

    /**
     * Kolom deleted_at untuk soft deletes
     */
    protected const DELETED_AT = 'deleted_at';

    /**
     * Daftar koneksi RT yang valid
     */
    public static array $validConnections = [
        'rt1', 'rt2', 'rt3', 'rt4', 'rt5',
        'rt6', 'rt7', 'rt8', 'rt9', 'rt10',
        'rt11', 'rt12', 'rt13', 'rt14', 'rt15',
    ];

    /**
     * Scope untuk switch connection ke RT tertentu
     * Usage: House::rt('rt9')->get() atau House::rt(9)->get()
     */
    public function scopeRt($query, string|int $rt)
    {
        $connection = is_numeric($rt) ? "rt{$rt}" : $rt;

        if (!in_array($connection, self::$validConnections)) {
            throw new \InvalidArgumentException("Invalid RT connection: {$connection}");
        }

        return $query->on($connection);
    }

    /**
     * Static helper untuk set connection
     * Usage: House::forRt(9)->where(...)->get()
     */
    public static function forRt(string|int $rt): static
    {
        $connection = is_numeric($rt) ? "rt{$rt}" : $rt;

        if (!in_array($connection, static::$validConnections)) {
            throw new \InvalidArgumentException("Invalid RT connection: {$connection}");
        }

        $instance = new static;
        $instance->setConnection($connection);

        return $instance;
    }
}
