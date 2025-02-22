<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\Machine;
use App\Models\Line;
use App\Models\Supplier;
use App\Models\TestCriteria;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class LineImport implements ToCollection, WithHeadingRow, WithStartRow
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
        if (!$row['id'] || !$row['name']) {
            return;
            // Log::debug($row);
        }
        $validated = Line::validate($row, $row['id']);
        if ($validated->fails()) {
            throw new \Exception($validated->errors()->first());
        }
        Line::updateOrCreate(['id' => $row['id']], ['name' => $row['name'], 'display' => $row['display'] == 'Có' ? 1 : 0]);
    }
}
