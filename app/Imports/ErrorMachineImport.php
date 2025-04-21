<?php

namespace App\Imports;

use App\Models\ErrorMachine;
use App\Models\Machine;
use App\Models\Line;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ErrorMachineImport implements ToCollection, WithHeadingRow, WithStartRow
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
        if (!$row['id'] || !$row['ten_su_co'] || !$row['line_name']) {
            return; // Bỏ qua hàng nếu tên thiết bị không tồn tại
            // Log::debug($row);
        }

        $line = Line::where(['name' => $row['line_name']])->first();
        if(!$line){
            throw new \Exception('Không tìm thấy công đoạn: ' . $row['line_name']);
        }

        $lineId = $line->id;

        // Tạo Error Machine
        $machine = ErrorMachine::updateOrCreate([
            'id' => $row['id'],
        ], [
            'ten_su_co' => $row['ten_su_co'],
            'line_id' => $lineId,
            'nguyen_nhan' => $row['nguyen_nhan'] ?? null,
            'cach_xu_ly' => $row['cach_xu_ly'] ?? null,
        ]);
    }
}
