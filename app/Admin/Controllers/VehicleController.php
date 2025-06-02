<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\User;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\LocatorMLTMap;
use App\Models\Material;
use App\Models\Order;
use App\Models\Vehicle;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class VehicleController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('vehicles/list', [VehicleController::class, 'getVehicles']);
            Route::patch('vehicles/update', [VehicleController::class, 'updateVehicles']);
            Route::post('vehicles/create', [VehicleController::class, 'createVehicles']);
            Route::delete('vehicles/delete', [VehicleController::class, 'deleteVehicles']);
            Route::get('vehicles/export', [VehicleController::class, 'exportVehicles']);
            Route::post('vehicles/import', [VehicleController::class, 'importVehicles']);
        });
    }

    public function getVehicles(Request $request)
    {
        $query = Vehicle::with('driver', 'assistant_driver1', 'assistant_driver2');
        if(isset($request->id)){
            $query->where('id', 'like', "%$request->id%");
        }
        if(isset($request->driver_name)){
            $query->whereHas('driver', function($q) use($request){
                $q->where('name', 'like', "%$request->driver_name%");
            });
        }
        if(isset($request->assistant_driver1_name)){
            $query->whereHas('assistant_driver1', function($q) use($request){
                $q->where('name', 'like', "%$request->driver_name%");
            });
        }
        if(isset($request->assistant_driver2_name)){
            $query->whereHas('assistant_driver2', function($q) use($request){
                $q->where('name', 'like', "%$request->driver_name%");
            });
        }
        $records = $query->paginate($request->pageSize ?? null);
        $vehicles = $records->items();
        foreach ($vehicles as $key => $record) {
            $record->user1_name = $record->driver->name ?? "";
            $record->user1_username = $record->driver->username ?? "";
            $record->user1_phone_number = $record->driver->phone_number ?? "";
            $record->user2_name = $record->assistant_driver1->name ?? "";
            $record->user2_username = $record->assistant_driver1->username ?? "";
            $record->user2_phone_number = $record->assistant_driver1->phone_number ?? "";
            $record->user3_name = $record->assistant_driver2->name ?? "";
            $record->user3_username = $record->assistant_driver2->username ?? "";
            $record->user3_phone_number = $record->assistant_driver2->phone_number ?? "";
        }
        return $this->success($vehicles);
        return $this->success(['data' => $vehicles, 'pagination' => QueryHelper::pagination($request, $records)]);
    }
    public function updateVehicles(Request $request)
    {
        $input = $request->all();
        $vehicle = Vehicle::where('id', $input['id'])->first();
        if ($vehicle) {
            $update = $vehicle->update($input);
            if ($update) {
                isset($input['user1_phone_number']) && $vehicle->driver()->update(['phone_number' => $input['user1_phone_number']]);
                isset($input['user2_phone_number']) && $vehicle->assistant_driver1()->update(['phone_number' => $input['user2_phone_number']]);
                isset($input['user3_phone_number']) && $vehicle->assistant_driver2()->update(['phone_number' => $input['user3_phone_number']]);
                return $this->success($update);
            } else {
                return $this->failure('', 'Không thành công');
            }
        } else {
            return $this->failure('', 'Không tìm thấy xe');
        }
    }

    public function createVehicles(Request $request)
    {
        $input = $request->all();
        $check = Vehicle::find($input['id']);
        if($check) return $this->failure('', 'Số xe đã tồn tại trong hệ thống');
        $vehicle = Vehicle::create($input);
        isset($input['user1_phone_number']) && $vehicle->driver()->update(['phone_number' => $input['user1_phone_number']]);
        isset($input['user2_phone_number']) && $vehicle->assistant_driver1()->update(['phone_number' => $input['user2_phone_number']]);
        isset($input['user3_phone_number']) && $vehicle->assistant_driver2()->update(['phone_number' => $input['user3_phone_number']]);
        return $this->success($vehicle, 'Tạo thành công');
    }

    public function deleteVehicles(Request $request)
    {
        $input = $request->all();
        Vehicle::whereIn('id', $input)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportVehicles(Request $request)
    {
        try {
            $query = Vehicle::with('driver', 'assistant_driver1', 'assistant_driver2');
            if(isset($request->id)){
                $query->where('id', 'like', "%$request->id%");
            }
            if(isset($request->driver_name)){
                $query->whereHas('driver', function($q) use($request){
                    $q->where('name', 'like', "%$request->driver_name%");
                });
            }
            if(isset($request->assistant_driver1_name)){
                $query->whereHas('assistant_driver1', function($q) use($request){
                    $q->where('name', 'like', "%$request->driver_name%");
                });
            }
            if(isset($request->assistant_driver2_name)){
                $query->whereHas('assistant_driver2', function($q) use($request){
                    $q->where('name', 'like', "%$request->driver_name%");
                });
            }
            $records = $query->get()->all();
            // dd($records);
            // return response()->json($records);
            foreach ($records as $record) {
                $record->user1_name = $record->driver->name ?? "";
                $record->user1_username = $record->driver->username ?? "";
                $record->user1_phone_number = $record->driver->phone_number ?? "";
                $record->user2_name = $record->assistant_driver1->name ?? "";
                $record->user2_username = $record->assistant_driver1->username ?? "";
                $record->user2_phone_number = $record->assistant_driver1->phone_number ?? "";
                $record->user3_name = $record->assistant_driver2->name ?? "";
                $record->user3_username = $record->assistant_driver2->username ?? "";
                $record->user3_phone_number = $record->assistant_driver2->phone_number ?? "";
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
            $header = ['STT', 'Phương tiện', 'Tải trọng', 'Lái xe' => ['Họ và tên', 'MÃ NV', 'SĐT'], 'Phụ xe 1' => ['Họ và tên', 'MÃ NV', 'SĐT'], 'Phụ xe 2' => ['Họ và tên', 'MÃ NV', 'SĐT']];
            $table_key = [
                'A' => 'stt',
                'B' => 'id',
                'C' => 'weight',
                'D' => 'user1_name',
                'E' => 'user1_username',
                'F' => 'user1_phone_number',
                'G' => 'user2_name',
                'H' => 'user2_username',
                'I' => 'user2_phone_number',
                'J' => 'user3_name',
                'K' => 'user3_username',
                'L' => 'user3_phone_number',
            ];
            foreach ($header as $key => $cell) {
                if (!is_array($cell)) {
                    $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row + 1])->getStyle([$start_col, $start_row, $start_col, $start_row + 1])->applyFromArray($headerStyle);
                } else {
                    $sheet->setCellValue([$start_col, $start_row], $key)->mergeCells([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->getStyle([$start_col, $start_row, $start_col + count($cell) - 1, $start_row])->applyFromArray($headerStyle);
                    foreach ($cell as $val) {
                        $sheet->setCellValue([$start_col, $start_row + 1], $val)->getStyle([$start_col, $start_row + 1])->applyFromArray($headerStyle);
                        $start_col += 1;
                    }
                    continue;
                }
                $start_col += 1;
            }

            $sheet->setCellValue([1, 1], 'Danh sách xe')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
            $sheet->getRowDimension(1)->setRowHeight(40);
            $table_col = 1;
            $table_row = $start_row + 2;
            foreach ($records as $key => $row) {
                $table_col = 1;
                
                // Tạo array dữ liệu một cách rõ ràng thay vì cast object
                $rowData = [
                    'stt' => $key + 1,
                    'id' => $row->id,
                    'weight' => $row->weight,
                    'user1_name' => $row->driver->name ?? "",
                    'user1_username' => $row->driver->username ?? "",
                    'user1_phone_number' => $row->driver->phone_number ?? "",
                    'user2_name' => $row->assistant_driver1->name ?? "",
                    'user2_username' => $row->assistant_driver1->username ?? "",
                    'user2_phone_number' => $row->assistant_driver1->phone_number ?? "",
                    'user3_name' => $row->assistant_driver2->name ?? "",
                    'user3_username' => $row->assistant_driver2->username ?? "",
                    'user3_phone_number' => $row->assistant_driver2->phone_number ?? "",
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
            $fileName = "DanhSachXe_{$timestamp}.xlsx";
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function importVehicles(Request $request)
    {
        $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['file']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $vehicle = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key > 4) {
                $input = [];
                $input['id'] = $row['B'];
                $input['weight'] = $row['C'];
                $user1 = User::where('username', (int)$row['E'])->where('username', '<>', 'admin')->first();
                $input['user1'] = $user1->id ?? null;
                $user2 = User::where('username', (int)$row['H'])->where('username', '<>', 'admin')->first();
                $input['user2'] = $user2->id ?? null;
                $user3 = User::where('username', (int)$row['K'])->where('username', '<>', 'admin')->first();
                $input['user3'] = $user3->id ?? null;
                if ($input['id']) {
                    $vehicle[] = $input;
                }
            }
        }
        foreach ($vehicle as $key => $input) {
            Vehicle::create($input);
        }
        return $this->success([], 'Upload thành công');
    }

    public function num_to_letters($n)
    {
        $n -= 1;
        for ($r = ""; $n >= 0; $n = intval($n / 26) - 1)
            $r = chr($n % 26 + 0x41) . $r;
        return $r;
    }
}