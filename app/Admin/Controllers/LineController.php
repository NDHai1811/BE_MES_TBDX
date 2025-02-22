<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\LineExport;
use App\Helpers\QueryHelper;
use App\Imports\LineImport;
use App\Models\Line;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class LineController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('cong-doan/list', [LineController::class, 'getLine']);
            Route::patch('cong-doan/update/{id}', [LineController::class, 'updateLine']);
            Route::post('cong-doan/create', [LineController::class, 'createLine']);
            Route::delete('cong-doan/delete/{id}', [LineController::class, 'deleteLine']);
            Route::get('cong-doan/export', [LineController::class, 'exportLine']);
            Route::post('cong-doan/import', [LineController::class, 'importLine']);
        });
    }

    function lineQuery(Request $request)
    {
        $query = Line::orderBy('ordering');
        if (isset($request->line)) {
            $query->where('name', 'like', "%$request->line%");
        }
        return $query;
    }

    public function getLine(Request $request)
    {
        $query = $this->lineQuery($request);
        $records = $query->paginate($request->pageSize ?? null);
        return $this->success(['data' => $records->items(), 'pagination' => QueryHelper::pagination($request, $records)]);
    }
    public function updateLine(Request $request, $id)
    {
        $input = $request->all();
        $line = Line::where('id', $id)->first();
        if ($line) {
            $update = $line->update($input);
            if ($update) {
                return $this->success($line);
            } else {
                return $this->failure('', 'Không thành công');
            }
        } else {
            return $this->failure('', 'Không tìm thấy công đoạn');
        }
    }

    public function createLine(Request $request)
    {
        $input = $request->all();
        $line = Line::create($input);
        return $this->success($line, 'Tạo thành công');
    }

    public function deleteLine(Request $request, $id)
    {
        $input = $request->all();
        Line::where('id', $id)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportLine(Request $request)
    {
        # Set file path
        $timestamp = date('YmdHi');
        $file = "CongDoan_$timestamp.xlsx";
        $filePath = "export/$file";
        $data = $this->lineQuery($request)->get();
        $result = Excel::store(new LineExport($data), $filePath, 'excel');

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

    public function importLine(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        try {
            Excel::import(new LineImport, $request->file('file'));
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 'THỰC HIỆN THẤT BẠI');
        }

        return $this->success([], 'NHẬP DỮ LIỆU THÀNH CÔNG');
    }
}
