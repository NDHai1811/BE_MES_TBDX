<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\CustomerExport;
use App\Exports\MasterData\SupplierExport;
use App\Helpers\QueryHelper;
use App\Imports\CustomerImport;
use App\Imports\SupplierImport;
use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\ErrorLog;
use App\Models\Supplier;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Str;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('supplier/list', [SupplierController::class, 'getSuppliers']);
            Route::patch('supplier/update/{id}', [SupplierController::class, 'updateSupplier']);
            Route::post('supplier/create', [SupplierController::class, 'createSupplier']);
            Route::delete('supplier/delete/{id}', [SupplierController::class, 'deleteSupplier']);
            Route::get('supplier/export', [SupplierController::class, 'exportSupplier']);
            Route::post('supplier/import', [SupplierController::class, 'importSupplier']);
        });
    }

    function supplierQuery(Request $request){
        $query = Supplier::orderBy('name');
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        if (isset($request->id)) {
            $query->where('id', 'like', "%$request->id%");
        }
        return $query;
    }
    
    public function getSuppliers(Request $request)
    {
        $query = $this->supplierQuery($request);
        $records = $query->paginate($request->pageSize ?? null);
        $suppliers = $records->items();
        return $this->success(['data'=>$suppliers, 'pagination' => QueryHelper::pagination($request, $records)]);
    }

    public function updateSupplier(Request $request, $id)
    {
        $input = $request->all();
        $validated = Supplier::validate($input, $id);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $supplier = Supplier::where('id', $id)->first();
        if ($supplier) {
            $update = $supplier->update($input);
        } else {
            return $this->failure('', 'Không tìm thấy nhà cung cấp');
        }
        return $this->success($supplier, 'Cập nhật thành công');
    }

    public function createSupplier(Request $request)
    {
        $input = $request->all();
        $validated = Supplier::validate($input);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $supplier = Supplier::create($input);
        return $this->success($supplier, 'Tạo thành công');
    }

    public function deleteSupplier(Request $requestm, $id)
    {
        $supplier = Supplier::find($id);
        if (!$supplier) {
            return $this->failure('', 'Không tìm thấy nhà cung cấp');
        } else {
            $supplier->delete();
        }
        return $this->success('Xoá thành công');
    }

    public function exportSupplier(Request $request)
    {
        # Set file path
        $timestamp = date('YmdHi');
        $file = "NhaCungCap_$timestamp.xlsx";
        $filePath = "export/$file";
        $data = $this->supplierQuery($request)->get();
        if ($data->isEmpty()){
            return $this->failure([], 'Không có dữ liệu để xuất', 404);
        }
        $result = Excel::store(new SupplierExport($data), $filePath, 'excel');

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

    public function importSupplier(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        try {
            Excel::import(new SupplierImport, $request->file('file'));
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 'THỰC HIỆN THẤT BẠI');
        }

        return $this->success([], 'NHẬP DỮ LIỆU THÀNH CÔNG');
    }
}