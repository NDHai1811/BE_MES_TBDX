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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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
    public function exportMaterials(Request $request){
        $query = Material::with('locator', 'supplier')
            ->select(['id', 'ma_vat_tu', 'ma_cuon_ncc', 'so_kg', 'loai_giay', 'kho_giay', 'dinh_luong', 'fsc'])
            ->orderByRaw('CHAR_LENGTH(id) DESC')
            ->orderBy('id', 'desc');
        
        // Apply filters
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
        
        $records = $query->get();
        
        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Không có dữ liệu để xuất'
            ]);
        }
        
        try {
            foreach($records as $record){
                $record->id = $record->id ?? "";
                $record->ma_vat_tu = $record->ma_vat_tu ?? "";
                $record->ten_nha_cung_cap = $record->supplier->name ?? "";
                $record->ma_cuon_ncc = $record->ma_cuon_ncc ?? "";
                $record->so_kg = $record->so_kg ?? "";
                $record->loai_giay = $record->loai_giay ?? "";
                $record->kho_giay = $record->kho_giay ?? "";
                $record->dinh_luong = $record->dinh_luong ?? "";
                $record->fsc =  $record->fsc == 1 ? "X" : "";
                $record->vi_tri = $record->locator->locator_mlt_id ?? "";
                $record->phan_loai = $record->vi_tri == "" ? "Chưa nhập kho" : "Đã nhập kho";
            }
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $start_row = 2;
            $start_col = 1;
            
            $centerStyle = [
                'alignment' => [
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'outline' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ];
            $headerStyle = array_merge($centerStyle, [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'BFBFBF'],
                ]
            ]);
            $titleStyle = array_merge($centerStyle, [
                'font' => ['size' => 16, 'bold' => true],
            ]);
            $border = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                        'color' => ['argb' => '000000'],
                    ],
                ],
            ];
            
            $header = ['STT', 'Mã cuộn TBDX', 'Mã vật tư', 'Tên nhà cung cấp', 'Mã cuộn NCC', 'Số kg', 'Loại giấy', 'Khổ giấy', 'Định lượng', 'FSC', 'Vị trí', 'Phân loại'];
            $table_key = [
                'A' => 'stt',
                'B' => 'id',
                'C' => 'ma_vat_tu',
                'D' => 'ten_nha_cung_cap',
                'E' => 'ma_cuon_ncc',
                'F' => 'so_kg',
                'G' => 'loai_giay',
                'H' => 'kho_giay',
                'I' => 'dinh_luong',
                'J' => 'fsc',
                'K' => 'vi_tri',
                'L' => 'phan_loai',
            ];
            
            foreach ($header as $key => $cell) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->getStyle([$start_col, $start_row])->applyFromArray($headerStyle);
                $start_col += 1;
            }

            $sheet->setCellValue([1, 1], 'Danh sách nguyên vật liệu')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
            $sheet->getRowDimension(1)->setRowHeight(40);
            
            $table_col = 1;
            $table_row = $start_row + 1;
            foreach ($records as $key => $row) {
                $table_col = 1;
                
                $rowData = [
                    'stt' => $key + 1,
                    'id' => $row->id,
                    'ma_vat_tu' => $row->ma_vat_tu,
                    'ten_nha_cung_cap' => $row->ten_nha_cung_cap,
                    'ma_cuon_ncc' => $row->ma_cuon_ncc,
                    'so_kg' => $row->so_kg,
                    'loai_giay' => $row->loai_giay,
                    'kho_giay' => $row->kho_giay,
                    'dinh_luong' => $row->dinh_luong,
                    'fsc' => $row->fsc,
                    'vi_tri' => $row->vi_tri,
                    'phan_loai' => $row->phan_loai,
                ];
                
                foreach ($table_key as $k => $value) {
                    if (isset($rowData[$value])) {
                        $sheet->setCellValue($k . $table_row, $rowData[$value])->getStyle($k . $table_row)->applyFromArray($centerStyle);
                    }
                    $table_col += 1;
                }
                $table_row += 1;
            }
            
            foreach ($sheet->getColumnIterator() as $column) {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
                $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
            }

            // ==== Lưu file và trả về base64 ====
            $timestamp = date('Ymd_His');
            $fileName = "DanhSachNguyenVatLieu_{$timestamp}.xlsx";
            $filePath = "export/$fileName";

            Storage::disk('excel')->makeDirectory('export');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save(storage_path("app/excel/$filePath"));

            $fileContent = Storage::disk('excel')->get($filePath);
            $fileType = File::mimeType(storage_path("app/excel/$filePath"));
            $base64 = base64_encode($fileContent);
            $fileBase64Uri = "data:$fileType;base64,$base64";

            Storage::disk('excel')->delete($filePath);

            return response()->json([
                'success' => true,
                'data' => [
                    'file' => $fileName,
                    'type' => $fileType,
                    'data' => $fileBase64Uri,
                ],
            ]);
            
        } catch(\Throwable $th) {
            ErrorLog::saveError($request, $th);
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}