<?php

namespace App\Models;

use App\Traits\CodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class ClassModel extends Model
{
    use CodeGenerator;
    protected $table = "classes";
    protected $fillable = [
        'start_year',
        'end_year',
        'name',
        'code',
        'status',
        'channel_id',
        'subject_id',
    ];
    protected $prefix = 'CLS';
    public function channel()
    {
        return $this->belongsTo(Channel::class, "channel_id");
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, "subject_id");
    }

    public function groups()
    {
        return $this->hasMany(Group::class, "class_id");
    }


    private static function mapYearToCode(int $year): string
    {
        if ($year >= 1 && $year <= 6) {
            // Primary
            return 'A' . $year; // A1 .. A6
        } elseif ($year >= 7 && $year <= 9) {
            // Preparatory
            return 'B' . ($year - 6); // B1 .. B3
        } elseif ($year >= 10 && $year <= 12) {
            // Secondary
            return 'C' . ($year - 9); // C1 .. C3
        }

        throw new \InvalidArgumentException("Year $year not valid in Egyptian education system");
    }


    public function generateCode(): string
    {
        $data = request()->all();

        $subject_code = Subject::select("code")->where("id", $data["subject_id"])->first()?->code;

        return "C" .  $this->channel->id . "-" . $this->prefix . "-" . $subject_code . "-" . self::mapYearToCode($data["year"]);
    }


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {

                $code  = $model->generateCode();

                if (ClassModel::where("code", $code)->exists()) {
                    throw ValidationException::withMessages([
                        'code' => ["The code [$code] already exists in this channel."],
                    ]);
                }

                $model->code = $code;
            }
        });
    }
}
