<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\Machine;
use App\Models\Line;
use App\Models\TestCriteria;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CustomerImport implements ToCollection, WithHeadingRow, WithStartRow
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
        if (!$row['customer_id'] || !$row['customer_name'] || !$row['short_name']) {
            return;
            // Log::debug($row);
        }

        Customer::firstOrCreate(['id'=>$row['customer_id']], ['name'=>$row['customer_name']]);

        CustomerShort::updateOrCreate(['customer_id'=>$row['customer_id']], ['short_name'=>$row['short_name']]);
    }
}
