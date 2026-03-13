<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'group', 'key', 'value', 'type', 'label', 'description',
    ];

    /**
     * Get a setting value by key with auto-casting
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->castValue();
    }

    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, mixed $value): void
    {
        $setting = static::where('key', $key)->first();

        if ($setting) {
            // Convert boolean to string for storage
            if (is_bool($value)) {
                $value = $value ? '1' : '0';
            }
            $setting->update(['value' => (string) $value]);
        }
    }

    /**
     * Get all settings for a group, auto-casted
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->castValue()])
            ->toArray();
    }

    /**
     * Get all settings grouped by group name
     */
    public static function getAllGrouped(): array
    {
        return static::all()
            ->groupBy('group')
            ->map(fn ($items) => $items->mapWithKeys(
                fn (Setting $s) => [$s->key => $s->castValue()]
            ))
            ->toArray();
    }

    /**
     * Bulk update settings from key-value pairs
     */
    public static function bulkUpdate(array $data): void
    {
        foreach ($data as $key => $value) {
            static::setValue($key, $value);
        }
    }

    /**
     * Cast the value based on the type field
     */
    public function castValue(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => in_array($this->value, ['1', 'true', 'yes'], true),
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Get the raw model data for forms
     */
    public static function getFormData(): array
    {
        return static::all()
            ->mapWithKeys(fn (Setting $s) => [$s->key => $s->value])
            ->toArray();
    }
}
