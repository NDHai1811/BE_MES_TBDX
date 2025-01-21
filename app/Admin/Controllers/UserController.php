<?php

namespace App\Admin\Controllers;

use App\Helpers\QueryHelper;
use App\Models\User;
use App\Models\Department;
use App\Models\UserRole;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;

class UserController extends AdminController
{
    use API;
    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('users/list', [UserController::class, 'getUsers']);
            Route::get('users/roles', [UserController::class, 'getUserRoles']);
            Route::patch('users/update', [UserController::class, 'updateUsers']);
            Route::post('users/create', [UserController::class, 'createUsers']);
            Route::delete('users/delete', [UserController::class, 'deleteUsers']);
            Route::get('users/export', [UserController::class, 'exportUsers']);
            Route::post('users/import', [UserController::class, 'importUsers']);
            Route::get('profile', [UserController::class, 'profile']);
        });
    }

    public function getUsers(Request $request)
    {
        $query = User::with('roles', 'department');
        if (!isset($request->all_user)) {
            $query->whereNull('deleted_at');
        }
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        if (isset($request->username)) {
            $query->where('username', 'like', "%$request->username%");
        }
        if (isset($request->department_name)) {
            $query->whereHas('department', function($q)use($request){
                $q->where('name', 'like', "$request->department_name%");
            });
        }
        $records = $query->paginate($request->pageSize ?? null);
        $users = $records->items();
        foreach ($users as $key => $user) {
            $user->usage_time = round($user->usage_time_in_day / 60);
            $user->department_name = $user->department->name ?? "";
        }
        return $this->success(['data' => $users, 'pagination' => QueryHelper::pagination($request, $records)]);
    }
    public function getUserRoles(Request $request)
    {
        $roles = config('admin.database.roles_model')::select('name as label', 'id as value')->get();
        return $this->success($roles);
    }
    public function updateUsers(Request $request)
    {
        $input = $request->all();
        $user = User::where('id', $input['id'])->first();
        if ($user) {
            $validated = User::validateUpdate($input);
            if ($validated->fails()) {
                return $this->failure('', $validated->errors()->first());
            }
            $update = $user->update($input);
            if ($update) {
                $user_roles = UserRole::where('user_id', $user->id)->delete();
                foreach ($input['roles'] ?? [] as $role) {
                    UserRole::insert(['role_id' => $role, 'user_id' => $user->id]);
                }
                return $this->success($user);
            } else {
                return $this->failure('', 'Không thành công');
            }
        } else {
            return $this->failure('', 'Không tìm thấy tài khoản');
        }
    }

    public function createUsers(Request $request)
    {
        $input = $request->all();
        $input['password'] = Hash::make('123456');
        $user = User::create($input);
        foreach ($input['roles'] ?? [] as $role) {
            UserRole::insert(['role_id' => $role, 'user_id' => $user->id]);
        }
        return $this->success($user, 'Tạo thành công');
    }

    public function deleteUsers(Request $request)
    {
        $input = $request->all();
        User::whereIn('id', $input)->update(['deleted_at' => now()]);
        return $this->success('Xoá thành công');
    }

    public function exportUsers(Request $request)
    {
        $query = User::with('roles')->whereNull('deleted_at');
        if (!isset($request->all_user)) {
            $query->whereNull('deleted_at');
        }
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        if (isset($request->username)) {
            $query->where('username', 'like', "%$request->username%");
        }
        if (isset($request->department_name)) {
            $query->whereHas('department', function($q)use($request){
                $q->where('name', 'like', "$request->department_name%");
            });
        }
        $users = $query->get();
        foreach ($users as $user) {
            $bo_phan = [];
            foreach ($user->roles as $role) {
                $bo_phan[] = $role->name;
            }
            $user->bo_phan = implode(", ", $bo_phan);
            $user->department_name = $user->department->name ?? "";
        }
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $start_row = 2;
        $start_col = 1;
        $centerStyle = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'wrapText' => true
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
        $header = ['Username', 'Tên', 'Bộ phận', 'Phân quyền', 'User chức năng'];
        $table_key = [
            'A' => 'username',
            'B' => 'name',
            'C' => 'department_name',
            'D' => 'bo_phan',
            'E' => 'function_user'
        ];
        foreach ($header as $key => $cell) {
            if (!is_array($cell)) {
                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
            }
            $start_col += 1;
        }

        $sheet->setCellValue([1, 1], 'Quản lý tài khoản')->mergeCells([1, 1, $start_col - 1, 1])->getStyle([1, 1, $start_col - 1, 1])->applyFromArray($titleStyle);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $table_col = 1;
        $table_row = $start_row + 1;
        foreach ($users->toArray() as $key => $row) {
            $table_col = 1;
            $row = (array)$row;
            foreach ($table_key as $k => $value) {
                if (isset($row[$value])) {
                    $sheet->setCellValue($k . $table_row, $row[$value])->getStyle($k . $table_row)->applyFromArray($centerStyle);
                } else {
                    continue;
                }
                $table_col += 1;
            }
            $table_row += 1;
        }
        foreach ($sheet->getColumnIterator() as $column) {
            if ($column->getColumnIndex() === 'D') {
                $sheet->getColumnDimension($column->getColumnIndex())->setWidth(50);
            } else {
                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
            }

            $sheet->getStyle($column->getColumnIndex() . ($start_row) . ':' . $column->getColumnIndex() . ($table_row - 1))->applyFromArray($border);
        }
        header("Content-Description: File Transfer");
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Danh sách tài khoản.xlsx"');
        header('Cache-Control: max-age=0');
        header("Content-Transfer-Encoding: binary");
        header('Expires: 0');
        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('exported_files/Danh sách tài khoản.xlsx');
        $href = '/exported_files/Danh sách tài khoản.xlsx';
        return $this->success($href);
    }

    public function importUsers(Request $request)
    {
        $extension = pathinfo($_FILES['files']['name'], PATHINFO_EXTENSION);
        if ($extension == 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
        } elseif ($extension == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        // file path
        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $data = [];
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 4
            if ($key > 2) {
                $input = [];
                $user_name = "";
                foreach (preg_split("/\s+/", $row['C']) as $w) {
                    $user_name .= strtolower(mb_substr($w, 0, 1));
                }
                $input['username'] = $row['A'];
                $input['name'] = $row['B'];
                $input['department_name'] = $row['C'];
                $input['bo_phan'] = $row['D'];
                $input['function_user'] = $row['E'];
                // if(!$input['username'] &&  $input['name'] && $input['bo_phan']){
                //     break;
                // }
                $validated = User::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ ' . ($key) . ': ' . $validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        foreach ($data as $key => $input) {
            $user = User::where('username', $input['username'])->first();
            if ($user) {
                $user->update($input);
            } else {
                $input['password'] = Hash::make('123456');
                $user = User::create($input);
            }
            UserRole::where('user_id', $user->id)->delete();
            foreach (explode(',', $input['bo_phan']) as $bo_phan) {
                $role = DB::table('admin_roles')->where('name', trim($bo_phan))->first();
                if ($role) {
                    $exists = DB::table('admin_role_users')
                        ->where('role_id', $role->id)
                        ->where('user_id', $user->id)
                        ->exists();

                    if (!$exists) {
                        UserRole::insert([
                            'role_id' => $role->id,
                            'user_id' => $user->id,
                        ]);
                    }
                }
            }
            $department = Department::where('name', trim($input['department_name']))->first();
            $user->update(['department_id'=>$department->id ?? null]);
        }
        return $this->success([], 'Upload thành công');
    }

    public function profile(Request $request){
        $user = $request->user();
        $user->permission = $user->roles->flatMap->permissions;
        return $this->success($user);
    }
}
