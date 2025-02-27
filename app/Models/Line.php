<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Line extends Model
{
    use HasFactory;
    const LINE_SONG = "30";
    const LINE_IN = "31";
    const LINE_DAN = "32";
    const LINE_XA_LOT = "33";
    protected $fillable = ['id', 'name', 'note', 'ordering', 'display'];
    protected $hidden = ['created_at', 'updated_at'];
    protected $casts = [
        "id" => "string"
    ];
    public function machine()
    {
        return $this->hasMany(Machine::class, 'line_id');
    }
    public function checkSheet()
    {
        return $this->hasMany(CheckSheet::class, 'line_id');
    }

    // Lỗi của máy
    public function error()
    {
        return $this->hasMany(ErrorMachine::class, 'line_id');
    }

    static function validate($input, $id = null)
    {
        $validated = Validator::make(
            $input,
            [
                'id' => 'required|unique:lines,id,' . $id,
                'name' => 'required',
            ],
            [
                'name.required' => 'Không có tên công đoạn',
                'id.required' => 'Không có mã công đoạn',
                'id.unique' => 'Mã công đoạn đã tồn tại',
            ]
        );
        return $validated;
    }

    public function children(){
        return $this->machine();
    }
}
