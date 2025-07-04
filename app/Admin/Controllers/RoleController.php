<?php

namespace App\Admin\Controllers;

use App\Exports\MasterData\RoleExport;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Traits\API;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class RoleController extends AdminController
{
    use API;

    public static function registerRoutes()
    {
        Route::controller(self::class)->group(function () {
            Route::get('roles/tree', [RoleController::class, 'getRoles']);
            Route::get('roles/list', [RoleController::class, 'getRolesList']);
            Route::get('roles/permissions', [RoleController::class, 'getPermissions']);
            Route::patch('roles/update/{id}', [RoleController::class, 'updateRole']);
            Route::post('roles/create', [RoleController::class, 'createRole']);
            Route::delete('roles/delete/{id}', [RoleController::class, 'deleteRoles']);
            Route::get('roles/export', [RoleController::class, 'exportRoles']);
            Route::post('roles/import', [RoleController::class, 'importRoles']);
        });
    }

    public function getRoles(Request $request){
        $query = Role::with('children', 'permissions', 'parent')->select('*', 'id as key')->whereNull('parent_id')->orderBy('created_at');
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $roles = $this->buildTree($query->get());
        return $this->success($roles);
    }

    function queryRole($request)
    {
        $query = Role::with('permissions')->orderBy('created_at');
        if (isset($request->id)) {
            $query->where('id', 'like', "%$request->id%");
        }
        if (isset($request->name)) {
            $query->where('name', 'like', "%$request->name%");
        }
        return $query;
    }

    public function getRolesList(Request $request){
        $query = Role::with('permissions', 'parent')->select('*', 'id as key')->orderBy('created_at');
        if(isset($request->name)){
            $query->where('name', 'like', "%$request->name%");
        }
        $roles = $query->get();
        return $this->success($roles);
    }

    private function buildTree($items)
    {
        $tree = [];
        foreach ($items as $item) {
            $parent = $item->parent;
            if($parent){
                $item['parent'] = $parent;
            }
            $children = $this->buildTree($item->children);
            if ($children->isNotEmpty()) {
                $item['children'] = $children;
            }else{
                unset($item['children']);
            }
            $permissions = $item->permissions;
            if ($permissions->isNotEmpty()) {
                $item['permissions'] = $permissions;
            }else{
                unset($item['permissions']);
            }
            $tree[] = $item->toArray();
        }
        return collect($tree);
    }

    public function getPermissions(Request $request){
        $permissions = Permission::select('id as value', 'name as label')->orderBy('name')->get();
        return $this->success($permissions);
    }

    public function updateRole(Request $request, $id){
        $input = $request->all();

        $validated = Role::validateUpdate($input, true);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $role = Role::find($id);
        if($role){
            $input['slug'] = Str::slug($input['name']);
            $update = $role->update($input);
            $user_roles = RolePermission::where('role_id', $role->id)->delete();
            foreach($input['permissions'] as $permission){
                RolePermission::create(['role_id'=>$role->id,'permission_id'=>$permission]);
            }
            return $this->success($role);
        }
        else{
            return $this->failure('', 'Không tìm thấy bộ phận');
        }
    }

    public function createRole(Request $request){
        $input = $request->all();
        $validated = Role::validateUpdate($input, false);
        if ($validated->fails()) {
            return $this->failure('', $validated->errors()->first());
        }
        $input['slug'] = Str::slug($input['name']);
        $role = Role::create($input);
        if($role){
            foreach($input['permissions'] ?? [] as $permission){
                RolePermission::create(['role_id'=>$role->id,'permission_id'=>$permission]);
            }
        }
        return $this->success($role, 'Tạo thành công');
    }

    public function deleteRoles($id){
        $role = Role::find($id);
        if (!$role) {
            return $this->failure([], 'Không tìm thấy role với ID được cung cấp', 404);
        }
        $role->delete();
        DB::table('role_permissions')->where('role_id', $id)->delete();
        return $this->success('Xoá thành công');
    }

    public function exportRoles(Request $request){
//        $query = Role::with('permissions')->orderBy('created_at');
////        return $query;
//        if(isset($request->name)){
//            $query->where('name', 'like', "%$request->name%");
//        }
//        $roles = $query->get();
////        return $roles;
//        foreach( $roles as $role ){
//            $quyen = [];
//            foreach($role->permissions as $permission){
//                $quyen[] = $permission->name;
//            }
//            $role->quyen = implode(", ", $quyen);
//        }
//        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
//        $sheet = $spreadsheet->getActiveSheet();
//        $start_row = 2;
//        $start_col = 1;
//        $centerStyle = [
//            'alignment' => [
//                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
//                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
//                'wrapText' => true
//            ],
//            'borders' => array(
//                'outline' => array(
//                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
//                    'color' => array('argb' => '000000'),
//                ),
//            ),
//        ];
//        $headerStyle = array_merge($centerStyle, [
//            'font' => ['bold' => true],
//            'fill' => [
//                'fillType' => Fill::FILL_SOLID,
//                'startColor' => array('argb' => 'BFBFBF')
//            ]
//        ]);
//        $titleStyle = array_merge($centerStyle, [
//            'font' => ['size'=>16, 'bold' => true],
//        ]);
//        $border = [
//            'borders' => array(
//                'allBorders' => array(
//                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
//                    'color' => array('argb' => '000000'),
//                ),
//            ),
//        ];
//        $header = ['Tên quyền', 'Chức năng'];
//        $table_key = [
//            'A'=>'name',
//            'B'=>'quyen',
//        ];
//        foreach($header as $key => $cell){
//            if(!is_array($cell)){
//                $sheet->setCellValue([$start_col, $start_row], $cell)->mergeCells([$start_col, $start_row, $start_col, $start_row])->getStyle([$start_col, $start_row, $start_col, $start_row])->applyFromArray($headerStyle);
//            }
//            $start_col+=1;
//        }
//        $sheet->setCellValue([1, 1], 'Quản lý phân quyền')->mergeCells([1, 1, $start_col-1, 1])->getStyle([1, 1, $start_col-1, 1])->applyFromArray($titleStyle);
//        $sheet->getRowDimension(1)->setRowHeight(40);
//        $table_col = 1;
//        $table_row = $start_row+1;
//        foreach($roles->toArray() as $key => $row){
//            $table_col = 1;
//            $row = (array)$row;
//            foreach($table_key as $k=>$value){
//                if(isset($row[$value])){
//                    $sheet->setCellValue($k.$table_row,$row[$value])->getStyle($k.$table_row)->applyFromArray($centerStyle);
//                }else{
//                    continue;
//                }
//                $table_col+=1;
//            }
//            $table_row+=1;
//        }
//        foreach ($sheet->getColumnIterator() as $column) {
//            if ($column->getColumnIndex() === 'B') {
//                $sheet->getColumnDimension($column->getColumnIndex())->setWidth(50);
//            } else {
//                $sheet->getColumnDimension($column->getColumnIndex())->setAutoSize(true);
//            }
//            $sheet->getStyle($column->getColumnIndex().($start_row).':'.$column->getColumnIndex().($table_row-1))->applyFromArray($border);
//        }
//        header("Content-Description: File Transfer");
//        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//        header('Content-Disposition: attachment;filename="Phân quyền.xlsx"');
//        header('Cache-Control: max-age=0');
//        header("Content-Transfer-Encoding: binary");
//        header('Expires: 0');
//        $writer =  new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
//        $writer->save('../app/excel/export/Phân quyền.xlsx');
//        $href = '/export/Phân quyền.xlsx';
//
//        return $this->success($href);


//        //viet dua tren export excel machine
        $file = "Phân quyền.xlsx";
        $filePath = "export/$file";
        $data = $this->queryRole($request)->get();
        if ($data->isEmpty()){
            return $this->failure([], 'Không có dữ liệu để xuất', 404);
        }
        $result = Excel::store(new RoleExport($data), $filePath, 'excel');

        if (empty($result))
            return $this->failure([], 'THAO TÁC THẤT BẠI', 500);
        # Generate file base64
        $fileContent = Storage::disk('excel')->get($filePath);
        $fileType = File::mimeType(storage_path("app/excel/$filePath"));
        $base64 = base64_encode($fileContent);
        $fileBase64Uri = "data:$fileType;base64,$base64";

        # Delete if needed
        Storage::disk('excel')->delete($filePath);

        # Return
        return $this->success([
            'file' => $file,
            'type' => $fileType,
            'data' => $fileBase64Uri,
        ]);
    }

    public function importRoles(Request $request){
        $request->validate([
            'file' => 'required|mimes:xlsx',
        ]);
//        return $request->file('file')->getRealPath();
//        $extension = pathinfo($request->file('file'), PATHINFO_EXTENSION);
//        return $extension;
//        return $request->file('file');
//        if ($extension == 'csv') {
//            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
//        } elseif ($extension == 'xlsx') {
//            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
//        } else {
//            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
//        }
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        // file path
//        $spreadsheet = $reader->load($_FILES['files']['tmp_name']);
        $spreadsheet = $reader->load($request->file('file'));
        $allDataInSheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $data = [];
//        return $allDataInSheet;
        foreach ($allDataInSheet as $key => $row) {
            //Lấy dứ liệu từ dòng thứ 2
            if ($key > 2) {
                $input = [];
                $input['name'] = $row['A'];
                $input['quyen'] = $row['B'];
                $validated = Role::validateUpdate($input);
                if ($validated->fails()) {
                    return $this->failure('', 'Lỗi dòng thứ '.($key).': '.$validated->errors()->first());
                }
                $data[] = $input;
            }
        }
        return $data;
        foreach ($data as $key => $input) {
            $role = Role::where('name', 'like', $input['name'])->first();
            if($role) {
                $role->update($input);
                $role_permission = RolePermission::where('role_id', $role->id)->delete();
                foreach(explode(', ', $input['quyen']) as $quyen){
                    $permission = Permission::where('name', 'like', trim($quyen))->first();
                    if($permission) {
                        $role_permission = RolePermission::create(['role_id'=>$role->id, 'permission_id' => $permission->id]);
                    }
                }
            }else{
                $input['slug'] = Str::slug($input['name']);
                $role = Role::create($input);
                foreach(explode(', ', $input['quyen']) as $quyen){
                    $permission = Permission::where('name', 'like', trim($quyen))->first();
                    if($permission) {
                        $role_permission = RolePermission::create(['role_id'=>$role->id, 'permission_id' => $permission->id]);
                    }
                }
            }
        }
        return $this->success([], 'Upload thành công');
    }
}