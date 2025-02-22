<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\LineErrorExport;
use App\Helpers\QueryHelper;
use App\Imports\LineErrorImport;
use App\Models\Error;
use App\Models\Line;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ErrorController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('errors/list', [ErrorController::class, 'getErrors']);
            Route::patch('errors/update', [ErrorController::class, 'updateErrors']);
            Route::post('errors/create', [ErrorController::class, 'createErrors']);
            Route::delete('errors/delete', [ErrorController::class, 'deleteErrors']);
            Route::get('errors/export', [ErrorController::class, 'exportErrors']);
            Route::post('errors/import', [ErrorController::class, 'importErrors']);
        });
    }

    function errorQuery($request)
    {
        $query = Error::with('line')->orderBy('created_at', 'DESC');
        if (isset($request->id)) {
            $query->where('id', 'like', "%$request->id%");
        }
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        return $query;
    }

    public function getErrors(Request $request){
        $query = $this->errorQuery($request);
        $records = $query->paginate($request->pageSize ?? null);
        $errors = $records->items();
        return $this->success(['data' => $errors, 'pagination' => QueryHelper::pagination($request, $records)]);
    }
    public function updateErrors(Request $request){
        $line_arr = [];
        $lines = Line::all();
        foreach($lines as $line){
            $line_arr[Str::slug($line->name)] = $line->id;
        }

        $input = $request->all();
        $validated = Error::validateUpdate($input);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $error = Error::where('id', $input['id'])->first();
        if($error){
            $update = $error->update($input);
            return $this->success($error);
        }
        else{
            return $this->failure('', 'Không tìm thấy lỗi');
        }
    }

    public function createErrors(Request $request){
        $line_arr = [];
        $lines = Line::all();
        foreach($lines as $line){
            $line_arr[Str::slug($line->name)] = $line->id;
        }

        $input = $request->all();
        $validated = Error::validateUpdate($input, false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $error = Error::create($input);
        return $this->success($error, 'Tạo thành công');
    }

    public function deleteErrors(Request $request){
        $input = $request->all();
        Error::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportErrors(Request $request)
    {
        # Set file path
        $timestamp = date('YmdHi');
        $file = "LoiCongDoan_$timestamp.xlsx";
        $filePath = "export/$file";
        $data = $this->errorQuery($request)->get();
        $result = Excel::store(new LineErrorExport($data), $filePath, 'excel');

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

    public function importErrors(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        try {
            Excel::import(new LineErrorImport, $request->file('file'));
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 'THỰC HIỆN THẤT BẠI');
        }

        return $this->success([], 'NHẬP DỮ LIỆU THÀNH CÔNG');
    }
}
