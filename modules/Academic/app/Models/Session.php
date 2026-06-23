<?php

namespace Modules\Academic\App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Modules\Academic\App\Models\Group;
use Modules\Academic\App\Models\SessionTime;
use Modules\Channel\App\Traits\HasChannelScope;

class Session extends Model
{
    use HasChannelScope;

    protected $table = 'group_sessions';

    protected $fillable = [
        'channel_id', 'group_id', 'session_time_id',
        'scheduled_at', 'duration_minutes', 'type', 'status', 'location', 'notes',
        'qr_token', 'qr_expires_at',
    ];

    protected $casts = [
        'scheduled_at'     => 'datetime',
        'qr_expires_at'    => 'datetime',
        'duration_minutes' => 'integer',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function sessionTime()
    {
        return $this->belongsTo(SessionTime::class);
    }

    public function attendances()
    {
        return $this->hasMany(\Modules\Attendance\App\Models\Attendance::class, 'session_id');
    }

    public function getLocalTimeAttribute(): string
    {
        return $this->scheduled_at->setTimezone('Africa/Cairo')->toDateTimeString();
    }

    public function scopeScheduled($q) { return $q->where('status', 'scheduled'); }
    public function scopeCompleted($q) { return $q->where('status', 'completed'); }
    public function scopeCancelled($q) { return $q->where('status', 'cancelled'); }
    public function scopeLive($q)      { return $q->where('status', 'live'); }

    public function canBeEdited(): bool
    {
        return $this->status === 'scheduled';
    }

    // ─── QR Token helpers ────────────────────────────────────────────────────

    /**
     * Generate a fresh signed QR token and persist it on this session.
     * QR expires at: scheduled_at + duration_minutes + 30 min grace period.
     */
    public function refreshQrToken(): self
    {
        $expiresAt = $this->scheduled_at
            ->copy()
            ->addMinutes($this->duration_minutes + 30);

        $payload = "{$this->id}|{$expiresAt->timestamp}";
        $sig     = hash_hmac('sha256', $payload, config('app.key'));
        $token   = base64_encode($payload) . '.' . $sig;

        $this->update([
            'qr_token'      => $token,
            'qr_expires_at' => $expiresAt,
        ]);

        return $this->fresh();
    }

    /**
     * Verify a QR token string and return ['session_id', 'expires_at'] or null on failure.
     */
    public static function parseQrToken(string $token): ?array
    {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $decoded = base64_decode($parts[0], true);
        if ($decoded === false) {
            return null;
        }

        $expectedSig = hash_hmac('sha256', $decoded, config('app.key'));
        if (! hash_equals($expectedSig, $parts[1])) {
            return null;
        }

        $segments = explode('|', $decoded, 2);
        if (count($segments) !== 2) {
            return null;
        }

        return [
            'session_id' => (int) $segments[0],
            'expires_at' => (int) $segments[1],
        ];
    }
}
