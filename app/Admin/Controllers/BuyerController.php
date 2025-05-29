<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\Buyer;
use App\Models\Customer;
use App\Models\CustomerShort;
use App\Models\Error;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use App\Models\Line;
use Illuminate\Support\Facades\Route;
use stdClass;

class BuyerController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('buyers/list', [BuyerController::class, 'listBuyer']);
            Route::patch('buyers/update', [BuyerController::class, 'updateBuyers']);
            Route::post('buyers/create', [BuyerController::class, 'createBuyers']);
            Route::delete('buyers/delete', [BuyerController::class, 'deleteBuyers']);
            Route::get('buyers/export', [BuyerController::class, 'exportBuyers']);
        });
    }

    public function listBuyer(Request $request)
    {
        $query = Buyer::with('customershort')->orderBy('created_at', 'DESC');
        if ($request->customer_id) {
            $query = $query->where('customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if ($request->customer_name) {
            $customer_ids = CustomerShort::where('short_name', 'like', '%' . $request->customer_name . '%')->pluck('customer_id')->toArray();
            $query = $query->whereIn('customer_id', $customer_ids);
        }
        if ($request->so_lop) {
            $query = $query->where('so_lop', $request->so_lop);
        }
        if ($request->phan_loai_1) {
            $query = $query->where('phan_loai_1', $request->phan_loai_1);
        }
        if ($request->id) {
            $query = $query->where('id', 'like', '%' . $request->id . '%');
        }
        if ($request->ma_cuon_f) {
            $query = $query->where('ma_cuon_f', 'like', '%' . $request->ma_cuon_f . '%');
        }
        if ($request->ma_cuon_se) {
            $query = $query->where('ma_cuon_se', 'like', '%' . $request->ma_cuon_se . '%');
        }
        if ($request->ma_cuon_le) {
            $query = $query->where('ma_cuon_le', 'like', '%' . $request->ma_cuon_le . '%');
        }
        if ($request->ma_cuon_sb) {
            $query = $query->where('ma_cuon_sb', 'like', '%' . $request->ma_cuon_sb . '%');
        }
        if ($request->ma_cuon_lb) {
            $query = $query->where('ma_cuon_lb', 'like', '%' . $request->ma_cuon_lb . '%');
        }
        if ($request->ma_cuon_sc) {
            $query = $query->where('ma_cuon_sc', 'like', '%' . $request->ma_cuon_sc . '%');
        }
        if ($request->ma_cuon_lc) {
            $query = $query->where('ma_cuon_lc', 'like', '%' . $request->ma_cuon_lc . '%');
        }
        $records = $query->paginate($request->pageSize ?? PHP_INT_MAX);
        $buyers = $records->items();
        $arr = ['S0105' => ['ma_cuon_f'], 'S0104' => ['ma_cuon_se', 'ma_cuon_le'], 'S0103' => ['ma_cuon_sb', 'ma_cuon_lb'], 'S0102' => ['ma_cuon_sc', 'ma_cuon_lc']];
        $position = ['ma_cuon_f' => 'S010501', 'ma_cuon_se' => 'S010401', 'ma_cuon_le' => 'S010402', 'ma_cuon_sb' => 'S010301', 'ma_cuon_lb' => 'S010302', 'ma_cuon_sc' => 'S010201', 'ma_cuon_lc' => 'S010202'];
        foreach ($buyers as $k => $record) {
            $mapping = [];
            $result = $record->toArray();
            foreach ($arr as $ke => $value) {
                $obj = new stdClass();
                $obj->label = ['Vị trí', 'Mã cuộn'];
                $obj->key = ['vi_tri', 'ma_cuon'];
                $in = [];
                foreach ($value as $key => $val) {
                    if ($result[$val]) {
                        $in[] = $position[$val];
                    }
                }
                $obj->position = $in;
                if (count($in) > 0) {
                    $mapping[$ke] = $obj;
                }
            }
            $record->mapping = json_encode($mapping);
            $record->save();
        }
        return $this->success(['data' => $buyers, 'pagination' => QueryHelper::pagination($request, $records)]);
    }

    public function createBuyers(Request $request)
    {
        $input = $request->all();
        $record = Buyer::create($input);
        return $this->success($record, 'Cập nhật thành công');
    }

    public function updateBuyers(Request $request)
    {
        $input = $request->all();
        $record = Buyer::find($input['id'])->update($input);
        return $this->success($record, 'Cập nhật thành công');
    }

    public function deleteBuyers(Request $request)
    {
        Buyer::where('id', $request->id)->delete();
        return $this->success([], 'Xóa thành công');
    }

    public function exportBuyers(Request $request)
    {
        $query = Buyer::orderBy('created_at', 'DESC');
        if ($request->customer_id) {
            $query = $query->where('customer_id', 'like', '%' . $request->customer_id . '%');
        }
        if ($request->customer_name) {
            $customer_ids = Customer::where('name', 'like', '%' . $request->customer_name . '%')->pluck('id')->toArray();
            $query = $query->whereIn('customer_id', $customer_ids);
        }
        if ($request->so_lop) {
            $query = $query->where('so_lop', $request->so_lop);
        }
        if ($request->phan_loai_1) {
            $query = $query->where('phan_loai_1', $request->phan_loai_1);
        }
        if ($request->id) {
            $query = $query->where('id', 'like', '%' . $request->id . '%');
        }
        $records = $query->get()->map(function ($record, $index) {
            return [
                $index + 1,
                $record->id,
                $record->customer_id,
                $record->buyer_vt,
                $record->phan_loai_1,
                $record->so_lop,
                $record->ma_cuon_f,
                $record->ma_cuon_se,
                $record->ma_cuon_le,
                $record->ma_cuon_sb,
                $record->ma_cuon_lb,
                $record->ma_cuon_sc,
                $record->ma_cuon_lc,
                $record->ket_cau_giay,
                $record->ghi_chu
            ];
        })->toArray();
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'borders' => array(
                'outline' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $headerStyle = array_merge($centerStyle, [
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => array('argb' => 'BFBFBF')
            ]
        ]);
        $titleStyle = array_merge($centerStyle, [
            'font' => ['size' => 16, 'bold' => true],
        ]);
        $border = [
            'borders' => array(
                'allBorders' => array(
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => array('argb' => '000000'),
                ),
            ),
        ];
        $header = [
            'STT',
            'Mã buyer',
            'Mã khách hàng',
            'Buyer viết tắt',
            'Phân loại 1',
            'Số lớp',
            'Mặt',
            'Sóng E sóng',
            'Sóng E láng',
            "Sóng B sóng",
            'Sóng B láng',
            'Sóng C sóng',
            'Sóng C đáy',
            'Kết cấu chạy giấy',
            'Ghi chú'
        ];
        $table_key = [
            'A' => 'stt',
            'B' => 'buyer_id',
            'C' => 'customer_id',
            'D' => 'buyer_vt',
            'E' => 'phan_loai_1',
            'F' => 'so_lop',
            'G' => 'ma_cuon_f',
            'H' => 'ma_cuon_se',
            'I' => 'ma_cuon_le',
            'J' => 'ma_cuon_sb',
            'K' => 'ma_cuon_lb',
            'L' => 'ma_cuon_sc',
            'M' => 'ma_cuon_lc',
            'N' => 'ket_cau_giay',
            'O' => 'ghi_chu',
        ];
        foreach ($header as $key => $cell) {
            $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            $start_col += 1;
        }
        $sheet->setCellValue([1, 1], 'Danh sách Buyer')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        $sheet->fromArray($records, null, 'A3');
        $sheet->getStyle([1, $table_row, $start_col - 1, count($records) + $table_row - 1])->applyFromArray(
            array_merge(
                $centerStyle,
                array(
                    'borders' => array(
                        'allBorders' => array(
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => array('argb' => '000000'),
                        ),
                    )
                )
            )
        );
        foreach ($sheet->getColumnIterator() as $column) {
            $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Danh sách buyer.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Danh sách buyer.xlsx');
        $href = '/exported_files/Danh sách buyer.xlsx';
        return $this->success($href);
    }
}
