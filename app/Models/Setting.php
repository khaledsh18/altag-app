<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value, returning a default if not found.
     */
    public static function getVal(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value.
     */
    public static function setVal(string $key, mixed $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
