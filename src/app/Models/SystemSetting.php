<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'description',
        'type',
    ];

    // Cache settings for performance
    public static function getSettings()
    {
        return Cache::remember('system_settings', 3600, function () {
            $settings = self::all();
            $result = [];

            foreach ($settings as $setting) {
                $value = $setting->value;

                switch ($setting->type) {
                    case 'integer':
                        $value = (int)$value;
                        break;
                    case 'float':
                        $value = (float)$value;
                        break;
                    case 'boolean':
                        $value = (bool)$value;
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }

                $result[$setting->key] = $value;
            }

            return $result;
        });
    }

    public static function getSetting($key, $default = null)
    {
        $settings = self::getSettings();
        return $settings[$key] ?? $default;
    }

    public static function setSetting($key, $value, $type = 'string', $description = null)
    {
        if ($type === 'json') {
            $value = json_encode($value);
        }

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'description' => $description
            ]
        );

        // Clear cache when settings change
        Cache::forget('system_settings');

        return $setting;
    }

    /**
     * Get SM-2 algorithm settings
     */
    public static function getSM2Settings()
    {
        return [
            'initial_interval' => self::getSetting('sm2_initial_interval', 1),
            'second_interval' => self::getSetting('sm2_second_interval', 6),
            'min_ease_factor' => self::getSetting('sm2_min_ease_factor', 1.3),
        ];
    }

    /**
     * Get user level thresholds
     */
    public static function getUserLevelThresholds()
    {
        return self::getSetting('user_level_thresholds', [
            1 => ['rating' => 0, 'max_collections' => 5],
            2 => ['rating' => 2.1, 'max_collections' => 10],
            3 => ['rating' => 3.1, 'max_collections' => 20],
            4 => ['rating' => 4.1, 'max_collections' => -1]
        ]);
    }

    /**
     * Get new cards per day limit
     */
    public static function getNewCardsPerDayLimit()
    {
        return self::getSetting('new_cards_per_day_limit', 20);
    }

    /**
     * Get password reset token expiry in minutes
     */
    public static function getPasswordResetTokenExpiry()
    {
        return self::getSetting('password_reset_token_expiry', 60);
    }

    /**
     * Clear settings cache
     */
    public static function clearCache()
    {
        Cache::forget('system_settings');
    }

    /**
     * Boot method to clear cache when model is saved or deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }

    /**
     * Scope to get settings by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get typed value accessor
     */
    public function getTypedValueAttribute()
    {
        $value = $this->value;

        switch ($this->type) {
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'boolean':
                return (bool)$value;
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Set typed value mutator
     */
    public function setValueAttribute($value)
    {
        if ($this->type === 'json' && is_array($value)) {
            $this->attributes['value'] = json_encode($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
}
