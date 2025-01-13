<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\UUID;
use Illuminate\Support\Facades\Validator;

class ErrorMachine extends Model
{
    use HasFactory;
    protected $table = "error_machine";
    protected $fillable = ['ten_su_co','nguyen_nhan', 'cach_xu_ly', 'line_id', 'id'];
    protected $casts=[
        "id"=>"string",
        "line_id"=>"string"
    ];

    public function line(){
        return $this->belongsTo(Line::class, 'line_id');
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'id'=>'required|unique:error_machine,id,'.($input['id']??""),
                'ten_su_co'=>'required',
                'line_id' => 'required',
                'nguyen_nhan'=>'required', 
                'cach_xu_ly'=>'required',
            ],
            [
                'id.required' => 'Không có mã lỗi',
                'id.unique' => 'Mã lỗi đã tồn tại',
                'ten_su_co.required'=>'Không có nội dung', 
                'line_id.required'=>'Không tìm thấy công đoạn',
                'nguyen_nhan.required'=>'Không có nguyên nhân',
                'cach_xu_ly.required'=>'Không có cách xử lý', 
            ]
        );
        return $validated;
    }
}
