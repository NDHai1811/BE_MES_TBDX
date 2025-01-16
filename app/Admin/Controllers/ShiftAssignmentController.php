<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\LSXPallet;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\ShiftAssignment;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\Route;
use stdClass;

class ShiftAssignmentController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('shift-assignment/list', [ShiftAssignmentController::class, 'getShiftAssignment']);
            Route::post('shift-assignment/create', [ShiftAssignmentController::class, 'createShiftAssignment']);
            Route::delete('shift-assignment/delete', [ShiftAssignmentController::class, 'deleteShiftAssignment']);
            Route::patch('shift-assignment/update', [ShiftAssignmentController::class, 'updateShiftAssignment']);
        });
    }

    public function getShiftAssignment(Request $request)
    {
        $query = ShiftAssignment::orderBy('shift_id')->orderBy('created_at', 'DESC');
        if (!empty($request->user_id)) {
            $query->where('user_id', $request->user_id);
        }
        if (!empty($request->username)) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('username', "$request->username");
            });
        }
        if (!empty($request->name)) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', "%$request->name%");
            });
        }
        if (!empty($request->shift_id)) {
            $query->where('shift_id', $request->shift_id);
        }
        $records = $query->with('user', 'shift')->paginate($request->pageSize ?? null);
        $shift_assignments = $records->items();
        foreach ($shift_assignments as $key => $value) {
            $value->user_name = $value->user->name ?? "";
            $value->username = $value->user->username ?? "";
            $value->shift_name = $value->shift->name ?? "";
        }
        return $this->success(['data' => $shift_assignments, 'pagination' => QueryHelper::pagination($request, $records)]);
    }

    public function updateShiftAssignment(Request $request)
    {
        $shift = ShiftAssignment::find($request->id);
        if (!$shift) {
            return $this->failure('', 'Không tìm thấy bản ghi');
        }
        $validated = ShiftAssignment::validate($request->all(), false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $shift->update($request->all());
        return $this->success($shift, 'Cập nhật thành công');
    }

    public function createShiftAssignment(Request $request)
    {
        $validated = ShiftAssignment::validate($request->all(), false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $shift = ShiftAssignment::create($request->all());
        return $this->success($shift, 'Tạo thành công');
    }

    public function deleteShiftAssignment(Request $request)
    {
        $shifts = ShiftAssignment::whereIn('id', $request->all())->get();
        if (!count($shifts)) {
            return $this->failure('', 'Không tìm thấy bản ghi');
        }
        foreach ($shifts as $key => $value) {
            $value->delete();
        }
        return $this->success('', 'Xoá thành công');
    }
}
