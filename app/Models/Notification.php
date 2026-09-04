<?php

namespace App\Models;

use App\Enum\SearchModelParams;
use App\Events\NotificationSent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public static $searchable = SearchModelParams::Notification;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'data',
        'is_read',
        'company_id',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Methods
    public function markAsRead()
    {
        $this->update(['is_read' => true]);
    }

    // Static helper to create notifications + broadcast via Reverb + Firebase push
    public static function send($userId, $title, $body = null, $type = null, $data = null, $companyId = null)
    {
        $notification = self::create([
            'user_id' => $userId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
            'company_id' => $companyId,
        ]);

        // Broadcast real-time via Reverb (web)
        try {
            broadcast(new NotificationSent($notification));
        } catch (\Exception $e) {
            \Log::warning('Notification broadcast failed: ' . $e->getMessage());
        }

        // Send Firebase push notification (mobile app)
        try {
            $pushController = new \App\Http\Controllers\SendPushNotificationsController();
            $pushController->sendPushNotification($userId, $title, $body, $type, $data, $companyId);
        } catch (\Exception $e) {
            \Log::warning('Firebase push failed: ' . $e->getMessage());
        }

        return $notification;
    }

    // Send to multiple users
    public static function sendToMany($userIds, $title, $body = null, $type = null, $data = null, $companyId = null)
    {
        $notifications = [];
        foreach ($userIds as $userId) {
            $notifications[] = self::send($userId, $title, $body, $type, $data, $companyId);
        }
        return $notifications;
    }
}
