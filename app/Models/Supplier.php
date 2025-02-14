<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class Supplier extends Model
{
    use HasFactory;
    protected $table = "suppliers";
    protected $fillable = ['id', 'name'];
    protected $casts = [
        'id' => 'string',
    ];

    static function validate($input, $id = "")
    {
        $validated = Validator::make(
            $input,
            [
                'id'=>'required|unique:suppliers,id,' . $id,
                'name' => 'required',
            ],
            [
                'id.required' => 'Không có mã nhà cung cấp',
                'id.unique' => 'Mã nhà cung cấp đã tồn tại',
                'name.required'=>'Không có tên nhà cung cấp',
            ]
        );
        return $validated;
    }
}
