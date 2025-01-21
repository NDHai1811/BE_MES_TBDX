<?php

use App\Admin\Controllers\ApiController;
use App\Admin\Controllers\ApiOIController;
use App\Admin\Controllers\ApiUIController;
use App\Admin\Controllers\AuthController;
use App\Admin\Controllers\BuyerController;
use App\Admin\Controllers\CustomerController;
use App\Admin\Controllers\DepartmentController;
use App\Admin\Controllers\ErrorController;
use App\Admin\Controllers\ErrorMachineController;
use App\Admin\Controllers\InfoCongDoanController;
use App\Admin\Controllers\KhuonController;
use App\Admin\Controllers\KPIController;
use App\Admin\Controllers\LayoutController;
use App\Admin\Controllers\LineController;
use App\Admin\Controllers\MachineController;
use App\Admin\Controllers\MaintenanceController;
use App\Admin\Controllers\MaterialController;
use App\Admin\Controllers\MESUsageRateController;
use App\Admin\Controllers\OrderController;
use App\Admin\Controllers\PermissionController;
use App\Admin\Controllers\RoleController;
use App\Admin\Controllers\ShiftAssignmentController;
use App\Admin\Controllers\TestCriteriaController;
use App\Admin\Controllers\UserController;
use App\Admin\Controllers\UserMachineController;
use App\Admin\Controllers\VehicleController;
use App\Admin\Controllers\VOCRegisterController;
use App\Admin\Controllers\VOCTypeController;
use App\Admin\Controllers\WebsocketController;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//
Route::group(['middleware' => []], function () {
    AuthController::registerRoutes();
});

Route::group(['middleware' => [], 'prefix' => "/v2/websocket",], function () {
    WebsocketController::registerRoutes();//OI controller
});

Route::group(['middleware' => "auth:sanctum", 'prefix' => "/v2/oi",], function () {
    ApiOIController::registerRoutes();//OI controller
});

// Route::group(['middleware' => "auth:sanctum", 'prefix' => "/v2/ui",], function () {
//     ApiOIController::registerRoutes();//OI controller
// });

Route::group([
    'prefix'        => "",
    'middleware'    => "auth:sanctum",
    'as'            => '',
], function (Router $router) {
    InfoCongDoanController::registerRoutes();//Thông tin sản lượng lô
    MachineController::registerRoutes();//Máy
    ErrorController::registerRoutes();//Lỗi công đoạn
    TestCriteriaController::registerRoutes();//Chỉ tiêu kiểm tra
    LineController::registerRoutes();//Công đoạn
    UserController::registerRoutes();//Người dùng
    RoleController::registerRoutes();//Vai trò
    PermissionController::registerRoutes();//Quyền
    ErrorMachineController::registerRoutes();//Lỗi máy
    MaterialController::registerRoutes();//Nguyên vật liệu
    KhuonController::registerRoutes();//Khuôn
    MaintenanceController::registerRoutes();//Bảo trì
    OrderController::registerRoutes();//Đơn hàng
    CustomerController::registerRoutes();//Khách hàng
    BuyerController::registerRoutes();//Buyer
    LayoutController::registerRoutes();//Layout
    VehicleController::registerRoutes();//Xe
    UserMachineController::registerRoutes();//Phân bổ máy cho người dùng
    ShiftAssignmentController::registerRoutes();//Phân ca làm việc
    VOCTypeController::registerRoutes();//Loại VOC
    VOCRegisterController::registerRoutes();//VOC
    KPIController::registerRoutes();//KPI chart
    DepartmentController::registerRoutes();//Bộ phận
    $router->post('manufacture/production-plan/import', [ApiController::class, 'importKHSX']);
    $router->post('import/vehicle', [ApiUIController::class, 'importVehicle']);
    $router->post('update-tem', [ApiUIController::class, 'updateTem']);
    $router->post('import-khuon-link', [ApiUIController::class, 'importKhuonLink']);
    $router->post('upload-nhap-kho-nvl', [ApiController::class, 'uploadNKNVL']);
    $router->post('locate-by-supplier', [ApiController::class, 'phanKhuTheoNCC']);
    $router->post('import/tieu_chuan_ncc', [ApiController::class, 'importTieuChuanNCC']);
    $router->post('locator-mtl-map-import', [ApiController::class, 'importLocatorMLTMap']);
    $router->post('orders/import-from-plan', [OrderController::class, 'importOrdersFromPLan']);
});                                                                                                                                                                                                                              

//Api for test porpose
Route::group([
    'prefix'        => "/",
    'middleware'    => [],
    'as'            => '',
], function (Router $router) {
    $router->post('import-material', [ApiUIController::class, 'importMaterial']);
    $router->post('import', [ApiUIController::class, 'import']);
    $router->post('import-new-fg-locator', [ApiUIController::class, 'importNewFGLocator']);
    $router->get('intem', [ApiUIController::class, 'getTem']);
    $router->post('create-table-fields', [ApiUIController::class, 'insertTableFields']);
    $router->post('import-user-line-machine', [ApiUIController::class, 'importUserLineMachine']);
    $router->post('import-iqc-test-criterias', [ApiUIController::class, 'importIQCTestCriteria']);
    $router->post('update-customer-wahoure-fg-export', [ApiUIController::class, 'updateCustomerWarehouseFGExport']);
    $router->post('update-so-kg-dau-material', [ApiUIController::class, 'updateSoKGDauMaterial']);
    $router->post('searchMaterial', [ApiUIController::class, 'searchMasterDataMaterial']);
    $router->post('deleteOldMaterials', [ApiUIController::class, 'deleteMaterialHasNoLocation']); //Cập nhật tất cả vị trí kho có %C01% sang C01.001
    $router->post('sua-material', [ApiUIController::class, 'suaChuaLoiLam']);
    $router->get('update-info-from-plan', [ApiUIController::class, 'updateInfoFromPlan']);
    $router->get('update_admin_user_delivery_note', [ApiUIController::class, 'update_admin_user_delivery_note']);
    $router->post('update-new-machine-id', [ApiUIController::class, 'updateNewMachineId']);
    $router->get('update-ngaysx-info-cong-doan', [ApiUIController::class, 'updateNgaysxInfoCongDoan']);
    $router->get('update-dinhmuc-info-cong-doan', [ApiUIController::class, 'updateDinhMucInfoCongDoan']);
    $router->get('update-old-info-cong-doan', [ApiUIController::class, 'updateInfoCongDoanPriority']);
    $router->get('reset-info-cong-doan', [ApiUIController::class, 'resetInfoCongDoan']);
    $router->get('end-old-info-cong-doan', [ApiUIController::class, 'endOldInfoCongDoan']);
    $router->get('wtf', [ApiUIController::class, 'wtf']);
    $router->get('calculateUsageTime', [MESUsageRateController::class, 'calculateUsageTime']);
    $router->get('calculateMaintenanceMachine', [MESUsageRateController::class, 'calculateMaintenanceMachine']);
    $router->get('calculatePQCProcessing', [MESUsageRateController::class, 'calculatePQCProcessing']);
    $router->get('calculateKhuonBe', [MESUsageRateController::class, 'calculateKhuonBe']);
    $router->get('getTableSystemUsageRate', [MESUsageRateController::class, 'getTableSystemUsageRate']);
    $router->get('cronjob', [MESUsageRateController::class, 'cronjob']);
    $router->get('retriveData', [MESUsageRateController::class, 'retriveData']);
    $router->get('deleteDuplicate', [ApiUIController::class, 'deleteDuplicate']);
    $router->post('capNhatTonKhoTPExcel', [ApiUIController::class, 'capNhatTonKhoTPExcel']);
    $router->get('reorderInfoCongDoan', [ApiController::class, 'reorderInfoCongDoan']);
});

