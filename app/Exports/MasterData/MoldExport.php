<?php

namespace App\Exports\MasterData;

use App\Helpers\Utilities;
use App\Models\Equipment;
use App\Models\Machine;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MoldExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    private $rowNumber = 0;

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return $this->data; // Dữ liệu xuất ra file
    }

    public function headings(): array
    {
        return [
            'STT',
            'Mã khuôn bế',
            'Khách hàng',
            'Dài',
            'Rộng',
            'Cao',
            'Kích thước chuẩn',
            'Phân loại 1',
            'Mã buyer',
            'Khổ',
            'Dài khuôn bế',
            'Số con',
            'Số mảnh ghép',
            'Pad xé rãnh',
            'Máy',
            'Ghi chú khác',
            'Layout',
            'Nhà cung cấp',
            'Ngày đặt khuôn',
            'Người thiết kế'
        ];
    }

    public function map($record): array
    {
        $this->rowNumber++;
        return [
            $this->rowNumber,
            $record->khuon_id,
            $record->customer_id, 
            $record->dai, 
            $record->rong, 
            $record->cao,
            $record->kich_thuoc,
            $record->phan_loai_1,
            $record->buyer_id, 
            $record->kho_khuon, 
            $record->dai_khuon, 
            $record->so_con, 
            $record->so_manh_ghep, 
            $record->pad_xe_ranh, 
            $record->machine_id,
            $record->note, 
            $record->layout, 
            $record->supplier,
            $record->ngay_dat_khuon, 
            $record->designer->name ?? ""
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Apply styles to the header row
        $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFF2CC'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Apply border style to all cells
        $sheet->getStyle('A1:' . $sheet->getHighestColumn() . $sheet->getHighestRow())->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
    }
}
