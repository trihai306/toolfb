<?php

use App\Models\BrowserProfile;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
*/

// Private channel for extension commands
Broadcast::channel('extension.{extensionId}', function ($user, $extensionId) {
    // For API token auth, the profile is resolved by middleware
    // Allow if the profile matches the channel's extension_id
    $profile = BrowserProfile::where('extension_id', $extensionId)->first();
    return $profile !== null;
});
