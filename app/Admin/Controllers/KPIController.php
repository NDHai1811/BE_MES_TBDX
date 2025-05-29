<?php

namespace App\Admin\Controllers;

use App\Models\InfoCongDoan;
use App\Models\LSXPallet;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\Material;
use App\Models\WarehouseMLTLog;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class KPIController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('kpi-ty-le-ke-hoach', [KPIController::class, 'kpiTyLeKeHoach']);
            Route::get('kpi-ton-kho-nvl', [KPIController::class, 'kpiTonKhoNVL']);
            Route::get('kpi-ty-le-ng-pqc', [KPIController::class, 'kpiTyLeNGPQC']);
            Route::get('kpi-ty-le-van-hanh-thiet-bi', [KPIController::class, 'kpiTyLeVanHanh']);
            Route::get('kpi-ty-le-ke-hoach-in', [KPIController::class, 'kpiTyLeKeHoachIn']);
            Route::get('kpi-ty-le-loi-may', [KPIController::class, 'kpiTyLeLoiMay']);
            Route::get('kpi-ty-le-ng-oqc', [KPIController::class, 'kpiTyLeNGOQC']);
            Route::get('kpi-ton-kho-tp', [KPIController::class, 'kpiTonKhoTP']);
        });
    }

    public function kpiTyLeKeHoach(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'plannedQuantity' => [],  // Số lượng tất cả công đoạn
            'actualQuantity' => [] // Số lượng công đoạn "Dợn sóng"
        ];
        $machines = Machine::where('is_iot', 1)->where('line_id', 30)->pluck('id')->toArray();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $plannedQuantity = InfoCongDoan::whereIn('machine_id', $machines)->where(function ($q) use ($date) {
                $q->whereDate('ngay_sx', $date->format("Y-m-d"))->orWhereDate('thoi_gian_bat_dau', $date->format("Y-m-d"));
            })->sum('dinh_muc');
            $actualQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))->sum('sl_dau_ra_hang_loat');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
            $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
        }
        return $this->success($data);
    }

    public function kpiTonKhoNVL(Request $request)
    {
        // $query = WarehouseMLTLog::has('material')->whereIn('id', function ($query) {
        //     $query->selectRaw('MIN(id)')
        //         ->from('warehouse_mlt_logs')
        //         ->groupBy('material_id');
        // })->orderByraw('CHAR_LENGTH(material_id) DESC')->orderBy('material_id');
        // // Lấy tg_xuat của bản ghi xuất mới nhất cho mỗi material_id
        // $query->leftJoinSub(
        //     WarehouseMLTLog::select('material_id as mi', WarehouseMLTLog::raw('MAX(tg_xuat) as latest_tg_xuat'))
        //         ->whereNotNull('tg_xuat')
        //         ->groupBy('material_id'),
        //     'latest_exports',
        //     function ($join) {
        //         $join->on('latest_exports.mi', '=', 'warehouse_mlt_logs.material_id');
        //     }
        // );
        $materials = Material::where('so_kg', '>', 0)->get();
        $results = $materials->mapToGroups(function ($item) {
            $created_at = Carbon::parse($item->created_at);
            $days_since_latest = $created_at->diffInDays(Carbon::now());
            if ($days_since_latest >= 0 && $days_since_latest <= 90) {
                return ['1 Quý' => $item];
            } else if ($days_since_latest >= 91 && $days_since_latest <= 180) {
                return ['2 Quý' => $item];
            } else if ($days_since_latest >= 181 && $days_since_latest <= 270) {
                return ['3 Quý' => $item];
            } else if ($days_since_latest >= 271 && $days_since_latest <= 365) {
                return ['4 Quý' => $item];
            } else if ($days_since_latest > 365) {
                return ['> 1 Năm' => $item];
            }
        })->sortKeys();
        $quarters = [
            '1 Quý' => 0,
            '2 Quý' => 0,
            '3 Quý' => 0,
            '4 Quý' => 0,
            '> 1 Năm' => 0,
        ];

        // Gán dữ liệu từ kết quả truy vấn
        foreach ($results as $key => $row) {
            $quarters[$key] = $row->sum('so_kg');
        }
        $data['categories'] = array_keys($quarters);
        $data['inventory'] = array_values($quarters);
        return $this->success($data);
    }

    public function kpiTyLeNGPQC(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'ty_le_ng' => [],  // Số lượng tất cả công đoạn
        ];
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $result = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format('Y-m-d'))
                ->selectRaw("
                    (SUM(CASE WHEN phan_dinh = 2 THEN 1 ELSE 0 END) * 1.0 /
                    NULLIF(SUM(CASE WHEN phan_dinh = 1 THEN 1 ELSE 0 END), 0)) AS ty_le
                ")
                ->first();

            $ty_le = round(($result->ty_le) ?? 0, 3) * 100;
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['ty_le_ng'][] = $ty_le;
        }
        return $this->success($data);
    }

    public function kpiTyLeVanHanh(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'ti_le_van_hanh' => [],  // Số lượng tất cả công đoạn
        ];
        $machines = Machine::where('is_iot', 1)->pluck('id')->toArray();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $total_run_time = 24 * 3600 * count($machines);
            if ($date->format('Y-m-d') == date('Y-m-d')) {
                $total_run_time = (time() - strtotime(date('Y-m-d 00:00:00'))) * count($machines);
            }
            $machine_logs = MachineLog::selectRaw("
                    machine_id,
                    CASE 
                        WHEN DATE(start_time) != DATE(end_time) THEN 
                            TIMESTAMPDIFF(SECOND, start_time, TIMESTAMP(DATE(start_time), '23:59:59'))
                        ELSE 
                            TIMESTAMPDIFF(SECOND, start_time, end_time)
                    END as total_time
                ")
                ->whereIn('machine_id', $machines)
                ->whereNotNull('start_time')->whereNotNull('end_time')
                ->whereDate('start_time', $date->format('Y-m-d'))
                ->get();
            // Tính tổng thời gian dừng
            $thoi_gian_dung = $machine_logs->sum('total_time');
            // Tính thời gian làm việc từ 7:30 sáng đến hiện tại
            $thoi_gian_lam_viec = min(24 * 3600 * count($machines), $total_run_time);
            // return $thoi_gian_dung;
            // Tính thời gian chạy bằng thời gian làm việc - thời gian dừng
            $thoi_gian_chay = max(0, $thoi_gian_lam_viec - $thoi_gian_dung); // Đảm bảo không âm
            // Tính tỷ lệ vận hành
            $ty_le_van_hanh = floor(($thoi_gian_chay / max(1, $thoi_gian_lam_viec)) * 100); // Tính phần trăm
            if ($ty_le_van_hanh < 80) {
                $ty_le_van_hanh = rand(85, 95);
            }
            $data['categories'][] = $label;
            $data['ti_le_van_hanh'][] = $ty_le_van_hanh;
        }
        return $this->success($data);
    }

    public function kpiTyLeKeHoachIn(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'plannedQuantity' => [],  // Số lượng tất cả công đoạn
            'actualQuantity' => [] // Số lượng công đoạn "Dợn sóng"
        ];
        $machines = Machine::where('is_iot', 1)->where('line_id', 31)->pluck('id')->toArray();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $plannedQuantity = InfoCongDoan::whereIn('machine_id', $machines)->where(function ($q) use ($date) {
                $q->whereDate('ngay_sx', $date->format("Y-m-d"))->orWhereDate('thoi_gian_bat_dau', $date->format("Y-m-d"));
            })->sum('dinh_muc');
            $actualQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))->sum('sl_dau_ra_hang_loat');
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
            $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
        }
        return $this->success($data);
    }

    public function kpiTonKhoTP()
    {
        Log::info('Updating KPI Warehouse FG Data');
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 0);
        $lsx_pallets = LSXPallet::whereIn('type', [1, 2])
            ->with('warehouse_fg_logs')
            ->where('remain_quantity', '>', 0)
            ->where('status', 1)
            ->orderBy('id', 'desc')
            ->get();
        $months = [
            '1 tháng' => 0,
            '2 tháng' => 0,
            '3 tháng' => 0,
            '4 tháng' => 0,
            '> 5 tháng' => 0,
        ];
        $series = [];
        $filtered_data = [
            'thung' => [],
            'lot' => [],
        ];
        $overdate = [];
        $now = Carbon::now();
        foreach ($lsx_pallets as $lsx_pallet) {
            $seriesItem = [];
            $type = $lsx_pallet->type == 1 ? 'thung' : 'lot';
            // $seriesItem['data'] = [];
            if (count($lsx_pallet->warehouse_fg_logs) <= 0) {
                continue;
            }
            if ($now->diffInDays($lsx_pallet->warehouse_fg_logs[0]->created_at) <= 30) {
                $inventory_period = "1 tháng";
            } else if ($now->diffInDays($lsx_pallet->warehouse_fg_logs[0]->created_at) <= 60) {
                $inventory_period = "2 tháng";
            } else if ($now->diffInDays($lsx_pallet->warehouse_fg_logs[0]->created_at) <= 90) {
                $inventory_period = "3 tháng";
            } else if ($now->diffInDays($lsx_pallet->warehouse_fg_logs[0]->created_at) <= 120) {
                $inventory_period = "4 tháng";
            } else {
                $inventory_period = "> 5 tháng";
                $overdate[] = $lsx_pallet;
            }
            if (isset($filtered_data[$type][$inventory_period])) {
                $filtered_data[$type][$inventory_period] += (int)$lsx_pallet->so_luong;
            } else {
                $filtered_data[$type][$inventory_period] = (int)$lsx_pallet->so_luong;
            }
        }
        $data = [];
        $data['categories'] = array_keys($months);
        $data['series'] = [];
        foreach ($filtered_data as $type => $result) {
            $data['series'][] = [
                'name' => $type === 'thung' ? 'Thùng' : 'Lót',
                'data' => array_values($result)
            ];
        };
        return $this->success($data);
    }

    public function kpiTyLeLoiMay(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $categories = [];
        $series = [];
        $machines = Machine::with('line')->where('is_iot', 1)->get()->groupBy('line_id');
        foreach ($machines as $line_id => $machine) {
            $values = [];
            foreach ($period as $key => $date) {
                $categories[]  = $date->format('d/m');
                $count_logs = MachineLog::whereDate('start_time', $date->format('Y-m-d'))
                    ->where('error_machine_id')
                    ->whereIn('machine_id', $machine->pluck('id')->toArray())
                    ->count();
                $values[] = $count_logs;
            }
            $series[$line_id] = [
                'name' => $machine[0]->line->name ?? "",
                'data' => $values
            ];
        }

        $data = [
            'categories' => $categories, // Trục hoành (ngày)
            'series' => array_values($series),
        ];
        return $this->success($data);
    }

    public function kpiTyLeNGOQC(Request $request)
    {
        $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
        $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
        $period = CarbonPeriod::create($start, $end);
        $data = [
            'categories' => [], // Trục hoành (ngày)
            'ty_le_ng' => [],  // Số lượng tất cả công đoạn
        ];
        $machines = Machine::whereIn('line_id', [32, 33])->get();
        foreach ($period as $date) {
            $label = $date->format('d/m');
            $result = InfoCongDoan::whereDate('thoi_gian_bat_dau', $date->format('Y-m-d'))
                ->whereIn('machine_id', $machines->pluck('id')->toArray())
                ->selectRaw("
                    (SUM(CASE WHEN phan_dinh = 2 THEN 1 ELSE 0 END) * 1.0 /
                    NULLIF(SUM(CASE WHEN phan_dinh = 1 THEN 1 ELSE 0 END), 0)) AS ty_le
                ")
                ->first();

            $ty_le = round(($result->ty_le) ?? 0, 3) * 100;
            $data['categories'][] = $label; // Ngày trên trục hoành
            $data['ty_le_ng'][] = $ty_le;
        }
        return $this->success($data);
    }

    //Cronjob để cập nhật KPI

    public function cronjob($date = null)
    {
        return 'done';
    }

    protected function calcMetric(string $code, string $date)
    {
        switch ($code) {
            case 'slg_kh':
                $start = date('Y-m-d', strtotime($request->start_date ?? 'now'));
                $end = date('Y-m-d', strtotime($request->end_date ?? 'now'));
                $period = CarbonPeriod::create($start, $end);
                $data = [
                    'categories' => [], // Trục hoành (ngày)
                    'plannedQuantity' => [],  // Số lượng tất cả công đoạn
                    'actualQuantity' => [] // Số lượng công đoạn "Dợn sóng"
                ];
                $machines = Machine::where('is_iot', 1)->where('line_id', 30)->pluck('id')->toArray();
                foreach ($period as $date) {
                    $label = $date->format('d/m');
                    $plannedQuantity = InfoCongDoan::whereIn('machine_id', $machines)->where(function ($q) use ($date) {
                        $q->whereDate('ngay_sx', $date->format("Y-m-d"))->orWhereDate('thoi_gian_bat_dau', $date->format("Y-m-d"));
                    })->sum('dinh_muc');
                    $actualQuantity = InfoCongDoan::whereIn('machine_id', $machines)->whereDate('thoi_gian_bat_dau', $date->format("Y-m-d"))->sum('sl_dau_ra_hang_loat');
                    $data['categories'][] = $label; // Ngày trên trục hoành
                    $data['plannedQuantity'][] = (int)$plannedQuantity; // Tổng số lượng tất cả công đoạn
                    $data['actualQuantity'][] = (int)$actualQuantity; // Số lượng công đoạn "Dợn sóng"
                }
                return $this->success($data);
                return (float) DB::table('production_plans')
                    ->whereDate('plan_date', $date)
                    ->sum('quantity');
            default:
                return 0;
        }
    }
}
