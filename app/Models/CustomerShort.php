<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class CustomerShort extends Model
{
    use HasFactory;
    protected $table = "customer_short";
    protected $fillable = ['customer_id', 'short_name'];
    public function customer(){
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    static function validate($input, $id = null)
    {
        $validated = Validator::make(
            $input,
            [
                'customer_id' => 'required|unique:customer_short,customer_id,' . ($id ?? ""),
            ],
            [
                'customer_id.required' => 'Không có mã',
            ]
        );
        return $validated;
    }
}
