<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\KhuonLink;
use App\Models\Machine;
use App\Models\Line;
use App\Models\Supplier;
use App\Models\TestCriteria;
use App\Models\User;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MoldImport implements ToCollection, WithHeadingRow, WithStartRow
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
        if (!$row['khuon_id']) {
            return;
            // Log::debug($row);
        }
        $validated = KhuonLink::validate($row);
        if ($validated->fails()) {
            throw new \Exception($validated->errors()->first());
        }
        $designer = User::where('name', $row['designer_name'])->first();
        if($designer){
            $row['designer_id'] = $designer->id;
        } else {
            $row['designer_id'] = null;
        }
        KhuonLink::updateOrCreate(['khuon_id' => $row['khuon_id']], $row);
    }
}
