<?php

namespace App\Http\Controllers\Api;

use App\Events\CampaignCommand;
use App\Http\Controllers\Controller;
use App\Models\BrowserProfile;
use App\Models\CommentCampaign;
use App\Models\CommentLog;
use App\Models\CommentTemplate;
use App\Models\FacebookGroup;
use App\Models\PostLog;
use App\Models\PostTemplate;
use App\Models\ScheduledPost;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ExtensionController extends Controller
{
    // ===========================
    // AUTH ENDPOINTS (no middleware)
    // ===========================

    /**
     * Login with email + password → get API token for a browser profile
     * POST /api/extension/auth/login
     *
     * Extension sends browser_data alongside credentials:
     * {
     *   "email": "...",
     *   "password": "...",
     *   "profile_name": "Chrome - Acc chính",
     *   "browser_data": {
     *     "extension_id": "abc-123-...",
     *     "facebook_name": "Nguyễn Văn A",
     *     "facebook_uid": "100012345678",
     *     "user_agent": "Mozilla/5.0 ...",
     *     "browser_name": "Chrome",
     *     "browser_version": "134.0.6998.89",
     *     "os_info": "macOS 15.2",
     *     "screen_resolution": "1920x1080",
     *     "language": "vi",
     *     "timezone": "Asia/Ho_Chi_Minh",
     *     "cookies_count": 42
     *   }
     * }
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'profile_name' => 'nullable|string|max:255',
            'browser_data' => 'nullable|array',
        ]);

        // Verify user credentials
        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => 'Email hoặc mật khẩu không đúng.',
            ], 401);
        }

        $browserData = $validated['browser_data'] ?? [];
        $extensionId = $browserData['extension_id'] ?? $request->header('X-Extension-Id');

        // Find existing profile by extension_id or create new
        $profile = null;
        if ($extensionId) {
            $profile = BrowserProfile::where('extension_id', $extensionId)->first();
        }

        if (! $profile) {
            $profile = BrowserProfile::create([
                'name' => $validated['profile_name'] ?? "Profile - {$user->name}",
                'api_token' => '',
                'status' => 'offline',
            ]);
        }

        // Generate fresh token
        $plainToken = BrowserProfile::generateToken($profile);

        // Sync all browser data from extension
        $browserData['extension_id'] = $extensionId;
        $browserData['user_agent'] = $browserData['user_agent'] ?? $request->userAgent();
        $profile->syncFromExtension($browserData, $request->ip());

        return response()->json([
            'success' => true,
            'token' => $plainToken,
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'extension_id' => $profile->extension_id,
                'status' => $profile->status,
            ],
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Register a new browser profile
     * POST /api/extension/auth/register-profile
     */
    public function registerProfile(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'name' => 'required|string|max:255',
            'browser_data' => 'nullable|array',
            'proxy' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'error' => 'invalid_credentials',
                'message' => 'Email hoặc mật khẩu không đúng.',
            ], 401);
        }

        $profile = BrowserProfile::create([
            'name' => $validated['name'],
            'api_token' => '',
            'proxy' => $validated['proxy'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'offline',
        ]);

        $plainToken = BrowserProfile::generateToken($profile);

        // Sync browser data from extension
        $browserData = $validated['browser_data'] ?? [];
        $browserData['extension_id'] = $browserData['extension_id'] ?? $request->header('X-Extension-Id');
        $browserData['user_agent'] = $browserData['user_agent'] ?? $request->userAgent();
        $profile->syncFromExtension($browserData, $request->ip());

        return response()->json([
            'success' => true,
            'token' => $plainToken,
            'profile' => $profile->only(['id', 'name', 'extension_id', 'status', 'facebook_name', 'browser_name']),
        ]);
    }

    // ===========================
    // PROTECTED ENDPOINTS (require token)
    // ===========================

    /**
     * Get current profile info
     * GET /api/extension/profile
     */
    public function getProfile(Request $request)
    {
        $profile = $request->browser_profile;

        return response()->json([
            'success' => true,
            'profile' => $profile->only([
                'id', 'name', 'extension_id', 'facebook_name', 'facebook_uid',
                'browser_name', 'browser_version', 'os_info', 'screen_resolution',
                'language', 'timezone', 'ip_address', 'status', 'last_active_at',
            ]),
        ]);
    }

    /**
     * Extension pushes browser config update
     * POST /api/extension/sync-profile
     *
     * Called on:
     * - Extension startup (full browser info)
     * - Facebook login/logout (updated FB name/uid)
     * - Tab changes (updated cookies count)
     * - Periodic sync (every 5 minutes)
     */
    public function syncProfile(Request $request)
    {
        $validated = $request->validate([
            'extension_id' => 'nullable|string',
            'facebook_name' => 'nullable|string',
            'facebook_uid' => 'nullable|string',
            'user_agent' => 'nullable|string',
            'browser_name' => 'nullable|string',
            'browser_version' => 'nullable|string',
            'os_info' => 'nullable|string',
            'screen_resolution' => 'nullable|string',
            'language' => 'nullable|string',
            'timezone' => 'nullable|string',
            'cookies_count' => 'nullable|integer',
        ]);

        $profile = $request->browser_profile;
        $profile->syncFromExtension($validated, $request->ip());

        return response()->json([
            'success' => true,
            'message' => 'Profile synced',
            'profile' => $profile->fresh()->only([
                'id', 'name', 'facebook_name', 'browser_name', 'browser_version',
                'os_info', 'ip_address', 'status', 'last_active_at',
            ]),
        ]);
    }

    /**
     * Extension gets pending campaigns
     */
    public function getCampaigns(Request $request)
    {
        $profile = $request->browser_profile;

        $campaigns = CommentCampaign::where('extension_id', $profile->extension_id)
            ->where('status', 'running')
            ->get();

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Extension reports comment result
     */
    public function reportComment(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:comment_campaigns,id',
            'group_id' => 'required|string',
            'group_name' => 'required|string',
            'post_url' => 'nullable|string',
            'comment_content' => 'required|string',
            'status' => 'required|in:success,failed,skipped',
            'error_message' => 'nullable|string',
        ]);

        $log = CommentLog::create([
            ...$validated,
            'commented_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'log_id' => $log->id,
        ]);
    }

    /**
     * Extension reports campaign completion
     */
    public function campaignComplete(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:comment_campaigns,id',
            'stats' => 'nullable|array',
        ]);

        $campaign = CommentCampaign::findOrFail($validated['campaign_id']);
        $campaign->update([
            'status' => 'completed',
            'completed_at' => now(),
            'stats' => $validated['stats'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Extension reports campaign status change (completed/paused)
     * POST /api/extension/campaign-status
     */
    public function campaignStatus(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:comment_campaigns,id',
            'status' => 'required|in:running,paused,completed,failed',
            'stats' => 'nullable|array',
        ]);

        $campaign = CommentCampaign::findOrFail($validated['campaign_id']);
        $updateData = [
            'status' => $validated['status'],
            'stats' => $validated['stats'] ?? $campaign->stats,
        ];
        if ($validated['status'] === 'completed') {
            $updateData['completed_at'] = now();
        }
        $campaign->update($updateData);

        return response()->json([
            'success' => true,
            'campaign' => $campaign->fresh(),
        ]);
    }

    /**
     * Extension heartbeat — also accepts browser_data for auto-sync
     * POST /api/extension/heartbeat
     */
    public function heartbeat(Request $request)
    {
        $profile = $request->browser_profile;

        // Sync browser data if provided (extension can push changes on every heartbeat)
        $browserData = $request->input('browser_data', []);
        if (! empty($browserData)) {
            $profile->syncFromExtension($browserData, $request->ip());
        }

        return response()->json([
            'success' => true,
            'server_time' => now()->toISOString(),
            'profile_status' => $profile->status,
        ]);
    }

    /**
     * Get all settings
     */
    public function getSettings(Request $request)
    {
        $settings = Setting::getAllGrouped();

        return response()->json([
            'success' => true,
            'settings' => $settings,
        ]);
    }

    /**
     * Get comment templates
     * GET /api/extension/comment-templates
     */
    public function getCommentTemplates(Request $request)
    {
        $templates = CommentTemplate::orderBy('updated_at', 'desc')
            ->get(['id', 'name', 'content', 'images', 'tags', 'usage_count', 'updated_at']);

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Get post templates
     * GET /api/extension/post-templates
     */
    public function getPostTemplates(Request $request)
    {
        $templates = PostTemplate::active()
            ->orderBy('usage_count', 'desc')
            ->get(['id', 'name', 'category', 'content', 'images', 'tags', 'seed_comments', 'usage_count', 'updated_at']);

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    /**
     * Update settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        Setting::bulkUpdate($validated['settings']);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
        ]);
    }

    /**
     * Get groups for current profile
     * GET /api/extension/groups
     */
    public function getGroups(Request $request)
    {
        $profile = $request->browser_profile;

        $groups = FacebookGroup::where('browser_profile_id', $profile->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'group_id', 'name', 'category', 'url', 'image', 'created_at']);

        return response()->json([
            'success' => true,
            'groups' => $groups,
        ]);
    }

    /**
     * Sync groups from extension (batch upsert)
     * POST /api/extension/groups/sync
     */
    public function syncGroups(Request $request)
    {
        $validated = $request->validate([
            'groups' => 'required|array',
            'groups.*.group_id' => 'required|string',
            'groups.*.name' => 'required|string|max:500',
            'groups.*.category' => 'nullable|string|max:50',
            'groups.*.url' => 'nullable|string|max:500',
            'groups.*.image' => 'nullable|string|max:1000',
            'groups.*.member_count' => 'nullable|integer',
            'groups.*.privacy' => 'nullable|string|in:public,private',
        ]);

        $profile = $request->browser_profile;
        $synced = [];

        foreach ($validated['groups'] as $groupData) {
            $group = FacebookGroup::updateOrCreate(
                [
                    'browser_profile_id' => $profile->id,
                    'group_id' => $groupData['group_id'],
                ],
                [
                    'name' => $groupData['name'],
                    'category' => $groupData['category'] ?? 'general',
                    'url' => $groupData['url'] ?? null,
                    'image' => $groupData['image'] ?? null,
                    'member_count' => $groupData['member_count'] ?? null,
                    'privacy' => $groupData['privacy'] ?? null,
                ]
            );
            $synced[] = $group;
        }

        return response()->json([
            'success' => true,
            'synced' => count($synced),
            'groups' => $synced,
        ]);
    }

    /**
     * Delete a group by Facebook group ID
     * DELETE /api/extension/groups/{groupId}
     */
    public function deleteGroup(Request $request, string $groupId)
    {
        $profile = $request->browser_profile;

        $deleted = FacebookGroup::where('browser_profile_id', $profile->id)
            ->where('group_id', $groupId)
            ->delete();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Group deleted' : 'Group not found',
        ]);
    }

    /**
     * Broadcast command to extension
     */
    public function broadcast(Request $request)
    {
        $validated = $request->validate([
            'extension_id' => 'required|string',
            'command' => 'required|string|in:campaign.start,campaign.stop,config.update,ping,sync-profile,extension.command',
            'payload' => 'nullable|array',
        ]);

        event(new CampaignCommand(
            $validated['extension_id'],
            $validated['command'],
            $validated['payload'] ?? []
        ));

        return response()->json([
            'success' => true,
            'message' => "Command '{$validated['command']}' broadcasted to {$validated['extension_id']}",
        ]);
    }

    // ===========================
    // SCHEDULED POSTS
    // ===========================

    /**
     * GET /api/extension/scheduled-posts
     */
    public function getScheduledPosts(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $posts = ScheduledPost::forProfile($profile->id)
            ->orderBy('scheduled_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'posts' => $posts,
        ]);
    }

    /**
     * POST /api/extension/scheduled-posts
     */
    public function createScheduledPost(Request $request)
    {
        $profile = $request->attributes->get('profile');

        $validated = $request->validate([
            'content' => 'required|string',
            'images' => 'nullable|array',
            'group_ids' => 'required|array|min:1',
            'group_ids.*' => 'string',
            'settings' => 'nullable|array',
            'scheduled_at' => 'required|date|after:now',
        ]);

        $post = ScheduledPost::create([
            'browser_profile_id' => $profile->id,
            'content' => $validated['content'],
            'images' => $validated['images'] ?? [],
            'group_ids' => $validated['group_ids'],
            'settings' => $validated['settings'] ?? [],
            'scheduled_at' => $validated['scheduled_at'],
        ]);

        return response()->json([
            'success' => true,
            'post' => $post,
        ], 201);
    }

    /**
     * PUT /api/extension/scheduled-posts/{id}/status
     */
    public function updateScheduledPostStatus(Request $request, int $id)
    {
        $profile = $request->attributes->get('profile');

        $post = ScheduledPost::forProfile($profile->id)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:completed,failed',
            'results' => 'nullable|array',
        ]);

        if ($validated['status'] === 'completed') {
            $post->markCompleted($validated['results'] ?? []);
        } else {
            $post->markFailed($validated['results'] ?? []);
        }

        return response()->json([
            'success' => true,
            'post' => $post->fresh(),
        ]);
    }

    /**
     * DELETE /api/extension/scheduled-posts/{id}
     */
    public function cancelScheduledPost(Request $request, int $id)
    {
        $profile = $request->attributes->get('profile');

        $post = ScheduledPost::forProfile($profile->id)
            ->whereIn('status', ['pending', 'processing'])
            ->findOrFail($id);

        $post->update(['status' => 'cancelled', 'completed_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'Scheduled post cancelled.',
        ]);
    }

    /**
     * Report a post result (success/failure for a specific group)
     * POST /api/extension/post-logs
     */
    public function reportPostResult(Request $request)
    {
        $validated = $request->validate([
            'scheduled_post_id' => 'nullable|integer',
            'group_id' => 'required|string',
            'group_name' => 'nullable|string|max:500',
            'status' => 'required|in:success,failed,skipped',
            'content_preview' => 'nullable|string|max:500',
            'error' => 'nullable|string|max:1000',
            'post_url' => 'nullable|string|max:500',
        ]);

        $profile = $request->browser_profile;

        $log = PostLog::create([
            'scheduled_post_id' => $validated['scheduled_post_id'] ?? null,
            'browser_profile_id' => $profile->id,
            'group_id' => $validated['group_id'],
            'group_name' => $validated['group_name'] ?? null,
            'status' => $validated['status'],
            'content_preview' => $validated['content_preview'] ?? null,
            'error' => $validated['error'] ?? null,
            'post_url' => $validated['post_url'] ?? null,
            'posted_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'log_id' => $log->id,
        ]);
    }
}
