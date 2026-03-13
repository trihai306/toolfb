<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\CommentCampaign;
use App\Models\FacebookGroup;
use App\Models\ScheduledPost;

class BrowserProfile extends Model
{
    protected $fillable = [
        'name',
        'api_token',
        'extension_id',
        'facebook_name',
        'facebook_uid',
        'facebook_avatar',
        'user_agent',
        'browser_name',
        'browser_version',
        'os_info',
        'screen_resolution',
        'language',
        'timezone',
        'ip_address',
        'cookies_count',
        'browser_config',
        'proxy',
        'status',
        'notes',
        'last_active_at',
    ];

    protected $hidden = [
        'api_token',
    ];

    protected function casts(): array
    {
        return [
            'last_active_at' => 'datetime',
            'browser_config' => 'array',
        ];
    }

    // === Relationships ===

    public function campaigns(): HasMany
    {
        return $this->hasMany(CommentCampaign::class, 'extension_id', 'extension_id');
    }

    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(FacebookGroup::class);
    }

    // === Token helpers ===

    public static function generateToken(self $profile): string
    {
        $plainToken = Str::random(48);
        $profile->update(['api_token' => hash('sha256', $plainToken)]);
        return $plainToken;
    }

    public static function findByToken(string $plainToken): ?self
    {
        return static::where('api_token', hash('sha256', $plainToken))->first();
    }

    // === Sync browser config from extension ===

    /**
     * Update profile with data received from extension.
     * Extension sends all available browser info on connect and heartbeat.
     */
    public function syncFromExtension(array $browserData, ?string $ip = null): void
    {
        $data = [
            'status' => 'online',
            'last_active_at' => now(),
        ];

        // Map extension fields → db columns
        $fieldMap = [
            'extension_id'    => 'extension_id',
            'facebook_name'   => 'facebook_name',
            'facebook_uid'    => 'facebook_uid',
            'facebook_avatar' => 'facebook_avatar',
            'user_agent'      => 'user_agent',
            'browser_name'    => 'browser_name',
            'browser_version' => 'browser_version',
            'os_info'         => 'os_info',
            'screen_resolution' => 'screen_resolution',
            'language'        => 'language',
            'timezone'        => 'timezone',
            'cookies_count'   => 'cookies_count',
        ];

        foreach ($fieldMap as $inputKey => $dbColumn) {
            if (isset($browserData[$inputKey]) && $browserData[$inputKey] !== '') {
                $data[$dbColumn] = $browserData[$inputKey];
            }
        }

        // Store IP from request
        if ($ip) {
            $data['ip_address'] = $ip;
        }

        // Store full raw config for reference
        if (! empty($browserData)) {
            $data['browser_config'] = $browserData;
        }

        $this->update($data);
    }

    // === Legacy helper (still used by middleware) ===

    public function markOnline(?string $extensionId = null, ?string $userAgent = null): void
    {
        $data = [
            'status' => 'online',
            'last_active_at' => now(),
        ];

        if ($extensionId) {
            $data['extension_id'] = $extensionId;
        }
        if ($userAgent) {
            $data['user_agent'] = $userAgent;
        }

        $this->update($data);
    }

    public function markOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online'
            && $this->last_active_at
            && $this->last_active_at->gt(now()->subMinutes(5));
    }
}
