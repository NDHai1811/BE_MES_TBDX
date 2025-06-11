<?php

namespace App\Admin\Controllers;

use App\Models\User;
use App\Models\ErrorLog;
use App\Models\Jig;
use App\Models\Sheft;
use App\Models\UserLine;
use App\Models\UserLineMachine;
use App\Models\UserMachine;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MasterData\UserMachineExport;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class UserMachineController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('machine-assignment/list', [UserMachineController::class, 'getMachineAssignment']);
            Route::post('machine-assignment/create', [UserMachineController::class, 'createMachineAssignment']);
            Route::post('machine-assignment/delete', [UserMachineController::class, 'deleteMachineAssignment']);
            Route::patch('machine-assignment/update', [UserMachineController::class, 'updateMachineAssignment']);
            Route::get('machine-assignment/export', [UserMachineController::class, 'exportMachineAssignment']);
        });
    }

    /**
     * Build user query with filters
     */
    private function buildUserQuery(Request $request, $withRelations = ['user_line', 'user_machine'])
    {
        $query = User::orderBy('name');
        
        if (!isset($request->all_user)) {
            $query->whereNull('deleted_at');
        }
        if(isset($request->username)){
            $query->where('username', 'like', "%{$request->username}%");
        }
        if(isset($request->name)){
            $query->where('name', 'like', "%{$request->name}%");
        }
        
        return $query->with($withRelations);
    }

    public function getMachineAssignment(Request $request){
        $query = $this->buildUserQuery($request);
        $totalPage = $query->count();
        
        if(isset($request->page) && isset($request->pageSize)){
            $query->offset(($request->page - 1) * $request->pageSize)->limit($request->pageSize);
        }
        
        $records = $query->select('username', 'name', 'id')->get();
        
        foreach($records as $record){
            $record->line_id = $record->user_line->line_id ?? null;
            $record->machine_id = $record->user_machine->pluck('machine_id') ?? [];
        }
        
        return $this->success(['data'=>$records, 'totalPage'=>$totalPage]);
    }

    public function updateMachineAssignment(Request $request){
        $input = $request->all();
        $input['user_id'] = $input['id'];
        try {
            DB::beginTransaction();
            $user_line = UserLine::updateOrCreate(
                ['user_id'=>$input['user_id']],
                ['line_id'=>$input['line_id']],
            );
            if(isset($input['machine_id'])){
                UserMachine::where('user_id', $input['user_id'])->delete();
                foreach($input['machine_id'] as $machine_id){
                    UserMachine::create(['user_id'=>$input['user_id'], 'machine_id'=>$machine_id]);
                }
            }
            DB::commit();
            return $this->success('', 'Cập nhật thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }

    public function createMachineAssignment(Request $request){
        $input = $request->all();
        try {
            DB::beginTransaction();
            $machine_assign = UserLineMachine::create($input);
            DB::commit();
            return $this->success($machine_assign, 'Tạo thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }

    public function deleteMachineAssignment(Request $request){
        $input = $request->all();
        try {
            DB::beginTransaction();
            UserLineMachine::whereIn('id', $input)->delete();
            DB::commit();
            return $this->success('', 'Xoá thành công');
        } catch (\Throwable $th) {
            DB::rollBack();
            ErrorLog::saveError($request, $th);
            return $this->failure('', 'Đã xảy ra lỗi');
        }
    }
    public function exportMachineAssignment(Request $request){
        try {
            $query = $this->buildUserQuery($request);
            $records = $query->select('username', 'name', 'id')->get();
            
            foreach($records as $record){
                $record->line_id = $record->user_line->line_id ?? null;
                $record->machine_id = $record->user_machine->pluck('machine_id')->toArray() ?? [];
            }
            
            if ($records->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không có dữ liệu để xuất'
                ]);
            }
            
            $timestamp = date('YmdHi');
            $fileName = "PhanBoMayTheoTaiKhoan_$timestamp.xlsx";
            $filePath = "export/$fileName";
            
            Excel::store(new UserMachineExport($records), $filePath, 'excel');
            
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
            
        } catch (\Throwable $th) {
            ErrorLog::saveError($request, $th);
            return response()->json([
                'success' => false,
                'message' => 'Đã xảy ra lỗi khi xuất file: ' . $th->getMessage(),
            ], 500);
        }
    }
}