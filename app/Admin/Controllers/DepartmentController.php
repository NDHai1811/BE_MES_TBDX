<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use App\Models\CustomUser;
use App\Models\Department;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\Order;
use App\Models\Vehicle;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class DepartmentController extends Controller
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('departments/list', [DepartmentController::class, 'index']);
            Route::post('departments/create', [DepartmentController::class, 'create']);
            Route::delete('departments/delete', [DepartmentController::class, 'delete']);
            Route::patch('departments/update', [DepartmentController::class, 'update']);
        });
    }

    public function index(Request $request){
        $query = Department::orderBy('created_at');
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $records = $query->paginate($request->pageSize ?? null);
        $departments = $records->items();
        return $this->success(['data' => $departments, 'pagination' => QueryHelper::pagination($request, $records)]);
    }

    public function create(Request $request){
        $deparrtment = Department::create($request->all());
        return $this->success($deparrtment);
    }

    public function update(Request $request){
        $deparrtment = Department::where('id', $request->id)->update($request->all());
        return $this->success($deparrtment);
    }

    public function delete(Request $request){
        $input = $request->all();
        $deparrtment = Department::whereIn('id', $input)->delete();
        return $this->success($deparrtment);
    }
}
