<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\CustomerExport;
use App\Helpers\QueryHelper;
use App\Imports\CustomerImport;
use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\ErrorLog;
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

class CustomerController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('customer/list', [CustomerController::class, 'getCustomerByShortName']);
            Route::patch('customer/update/{id}', [CustomerController::class, 'updateCustomer']);
            Route::post('customer/create', [CustomerController::class, 'createCustomer']);
            Route::delete('customer/delete/{id}', [CustomerController::class, 'deleteCustomer']);
            Route::get('customer/export', [CustomerController::class, 'exportCustomer']);
            Route::post('customer/import', [CustomerController::class, 'importCustomer']);
            Route::get('real-customer-list', [CustomerController::class,'getCustomers']);
        });
    }

    function customerQuery(Request $request){
        $query = CustomerShort::with('customer')->orderBy('customer_id')->orderBy('short_name');
        if (isset($request->short_name)) {
            $query->where('short_name', 'like', "%$request->short_name%");
        }
        if (isset($request->name)) {
            $query->whereHas('customer', function($q) use($request){
                $q->where('name', 'like', "%$request->name%");
            });
        }
        if (isset($request->id)) {
            $query->where('customer_id', 'like', "%$request->id%");
        }
        return $query;
    }
    
    public function getCustomerByShortName(Request $request)
    {
        $query = $this->customerQuery($request);
        $records = $query->paginate($request->pageSize ?? null);
        $customer_short = $records->items();
        foreach ($customer_short as $customer) {
            $customer->name = $customer->customer->name ?? "";
        }
        return $this->success(['data'=>$customer_short, 'pagination' => QueryHelper::pagination($request, $records)]);
    }

    public function getCustomers(Request $request)
    {
        $query = Customer::orderBy('id');
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        if (isset($request->id)) {
            $query->where('id', 'like', "%$request->id%");
        }
        $records = $query->paginate($request->pageSize ?? null);
        $customers = $records->items();
        return $this->success(['data'=>$customers, 'pagination' => QueryHelper::pagination($request, $records)]);
    }

    public function updateCustomer(Request $request, $id)
    {
        $input = $request->all();
        $validated = CustomerShort::validate($input, $id);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $customer_short = CustomerShort::find($id);
        $customer = Customer::find($customer_short->customer_id);

        $new_customer_name = $request->name ?? null;
        $new_customer_short_name = $request->short_name ?? null;
        if ($customer && $customer_short) {
            $customer->name = $new_customer_name;
            $customer->save();
            $customer_short->short_name = $new_customer_short_name;
            $customer_short->save();
        } else {
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success([$customer_short, $customer], 'Cập nhật thành công');
    }

    public function createCustomer(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $customer = Customer::updateOrCreate(['id'=>$input['customer_id']], ['name'=>$input['name']]);
            if ($customer) {
                $short_name = CustomerShort::updateOrCreate(['customer_id'=>$customer->id, 'short_name'=>$input['short_name']]);
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
        return $this->success($customer, 'Tạo thành công');
    }

    public function deleteCustomer($id)
    {
        $customer_short = CustomerShort::find($id);
        $customer_id = $customer_short->customer_id;
        $list_customer_short = CustomerShort::all()->where('customer_id', $customer_id);
        if (!$customer_short) {
            return $this->failure('', 'Không tìm thấy');
        } else if (sizeof($list_customer_short) > 1) {
            $customer_short->delete();
        } else {
            $customer_short->delete();
            $customer = Customer::find($customer_id);
            $customer->delete();
        }
        return $this->success('Xoá thành công');
    }

    public function exportCustomer(Request $request)
    {
        # Set file path
        $timestamp = date('YmdHi');
        $file = "KhachHang_$timestamp.xlsx";
        $filePath = "export/$file";
        $data = $this->customerQuery($request)->get();
        $result = Excel::store(new CustomerExport($data), $filePath, 'excel');

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

    public function importCustomer(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        try {
            Excel::import(new CustomerImport, $request->file('file'));
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 'THỰC HIỆN THẤT BẠI');
        }

        return $this->success([], 'NHẬP DỮ LIỆU THÀNH CÔNG');
    }
}
