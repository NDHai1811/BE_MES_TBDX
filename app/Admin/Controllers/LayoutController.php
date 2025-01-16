<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\Buyer;
use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\Error;
use App\Models\ErrorLog;
use App\Models\Layout;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use App\Models\Line;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use stdClass;

class LayoutController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('layouts/list', [LayoutController::class, 'listLayout']);
            Route::post('layouts/create', [ApiController::class, 'createLayouts']);
            Route::patch('layouts/update', [ApiController::class, 'updateLayouts']);
            Route::delete('layouts/delete', [ApiController::class, 'deleteLayouts']);
        });
    }

    public function listLayout(Request $request)
    {
        $query = Layout::orderBy('created_at', 'DESC');
        if ($request->layout_id) {
            $query = $query->where('layout_id', 'like', '%' . $request->layout_id . '%');
        }
        if ($request->customer_id) {
            $query = $query->where('customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if ($request->machine_id) {
            $query = $query->where('machine_id', 'like', '%' . $request->machine_id . '%');
        }
        $records = $query->paginate($request->pageSize ?? PHP_INT_MAX);
        $layouts = $records->items();
        return $this->success(['data' => $layouts, 'pagination' => QueryHelper::pagination($request, $records)]);
    }

    public function createLayouts(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $record = Layout::create($input);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success($record, 'Thêm mới thành công');
    }

    public function updateLayouts(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $record = Layout::find($input['id'])->update($input);
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success($record, 'Cập nhật thành công');
    }

    public function deleteLayouts(Request $request)
    {
        try {
            DB::beginTransaction();
            Layout::where('id', $request->id)->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success([], 'Xóa thành công');
    }
}
