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
    protected $fillable = ['name', 'reason', 'behavior', 'line_id', 'id'];
    protected $casts = [
        "id" => "string",
        "line_id" => "string"
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
                'id' => 'required|unique:error_machine,id,' . ($id ?? ""),
                'name' => 'required',
                'line_id' => 'required',
            ],
            [
                'id.required' => 'Không có mã lỗi',
                'id.unique' => 'Mã lỗi đã tồn tại',
                'name.required' => 'Không có nội dung',
                'line_id.required' => 'Không tìm thấy công đoạn',
            ]
        );
        return $validated;
    }
}
