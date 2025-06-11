<?php
namespace App\Imports;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use App\Models\WareHouseMLTImport;
use App\Models\Supplier;
use App\Models\Material;
use App\Models\LocatorMLT;
use App\Models\LocatorMLTMap;
use App\Models\WarehouseMLTLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MaterialImport implements ToCollection, WithHeadingRow, WithStartRow{
    protected $fields;

    public function headingRow(): int
    {
        return 2;
    }

    public function startRow(): int
    {
        return 5;
    }

    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            $this->importRow($row->toArray());
        }
    }
    
    public function importRow(array $row){
        if(!$row['id'] || !$row['ma_cuon_ncc'] || !$row['ma_vat_tu']){
            Log::warning('Import: Bỏ qua dòng thiếu thông tin bắt buộc - ID: ' . ($row['id'] ?? 'null'));
            return;
        }

        try {
            DB::beginTransaction();
            
            // Chuẩn bị dữ liệu như createMaterial
            $input = [
                'id' => $row['id'],
                'ma_vat_tu' => $row['ma_vat_tu'],
                'ma_cuon_ncc' => $row['ma_cuon_ncc'],
                'so_kg' => $row['so_kg'] ?? 0,
                'loai_giay' => $row['loai_giay'] ?? '',
                'kho_giay' => $row['kho_giay'] ?? 0,
                'dinh_luong' => $row['dinh_luong'] ?? 0,
                'fsc' => isset($row['fsc']) && ($row['fsc'] === 'X' || $row['fsc'] === 1 || $row['fsc'] === '1') ? 1 : 0,
            ];

            // Validation cơ bản cho dữ liệu tính toán
            if (empty($input['so_kg']) || empty($input['kho_giay']) || empty($input['dinh_luong'])) {
                Log::warning('Import: Bỏ qua dòng thiếu dữ liệu tính toán - ID: ' . $row['id']);
                DB::rollBack();
                return;
            }

            // Tính toán so_m_toi như createMaterial
            $input['so_m_toi'] = floor(($input['so_kg'] / ($input['kho_giay'] / 100)) / ($input['dinh_luong'] / 1000));

            // Tạo/cập nhật Material
            $material = Material::updateOrCreate([
                'id' => $input['id'],
            ], $input);

            // Auto-create Supplier như createMaterial
            if (!empty($row['loai_giay'])) {
                Supplier::firstOrCreate(
                    ['id' => $row['loai_giay']], 
                    ['name' => $row['ten_ncc'] ?? $row['supplier_name'] ?? ""]
                );
            }

            // Xử lý locator từ WareHouseMLTImport (nếu có)
            if (!empty($row['ma_cuon_ncc'])) {
                $warehouse_import = WareHouseMLTImport::where('ma_cuon_ncc', $row['ma_cuon_ncc'])->first();
                if ($warehouse_import && !empty($warehouse_import->locator_id)) {
                    $locator = LocatorMLT::find($warehouse_import->locator_id);
                    if ($locator) {
                        // Tạo Locator Map
                        $locator_input = [
                            'locator_mlt_id' => $locator->id, 
                            'material_id' => $material->id
                        ];
                        LocatorMLTMap::updateOrCreate(['material_id' => $material->id], $locator_input);
                        
                        // Xử lý Warehouse Log như createMaterial
                        $log = WarehouseMLTLog::where('material_id', $material->id)
                            ->whereNull('tg_xuat')
                            ->orderBy('created_at', 'DESC')
                            ->first();
                            
                        if($log){
                            $log_input = [
                                'locator_id' => $locator->id, 
                                'material_id' => $material->id, 
                                'so_kg_nhap' => $material->so_kg,
                            ];
                            $log->update($log_input);
                        } else {
                            $log_input = [
                                'locator_id' => $locator->id, 
                                'material_id' => $material->id, 
                                'so_kg_nhap' => $material->so_kg, 
                                'tg_nhap' => date('Y-m-d H:i:s'),
                                'importer_id' => 1, // Default import user ID
                            ];
                            WarehouseMLTLog::create($log_input);
                        }
                    }
                }
            }

            DB::commit();
            
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Import: Lỗi import material ID ' . $row['id'] . ': ' . $th->getMessage());
            // Bỏ qua dòng lỗi và tiếp tục import
            return;
        }
    }
}