<?php

namespace App\Imports;

use App\Models\Machine;
use App\Models\Line;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RoleImport implements ToCollection, WithHeadingRow, WithStartRow
{
    protected $fields;

    // Hàm này xác định hàng bắt đầu lấy tiêu đề (heading row)
    public function headingRow(): int
    {
        return 2;
    }

    // Hàm này xác định hàng bắt đầu lấy dữ liệu (data row)
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

    protected function importRow(array $row)
    {
        if (!$row['id'] || !$row['name'] || !$row['line_name']) {
            return; // Bỏ qua hàng nếu tên thiết bị không tồn tại
            // Log::debug($row);
        }
//
//        $line = Line::where(['name' => $row['line_name']])->first();
//        if(!$line){
//            throw new \Exception('Không tìm thấy công đoạn: ' . $row['line_name']);
//        }
//
//        $lineId = $line->id;
//
//        // Tạo Machine
//        $machine = Machine::updateOrCreate([
//            'id' => $row['id'],
//        ], [
//            'machine_name' => $row['name'],
//            'line_id' => $lineId,
//            'kieu_loai' => $row['kieu_loai'] ?? null,
//            'device_id' => $row['device_id'] ?? null,
//            'is_iot' => $row['is_iot'] == 'Có' ? 1 : 0,
//        ]);


    }
}
