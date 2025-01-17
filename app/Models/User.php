<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Permission;
use Encore\Admin\Auth\Database\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Validator;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $fillable = [
        'id',
        'name',
        'username',
        'id',
        'password',
        'phone_number',
        'login_times_in_day',
        'last_use_at',
        'usage_time_in_day',
        'created_at',
        'updated_at',
        'deleted_at',
        'function_user',
        'department_id'
    ];
    protected $casts = [
        'id' => 'string'
    ];
    protected $guarded = [];

    public function roles()
    {
        return $this->belongsToMany(Role::class, UserRole::class, 'user_id', 'role_id');
    }

    public function permissions()
    {
        return $this->roles()->with('permissions')->get()->pluck('permissions')->flatten()->unique('id');
    }

    public function qc_permission()
    {
        $roles = $this->roles;
        $permissions = [];
        foreach ($roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->slug;
            }
        }
        $qc_permission = ['iqc', 'pqc', 'oqc'];
        return array_intersect($qc_permission, $permissions);
    }

    static function validateUpdate($input, $is_update = true)
    {
        $validated = Validator::make(
            $input,
            [
                'username' => 'required',
                'name' => 'required',
            ],
            [
                'username.required' => 'Không tìm thấy tài khoản',
                'name.required' => 'Không có tên',
            ]
        );
        return $validated;
    }

    public function user_line()
    {
        return $this->hasOne(UserLine::class, 'user_id');
    }
    public function user_machine()
    {
        return $this->hasMany(UserMachine::class, 'user_id');
    }

    public function hasPermission($permission)
    {
        foreach ($this->roles as $role) {
            if ($role->permissions()->where('slug', $permission)->first()) {
                return true;
            }
        }
        return false;
    }

    public function deliveryNotes()
    {
        return $this->belongsToMany(DeliveryNote::class, 'admin_user_delivery_note', 'admin_user_id', 'delivery_note_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
