<?php

namespace App\Admin\Controllers;

use App\Events\ProductionUpdated;
use App\Helpers\QueryHelper;
use App\Models\User;
use App\Models\DRC;
use App\Models\GroupPlanOrder;
use App\Models\Line;
use App\Models\LocatorMLTMap;
use App\Models\Machine;
use App\Models\MachineLog;
use App\Models\MachineParameterLogs;
use App\Models\Material;
use App\Models\Order;
use App\Models\Tracking;
use App\Models\Vehicle;
use App\Models\VOCRegister;
use App\Models\VOCType;
use Encore\Admin\Controllers\AdminController;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class WebsocketController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::post('update-iot', [WebsocketController::class, 'update']);
            Route::post('update-machine-status', [WebsocketController::class, 'updateMachineStatus']);
            Route::post('update-machine-params', [WebsocketController::class, 'updateMachineParams']);
        });
    }

    public function websocket(Request $request)
    {
        if (!isset($request['device_id'])) return 'Không có mã máy';
        $machine = Machine::with('line')->where('device_id', $request['device_id'])->first();
        $line = $machine->line;
        $tracking = Tracking::where('machine_id', $machine->id)->first();
        switch ($line->id) {
            case Line::LINE_SONG:
                return $this->CorrugatingProduction($request, $tracking, $machine);
                break;
            case Line::LINE_IN:
                if ($machine->id === 'CH02' || $machine->id === 'CH03') {
                    return $this->TemPrintProductionCH($request, $tracking, $machine);
                } else {
                    return $this->TemPrintProduction($request, $tracking, $machine);
                }
                break;
            case Line::LINE_DAN:
                return $this->TemGluingProduction($request, $tracking, $machine);
                break;
            default:
                break;
        }
        return $this->success($this->takeTime());
    }

    public function websocketMachineStatus(Request $request)
    {
        if (!isset($request['device_id'])) return $this->failure('Không có mã máy');;
        $machine = Machine::with('line')->where('device_id', $request['device_id'])->first();
        $tracking = Tracking::where('machine_id', $machine->id)->first();
        $res = MachineLog::UpdateStatus(['machine_id' => $machine->id, 'status' => (int)$request['Machine_Status'], 'timestamp' => date('Y-m-d H:i:s'), 'lo_sx' => $tracking->lo_sx ?? null]);
        broadcast(new ProductionUpdated($res))->toOthers();
        return $this->success('Đã cập nhật trạng thái');
    }

    public function websocketMachineParams(Request $request)
    {
        $input = $request->all();
        if (!isset($request->device_id)) return $this->failure('', 'Không có mã máy');
        $machine = Machine::where('device_id', $request->device_id)->first();
        if (!$machine) return $this->failure('', 'Không tìm thấy máy');
        $tracking = Tracking::where('machine_id', $machine->id)->first();
        MachineParameterLogs::create([
            'lo_sx' => $tracking->lo_sx,
            'machine_id' => $machine->id,
            "info" => $input
        ]);
        return $this->success($this->takeTime(), 'Lưu thông số');
    }
}
