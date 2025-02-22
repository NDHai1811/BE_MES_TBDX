<?php

namespace App\Models;

use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Error extends Model
{
    use HasFactory;
    protected $fillable = ['id', 'name', 'line_id'];
    public $incrementing = false;
    protected $casts = [
        "id" => "string"
    ];

    public function line()
    {
        return $this->belongsTo(Line::class, 'line_id');
    }

    static function validate($input, $id = null)
    {
        $validated = Validator::make(
            $input,
            [
                'id'=>'required|unique:errors,id,'.$id,
                'name'=>'required',
                'line_id' => 'required',
            ],
            [
                'id.required' => 'Không có mã lỗi',
                'id.unique' => 'Mã lỗi đã tồn tại',
                'name.required'=>'Không có nội dung', 
                'line_id.required'=>'Không tìm thấy công đoạn', 
            ]
        );
        return $validated;
    }
}
