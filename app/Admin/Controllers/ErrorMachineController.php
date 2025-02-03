<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\ErrorMachineExport;
use App\Helpers\QueryHelper;
use App\Imports\ErrorMachineImport;
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
            Route::patch('error-machines/update/{id}', [ErrorMachineController::class, 'updateErrorMachine']);
            Route::post('error-machines/create', [ErrorMachineController::class, 'createErrorMachine']);
            Route::delete('error-machines/delete/{id}', [ErrorMachineController::class, 'deleteErrorMachines']);
            Route::get('error-machines/export', [ErrorMachineController::class, 'exportErrorMachines']);
            Route::post('error-machines/import', [ErrorMachineController::class, 'importErrorMachines']);
        });
    }

    function queryErrorMachine($request)
    {
        $query = ErrorMachine::with('line')->orderBy('id');
        if (isset($request->line_id)) {
            $query->where('line_id', $request->line_id);
        }
        if (isset($request->id)) {
            $query->where('id', 'like', "%" . $request->id . "%");
        }
        if (isset($request->ten_su_co)) {
            $query->where('ten_su_co', 'like', "%" . $request->ten_su_co . "%");
        }
        return $query;
    }

    public function getErrorMachines(Request $request)
    {
        $query = $this->queryErrorMachine($request);
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

    public function updateErrorMachine(Request $request, $id)
    {
        $input = $request->all();
        $validated = ErrorMachine::validate($input, $id);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $error = ErrorMachine::find($id);
        if ($error) {
            $error->update($input);
        } else {
            return $this->failure('', 'Không tìm thấy lỗi máy');
        }
        return $this->success($error, 'Cập nhật thành công');
    }

    public function createErrorMachine(Request $request)
    {
        $input = $request->all();
        $validated = ErrorMachine::validate($input);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $error = ErrorMachine::create($input);
        return $this->success($error, 'Tạo thành công');
    }

    public function deleteErrorMachines(Request $request, $id)
    {
        $error = ErrorMachine::find($id);
        if ($error) {
            $error->delete();
        } else {
            return $this->failure('', 'Không tìm thấy lỗi máy');
        }
        return $this->success('Xoá thành công');
    }

    public function exportErrorMachines (Request $request)
    {
        # Set file path
        $timestamp = date('YmdHi');
        $file = "LỗiMáy_$timestamp.xlsx";
        $filePath = "export/$file";
        $data = $this->queryErrorMachine($request)->get();
        $result = Excel::store(new ErrorMachineExport($data), $filePath, 'excel');

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

    public function importErrorMachines(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        try {
            Excel::import(new ErrorMachineImport, $request->file('file'));
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 'THỰC HIỆN THẤT BẠI');
        }

        return $this->success([], 'NHẬP DỮ LIỆU THÀNH CÔNG');
    }
}
