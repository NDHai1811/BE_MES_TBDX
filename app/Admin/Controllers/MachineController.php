<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\MachineExport;
use App\Helpers\QueryHelper;
use App\Imports\MachineImport;
use App\Models\Customer;
use App\Models\Layout;
use App\Models\Machine;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use App\Traits\API;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\Line;
use App\Models\MachineParameter;
use App\Models\Parameters;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class MachineController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('machines/list', [MachineController::class, 'getMachines']);
            Route::patch('machines/update', [MachineController::class, 'updateMachine']);
            Route::post('machines/create', [MachineController::class, 'createMachine']);
            Route::delete('machines/delete', [MachineController::class, 'deleteMachines']);
            Route::get('machines/export', [MachineController::class, 'exportMachines']);
            Route::post('machines/import', [MachineController::class, 'importMachines']);
        });
    }

    public function getMachines(Request $request)
    {
        $query = Machine::with('line')->orderBy('created_at')->whereNull('parent_id');
        if (isset($request->id)) {
            $query->where('id', 'like', "%$request->id%");
        }
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        $records = $query->paginate($request->pageSize ?? null);
        $machines = $records->items();
        return $this->success(['data' => $machines, 'pagination' => QueryHelper::pagination($request, $records)]);
    }
    public function updateMachine(Request $request)
    {
        $line_arr = [];
        $lines = Line::all();
        foreach ($lines as $line) {
            $line_arr[Str::slug($line->name)] = $line->id;
        }

        $input = $request->all();
        if (isset($line_arr[Str::slug($input['line'])])) {
            $input['line_id'] = $line_arr[Str::slug($input['line'])];
        }
        $validated = Machine::validateUpdate($input);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $machine = Machine::where('id', $input['id'])->first();
        if ($machine) {
            $update = $machine->update($input);
            return $this->success($machine);
        } else {
            return $this->failure('', 'Không tìm thấy máy');
        }
    }

    public function createMachine(Request $request)
    {
        $line_arr = [];
        $lines = Line::all();
        foreach ($lines as $line) {
            $line_arr[Str::slug($line->name)] = $line->id;
        }

        $input = $request->all();
        if (isset($line_arr[Str::slug($input['line'])])) {
            $input['line_id'] = $line_arr[Str::slug($input['line'])];
        }
        $validated = Machine::validateUpdate($input);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $machine = Machine::create($input);
        return $this->success($machine, 'Tạo thành công');
    }

    public function deleteMachines(Request $request)
    {
        $input = $request->all();
        Machine::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportMachines(Request $request)
    {
        # Set file path
        $timestamp = date('YmdHi');
        $file = "Máy_$timestamp.xlsx";
        $filePath = "export/$file";
        $result = Excel::store(new MachineExport(), $filePath, 'excel');

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
            Excel::import(new MachineImport, $request->file('file'));
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 'THỰC HIỆN THẤT BẠI');
        }

        return $this->success([], 'NHẬP DỮ LIỆU THÀNH CÔNG');
    }
}
