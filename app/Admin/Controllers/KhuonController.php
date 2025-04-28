<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\MoldExport;
use App\Helpers\QueryHelper;
use App\Imports\MoldImport;
use App\Models\Customer;
use App\Models\User;
use App\Models\ErrorLog;
use App\Models\Khuon;
use App\Models\KhuonLink;
use App\Models\Sheft;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;

class KhuonController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('molds/list', [KhuonController::class, 'getKhuon']);
            Route::patch('molds/update/{id}', [KhuonController::class, 'updateKhuon']);
            Route::post('molds/create', [KhuonController::class, 'createKhuon']);
            Route::delete('molds/delete/{id}', [KhuonController::class, 'deleteKhuon']);
            Route::get('molds/export', [KhuonController::class, 'exportKhuon']);
            Route::post('molds/import', [KhuonController::class, 'importKhuon']);
        });
    }

    public function khuonQuery(Request $request){
        $query = KhuonLink::orderBy('created_at', 'DESC')->orderBy('khuon_id');
        if (isset($request->khuon_id)) {
            $query->where('khuon_id', 'like', "%$request->khuon_id%");
        }
        if (isset($request->customer_id)) {
            $query->where('customer_id', 'like', "%$request->customer_id%");
        }
        if (isset($request->kich_thuoc)) {
            $query->where('kich_thuoc', 'like', "%$request->kich_thuoc%");
        }
        return $query;
    }

    public function getKhuon(Request $request)
    {
        $query = $this->khuonQuery($request);
        $records = $query->paginate($request->pageSize ?? null);
        $molds = $records->items();
        foreach ($molds as $value) {
            $value->designer_name = $value->designer->name ?? null;
        }
        return $this->success(['data' => $molds, 'pagination' => QueryHelper::pagination($request, $records)]);
    }
    public function updateKhuon(Request $request, $id)
    {
        $input = $request->all();
        $khuon = KhuonLink::where('id', $id)->first();
        if ($khuon) {
            $validated = KhuonLink::validate($input, true);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $input['phan_loai_1'] = Str::slug($input['phan_loai_1']);
            if(!empty($input['designer_name'])){
                $input['designer_id'] = User::where('name', 'like', "%" . trim($input['designer_name']) . "%")->first()->id ?? null;
            }
            $update = $khuon->update($input);
            return $this->success($khuon, 'Cập nhật thành công');
        } else {
            return $this->failure('', 'Không tìm thấy công đoạn');
        }
    }

    public function createKhuon(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $validated = KhuonLink::validate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $input['phan_loai_1'] = Str::slug($input['phan_loai_1']);
            if(!empty($input['designer_name'])){
                $input['designer_id'] = User::where('name', 'like', "%" . trim($input['designer_name']) . "%")->first()->id ?? null;
            }
            $khuon = KhuonLink::create($input);
            DB::commit();
            return $this->success($khuon, 'Tạo thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::commit();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Không thành công');
        }
    }

    public function deleteKhuon(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            KhuonLink::where('id', $id)->delete();
            DB::commit();
            return $this->success('Xoá thành công');
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('Xoá không thành công');
        }
    }

    public function importKhuon(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);

        try {
            Excel::import(new MoldImport, $request->file('file'));
        } catch (\Throwable $th) {
            return $this->failure($th->getMessage(), 'THỰC HIỆN THẤT BẠI');
        }

        return $this->success([], 'NHẬP DỮ LIỆU THÀNH CÔNG');
    }

    public function exportKhuon(Request $request)
    {
        # Set file path
        $timestamp = date('YmdHi');
        $file = "Khuon_$timestamp.xlsx";
        $filePath = "export/$file";
        $data = $this->khuonQuery($request)->get();
        $result = Excel::store(new MoldExport($data), $filePath, 'excel');

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
}
