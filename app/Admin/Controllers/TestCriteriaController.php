<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\TestCriteriaExport;
use App\Helpers\QueryHelper;
use App\Imports\TestCriteriaImport;
use App\Models\ErrorLog;
use App\Models\TestCriteria;
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

class TestCriteriaController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('test_criteria/list', [TestCriteriaController::class, 'getTestCriteria']);
            Route::patch('test_criteria/update/{id}', [TestCriteriaController::class, 'updateTestCriteria']);
            Route::post('test_criteria/create', [TestCriteriaController::class, 'createTestCriteria']);
            Route::delete('test_criteria/delete/{id}', [TestCriteriaController::class, 'deleteTestCriteria']);
            Route::get('test_criteria/export', [TestCriteriaController::class, 'exportTestCriteria']);
            Route::post('test_criteria/import', [TestCriteriaController::class, 'importTestCriteria']);
        });
    }

    function queryTestCriteria(Request $request)
    {
        $query = TestCriteria::with('line')->orderBy('id')->whereNotNull('hang_muc')->where('hang_muc', '!=', '');
        if (isset($request->line_id)) {
            $query->where('line_id', $request->line_id);
        }
        if (isset($request->hang_muc)) {
            $query->where('hang_muc', 'like', "%$request->hang_muc%");
        }
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        return $query;
    }

    public function getTestCriteria(Request $request)
    {
        $query = $this->queryTestCriteria($request);
        $records = $query->paginate($request->pageSize ?? null);
        $test_criterias = $records->items();
        foreach ($test_criterias as $key => $test_criteria) {
            $test_criteria->line_name  = $test_criteria->line->name ?? "";
        }
        return $this->success(['data' => $test_criterias, 'pagination' => QueryHelper::pagination($request, $records)]);
    }
    public function updateTestCriteria(Request $request, $id)
    {
        $input = $request->all();
        $validated = TestCriteria::validate($input, $id);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $test_criteria = TestCriteria::where('id', $id)->first();
        if ($test_criteria) {
            $update = $test_criteria->update($input);
            return $this->success($test_criteria);
        } else {
            return $this->failure('', 'Không tìm thấy chỉ tiêu');
        }
    }

    public function createTestCriteria(Request $request)
    {
        $input = $request->all();
        $validated = TestCriteria::validate($input);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $test_criteria = TestCriteria::create($input);
        return $this->success($test_criteria, 'Tạo thành công');
    }

    public function deleteTestCriteria(Request $request, $id)
    {
        $test_criteria = TestCriteria::find($id);
        if (!$test_criteria) {
            return $this->failure('', 'Không tìm thấy chỉ tiêu');
        } else {
            $test_criteria->delete();
        }
        return $this->success('Xoá thành công');
    }

    public function exportTestCriteria(Request $request)
    {
        # Set file path
        $timestamp = date('YmdHi');
        $file = "ChiTieuKiemTra_$timestamp.xlsx";
        $filePath = "export/$file";
        $data = $this->queryTestCriteria($request)->get();
        $result = Excel::store(new TestCriteriaExport($data), $filePath, 'excel');

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

    public function importTestCriteria(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        try {
            Excel::import(new TestCriteriaImport, $request->file('file'));
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 'THỰC HIỆN THẤT BẠI');
        }

        return $this->success([], 'NHẬP DỮ LIỆU THÀNH CÔNG');
    }
}
