<?php

namespace App\Models;

use App\Models\Student;
use App\Models\SessionTime;
use App\Traits\CodeGenerator;
use Modules\Channel\App\Traits\HasChannelScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Group extends Model
{
    use CodeGenerator, HasChannelScope;
    protected $fillable = [
        'name',
        'code',
        'class_id',
        'numbre_of_sessions',
        'price_of_group',
        'status',
        'channel_id',
        'teacher_id',
    ];

    protected  $prefix = 'GRP';

    public function class()
    {
        return $this->belongsTo(ClassModel::class, "class_id");
    }
    public function times()
    {
        return $this->hasMany(SessionTime::class);
    }
 public function students() { 
    return $this->hasMany( Student::class);
 }


    public function generateCode(): string
    {
        $data = request()->all();

        $class_code = ClassModel::select("code")->where("id", $data["class_id"])->first()?->code;
        $count = $this->where("class_id", $data["class_id"])->count();

        return   str_replace("CLS", $this->prefix . "-" . ++$count, $class_code);
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
}
