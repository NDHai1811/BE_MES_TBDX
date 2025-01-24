<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\ErrorMachineExport;
use App\Helpers\QueryHelper;
use App\Models\ErrorLog;
use App\Models\ErrorMachine;
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
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ErrorMachineController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('error-machines/list', [ErrorMachineController::class, 'getErrorMachines']);
            Route::patch('error-machines/update', [ErrorMachineController::class, 'updateErrorMachine']);
            Route::post('error-machines/create', [ErrorMachineController::class, 'createErrorMachine']);
            Route::delete('error-machines/delete', [ErrorMachineController::class, 'deleteErrorMachines']);
            Route::get('error-machines/export', [ErrorMachineController::class, 'exportErrorMachines']);
            Route::post('error-machines/import', [ErrorMachineController::class, 'importErrorMachines']);
        });
    }

    public function getErrorMachines(Request $request)
    {
        $query = ErrorMachine::with('line')->has('line')->orderBy('created_at');
        if (isset($request->line_id)) {
            $query->where('line_id', $request->line_id);
        }
        if (isset($request->id)) {
            $query->where('id', 'like', "%" . $request->id . "%");
        }
        if (isset($request->ten_su_co)) {
            $query->where('ten_su_co', 'like', "%" . $request->ten_su_co . "%");
        }
        $records = $query->paginate($request->pageSize ?? null);
        $error_machines = $records->items();
        return $this->success(['data' => $error_machines, 'pagination' => QueryHelper::pagination($request, $records)]);
    }

    public function updateErrorMachine(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $validated = ErrorMachine::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $error = ErrorMachine::where('id', $input['id'])->first();
            if ($error) {
                $update = $error->update($input);
            } else {
                return $this->failure('', 'Không tìm thấy lỗi máy');
            }
            DB::commit();
            return $this->success($error);
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }

    public function createErrorMachine(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $validated = ErrorMachine::validateUpdate($input, false);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $error = ErrorMachine::create($input);
            DB::commit();
            return $this->success($error, 'Tạo thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }

    public function deleteErrorMachines(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            ErrorMachine::whereIn('id', $input)->delete();
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success('Xoá thành công');
    }

    public function exportMachines(Request $request)
    {
        # Set file path
        $timestamp = date('YmdHi');
        $file = "LỗiMáy_$timestamp.xlsx";
        $filePath = "export/$file";
        $result = Excel::store(new ErrorMachineExport(), $filePath, 'excel');

        if (empty($result))
            return $this->failure([], 'THAO TÁC THẤT BẠI', 500);
        # Generate file base64
        $fileContent = Storage::disk('excel')->get($filePath);
        $fileType = File::mimeType(storage_path("app/excel/$filePath"));
        $base64 = base64_encode($fileContent);
        $fileBase64Uri = "data:$fileType;base64,$base64";

        # Delete if needed
        Storage::disk('excel')->delete($filePath);

        # Return
        return $this->success([
            'file' => $file,
            'type' => $fileType,
            'data' => $fileBase64Uri,
        ]);
    }

    public function importMachines(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        try {
            Excel::import(new ErrorMachineExport, $request->file('file'));
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 'THỰC HIỆN THẤT BẠI');
        }

        return $this->success([], 'NHẬP DỮ LIỆU THÀNH CÔNG');
    }
}
