<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\ErrorLog;
use App\Models\LocatorFGMap;
use App\Models\LocatorMLT;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\Supplier;
use App\Models\WareHouseMLTImport;
use App\Models\WarehouseMLTLog;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class MaterialController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('material/list', [MaterialController::class, 'getMaterials']);
            Route::patch('material/update/{id}', [MaterialController::class, 'updateMaterial']);
            Route::post('material/create', [MaterialController::class, 'createMaterial']);
            Route::delete('material/delete/{id}', [MaterialController::class, 'deleteMaterials']);
            Route::get('material/export', [MaterialController::class, 'exportMaterials']);
            Route::post('material/import', [MaterialController::class, 'importMaterials']);
        });
    }

    public function getMaterials(Request $request)
    {
        $page = $request->page - 1;
        $pageSize = $request->pageSize;
        $query = Material::with('locator')->orderByRaw('CHAR_LENGTH(id) DESC')->orderBy('id', 'desc');
        if (isset($request->loai_giay)) {
            $query->where('loai_giay', 'like', "%$request->loai_giay%");
        }
        if (isset($request->ma_cuon_ncc)) {
            $query->where('ma_cuon_ncc', 'like', "%$request->ma_cuon_ncc%");
        }
        if (isset($request->id)) {
            $query->where('id', 'like', "%$request->id%");
        }
        if (isset($request->phan_loai)) {
            if ($request->phan_loai == 1) {
                $query->has('locator');
            } else if ($request->phan_loai == 0) {
                $query->doesntHave('locator');
            }
        }
        if (isset($request->locator_id)) {
            $query->whereHas('locator', function ($q) use ($request) {
                $q->where('locator_mlt_id', 'like', "%$request->locator_id%");
            });
        }
        $records = $query->paginate($request->pageSize ?? null);
        $materials = $records->items();

        foreach ($materials as $material) {
            $material->fsc = $material->fsc ? "X" : "";
            $material->ten_ncc = $material->supplier->name ?? "";
            $material->locator_id = $material->locator->locator_mlt_id ?? "";
        }
        return $this->success(['data' => $materials, 'pagination' => QueryHelper::pagination($request, $records)]);
    }
    public function updateMaterial(Request $request)
    {
        $input = $request->all();
        $material = Material::where('id', $input['key'])->first();
        if ($material) {
            try {
                DB::beginTransaction();
                $input['fsc'] = isset($input['fsc']) ? 1 : 0;
                $validated = Material::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', $validated->errors()->first());
                }
                $input['so_m_toi'] = floor(($input['so_kg'] / ($input['kho_giay'] / 100)) / ($input['dinh_luong'] / 1000));
                $material->update($input);
                Supplier::firstOrCreate(['id' => $input['loai_giay']], ['name' => $input['supplier_name'] ?? ""]);
                if (isset($input['locator_id'])) {
                    $locator = LocatorMLT::find($input['locator_id']);
                    if (!$locator) {
                        return $this->failure('', 'Vị trí không phù hợp');
                    } else {
                        $locator_input = ['locator_mlt_id' => $locator->id, 'material_id' => $material->id];
                        LocatorMLTMap::updateOrCreate(['material_id' => $material->id], $locator_input);
                        $log = WarehouseMLTLog::where('material_id', $material->id)->whereNull('tg_xuat')->orderBy('created_at', 'DESC')->first();
                        if($log){
                            $log_input = [
                                'locator_id' => $locator->id, 
                                'material_id' => $material->id, 
                                'so_kg_nhap'=>$material->so_kg,
                            ];
                            $log->update($log_input);
                        }else{
                            $log_input = [
                                'locator_id' => $locator->id, 
                                'material_id' => $material->id, 
                                'so_kg_nhap'=>$material->so_kg, 
                                'tg_nhap'=>date('Y-m-d H:i:s'),
                                'importer_id'=>$request->user()->id,
                            ];
                            WarehouseMLTLog::create($log_input);
                        }
                        
                    }
                }
                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                ErrorLog::saveError($request, $th);
                return $this->failure($th, 'Đã xảy ra lỗi');
            }
        } else {
            return $this->failure('', 'Không tìm thấy nguyên vật liệu');
        }
        return $this->success($material, 'Cập nhật thành công');
    }

    public function createMaterial(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            $input['fsc'] = isset($input['fsc']) ? 1 : 0;
            $validated = Material::validateUpdate($input, false);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $input['so_m_toi'] = floor(($input['so_kg'] / ($input['kho_giay'] / 100)) / ($input['dinh_luong'] / 1000));
            $material = Material::create($input);
            Supplier::firstOrCreate(['id' => $input['loai_giay']], ['name' => $input['supplier_name'] ?? ""]);
            if (isset($input['locator_id'])) {
                $locator = LocatorMLT::find($input['locator_id']);
                if (!$locator) {
                    return $this->failure('', 'Vị trí không phù hợp');
                } else {
                    $locator_input = ['locator_mlt_id' => $locator->id, 'material_id' => $material->id];
                    LocatorMLTMap::updateOrCreate(['material_id' => $material->id], $locator_input);
                    $log = WarehouseMLTLog::where('material_id', $material->id)->whereNull('tg_xuat')->orderBy('created_at', 'DESC')->first();
                    if($log){
                        $log_input = [
                            'locator_id' => $locator->id, 
                            'material_id' => $material->id, 
                            'so_kg_nhap'=>$material->so_kg,
                        ];
                        $log->update($log_input);
                    }else{
                        $log_input = [
                            'locator_id' => $locator->id, 
                            'material_id' => $material->id, 
                            'so_kg_nhap'=>$material->so_kg, 
                            'tg_nhap'=>date('Y-m-d H:i:s'),
                            'importer_id'=>$request->user()->id,
                        ];
                        WarehouseMLTLog::create($log_input);
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success($material, 'Tạo thành công');
    }

    public function deleteMaterials(Request $request)
    {
        try {
            DB::beginTransaction();
            $input = $request->all();
            foreach ($input as $material_id) {
                $log = WarehouseMLTLog::where('material_id', $material_id)->first();
                if ($log) return $this->failure('', 'Cuộn ' . $material_id . ' đã vào sản xuất');
                Material::where('id', $material_id)->delete();
                WareHouseMLTImport::whereIn('material_id', $material_id)->delete();
                LocatorMLTMap::where('material_id', $material_id)->delete();
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure($th, 'Đã xảy ra lỗi');
        }
        return $this->success('Xoá thành công');
    }
}
