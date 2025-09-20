<?php



namespace App\Models;

use App\Traits\CodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends Model
{
    use CodeGenerator;
    use HasFactory;

    protected $prefix = 'S';
    protected $fillable = [
        'code',
        'name',
        'email',
        'phone',
        'geneder',
        'password',
        'status',
        'group_id',
        "channel_id",
        'image',
    ];

    // If you want to hide password in responses
    protected $hidden = [
        'password',
    ];

    // Example: Student belongs to Channel
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function generateCode(): string
    {
        $data = request()->all();

        $group_code = Group::select("code")->where("id", $data["group_id"])->first()?->code;

        $count = $this->select("id")->where("group_id", $data["group_id"])->latest()->first()?->id ?? 0;

        $grp = "GRP";

        if (preg_match('/GRP-\d+/', $group_code, $match)) {
            $grp =  $match[0];
        }

        return preg_replace('/(GRP-\d+)/', $grp . "-" . $this->prefix . ++$count, $group_code);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {

                $code  = $model->generateCode();

                if (self::where("code", $code)->exists()) {
                    throw ValidationException::withMessages([
                        'code' => ["The code [$code] already exists in this channel."],
                    ]);
                }

                $model->code = $code;
            }
        });
    }

    public function attendanceForToday()
    {
        return $this->hasMany(\App\Models\Attendance::class, 'student_id')
            ->whereDate('date', now()->toDateString());
    }
}
