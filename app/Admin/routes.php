<?php

use App\Admin\Controllers\ApiMobileController;
use App\Admin\Controllers\ApiUIController;
use App\Admin\Controllers\ApiController;
use App\Admin\Controllers\BuyerController;
use App\Admin\Controllers\CustomAdminController;
use App\Admin\Controllers\CustomerController;
use App\Admin\Controllers\DeliveryNoteController;
use App\Admin\Controllers\DepartmentController;
use App\Admin\Controllers\ErrorController;
use App\Admin\Controllers\ErrorMachineController;
use App\Admin\Controllers\InfoCongDoanController;
use App\Admin\Controllers\KhuonController;
use App\Admin\Controllers\KPIController;
use App\Admin\Controllers\LayoutController;
use App\Admin\Controllers\LineController;
use App\Admin\Controllers\LSXPalletController;
use App\Admin\Controllers\MachineController;
use App\Admin\Controllers\MaintenanceController;
use App\Admin\Controllers\MaterialController;
use App\Admin\Controllers\MESUsageRateController;
use App\Admin\Controllers\OrderController;
use App\Admin\Controllers\PermissionController;
use App\Admin\Controllers\RoleController;
use App\Admin\Controllers\ShiftAssignmentController;
use App\Admin\Controllers\ShiftController;
use App\Admin\Controllers\TestCriteriaController;
use App\Admin\Controllers\UserController;
use App\Admin\Controllers\UserLineMachineController;
use App\Admin\Controllers\UserMachineController;
use App\Admin\Controllers\VehicleController;
use App\Admin\Controllers\VOCRegisterController;
use App\Admin\Controllers\VOCTypeController;
use Encore\Admin\Facades\Admin;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

Admin::routes();


//API

// UI-API
Route::group([
    'prefix'        => "/api",
    'middleware'    => [],
    'as'            => "/api" . '.',
], function (Router $router) {
    $router->get('/warehouse/convert-import', [ApiMobileController::class, 'converImportWarehouseFG']);

    $router->get('/produce/history', [ApiUIController::class, 'produceHistory']);
    $router->get('/produce/fmb', [ApiUIController::class, 'fmb']);
    $router->get('/qc/history', [ApiUIController::class, 'qcHistory']);
    $router->get('/qc/detail-data-error', [ApiUIController::class, 'getDetailDataError']);

    $router->get('/machine/error', [ApiUIController::class, 'machineError']);

    $router->get('/warning/alert', [ApiUIController::class, 'getAlert']);
    $router->get('/machine/perfomance', [ApiUIController::class, 'apimachinePerfomance']);

    $router->get('/kpi', [ApiUIController::class, 'apiKPI']);

    $router->get('/oqc', [ApiUIController::class, 'oqc']);

    $router->get('/dashboard/monitor', [ApiMobileController::class, 'dashboardMonitor']);
    $router->post('/dashboard/insert-monitor', [ApiMobileController::class, 'insertMonitor']);
    $router->get('/dashboard/get-monitor', [ApiMobileController::class, 'getMonitor']);

    $router->get('/inventory', [ApiUIController::class, 'inventory']);
});

// END UI-API;

Route::group([
    'prefix'        => "/api",
    'middleware'    => "auth:sanctum",
    'as'            => "mobile/api" . '.',
], function (Router $router) {
    // USER
    $router->get('/user/info', [ApiMobileController::class, 'userInfo']);
    $router->get('/user/logout', [ApiMobileController::class, 'logout']);
    $router->post('/user/password/update', [ApiMobileController::class, 'userChangePassword']);


    // LINE
    $router->get('/line/list', [ApiMobileController::class, 'listLine']);
    $router->get('/line/list-machine', [ApiMobileController::class, 'listMachineOfLine']);

    $router->get('/scenario/list', [ApiMobileController::class, 'listScenario']);
    $router->post('/scenario/update', [ApiMobileController::class, 'updateScenario']);
    //
    $router->get('/warehouse/propose-import', [ApiMobileController::class, 'getProposeImport']);
    $router->post('/warehouse/import', [ApiMobileController::class, 'importWareHouse']);
    $router->get('/warehouse/list-import', [ApiMobileController::class, 'listImportWareHouse']);
    $router->get('/warehouse/info-import', [ApiMobileController::class, 'infoImportWareHouse']);
    $router->get('/warehouse/list-customer', [ApiMobileController::class, 'listCustomerExport']);
    $router->get('/warehouse/propose-export', [ApiMobileController::class, 'getProposeExport']);
    $router->post('/warehouse/export', [ApiMobileController::class, 'exportWareHouse']);
    $router->get('/warehouse/info-export', [ApiMobileController::class, 'infoExportWareHouse']);
    $router->get('/material/list-log', [ApiMobileController::class, 'listLogMaterial']);
    $router->post('/material/update-log', [ApiMobileController::class, 'updateLogMaterial']);
    $router->post('/material/update-log-record', [ApiMobileController::class, 'updateLogMaterialRecord']);
    $router->post('/material/store-log', [ApiMobileController::class, 'storeLogMaterial']);
    $router->get('/material/list-lsx', [ApiMobileController::class, 'listLsxUseMaterial']);
    $router->post('/barrel/split', [ApiMobileController::class, 'splitBarrel']);
    $router->get('/warehouse/history', [ApiMobileController::class, 'getHistoryWareHouse']);
    $router->delete('/warehouse-export/destroy', [ApiMobileController::class, 'destroyWareHouseExport']);
    $router->post('/warehouse-export/update', [ApiMobileController::class, 'updateWareHouseExport']);
    $router->post('/warehouse-export/create', [ApiMobileController::class, 'createWareHouseExport']);
    $router->get('/warehouse-export/get-thung', [ApiMobileController::class, 'prepareGT']);
    $router->post('/warehouse-export/gop-thung', [ApiMobileController::class, 'gopThungIntem']);

    //PLAN PRODUCTION

    $router->get('/plan/detail', [ApiMobileController::class, 'planDetail']);
    $router->get('/plan/list/machine', [ApiMobileController::class, 'planMachineDetail']);
    $router->get('/plan/lsx/list', [ApiMobileController::class, 'lsxList']);
    $router->get('/plan/lsx/detail', [ApiMobileController::class, 'lsxDetail']);
    $router->post('/plan/lsx/update', [ApiMobileController::class, 'lsxUpdate']);
    $router->get('/plan/lsx/log', [ApiMobileController::class, 'lsxLog']);
    $router->delete('product_plan/destroy', [ApiMobileController::class, 'destroyProductPlan']);
    $router->post('product_plan/store', [ApiMobileController::class, 'storeProductPlan']);
    $router->post('product_plan/update', [ApiMobileController::class, 'updateProductPlan']);
    $router->post('/plan/lsx/test', [ApiMobileController::class, 'lsxTest']);


    //Machine
    $router->get('/machine/detail', [ApiMobileController::class, 'detailMachine']);

    //Warehouse
    $router->get('/warehouse/product/detail', [ApiMobileController::class, 'productDetail']);
    $router->get('/warehouse/detail', [ApiMobileController::class, 'warehouseDetail']);
    $router->get('/warehouse/log', [ApiMobileController::class, 'warehouseLog']);
    $router->post('/warehouse/cell_product/update', [ApiMobileController::class, 'cellProductUpdate']);
    $router->get('/warehouse/material', [ApiMobileController::class, 'material']);
    $router->get('/warehouse/list', [ApiMobileController::class, 'warehouseList']);
    $router->get('/warehouse/cell/empty', [ApiMobileController::class, 'cellEmpty']);



    // Meterial

    $router->get('/material/list', [ApiMobileController::class, 'materialList']);
    $router->get('/material/detail', [ApiMobileController::class, 'materialDetail']);
    $router->post('/material/create', [ApiMobileController::class, 'materialCreate']);
    $router->get('/material/log', [ApiMobileController::class, 'materialLog']);


    //Color
    $router->get('/color/list', [ApiMobileController::class, 'colorList']);


    //Unusual
    $router->get('/machine/log', [ApiMobileController::class, 'machineLog']);
    $router->get('/reason/list', [ApiMobileController::class, 'reasonList']);
    $router->post('/machine/log/update', [ApiMobileController::class, 'machineLogUpdate']);
    $router->get('/machine/reason/list', [ApiMobileController::class, 'machineReasonList']);


    //UIUX

    $router->get('/ui/plan', [ApiMobileController::class, 'uiPlan']);
    // ui-MAIN
    $router->get('/ui/lines', [ApiMobileController::class, 'ui_getLines']);
    $router->get('/ui/line/list-machine', [ApiMobileController::class, 'ui_getLineListMachine']);
    $router->get('/ui/machines', [ApiMobileController::class, 'ui_getMachines']);
    $router->get('/ui/products', [ApiMobileController::class, 'ui_getProducts']);
    $router->get('/ui/staffs', [ApiMobileController::class, 'ui_getStaffs']);
    $router->get('/ui/lo-san-xuat', [ApiMobileController::class, 'ui_getLoSanXuat']);
    $router->get('/ui/warehouses', [ApiMobileController::class, 'ui_getWarehouses']);
    $router->get('/ui/ca-san-xuat-s', [ApiMobileController::class, 'ui_getCaSanXuats']);
    $router->get('/ui/errors', [ApiMobileController::class, 'ui_getErrors']);
    $router->get('/ui/errors-machine', [ApiMobileController::class, 'ui_getErrorsMachine']);

    $router->get('/ui/thong-so-may', [ApiMobileController::class, 'uiThongSoMay']);


    // Test Criteria
    $router->get('/testcriteria/list', [ApiMobileController::class, 'testCriteriaList']);
    $router->post('/testcriteria/result', [ApiMobileController::class, 'testCriteriaResult']);
    $router->get('/error/list', [ApiMobileController::class, 'errorList']);
    $router->get('/testcriteria/lsx/choose', [ApiMobileController::class, 'testCriteriaChooseLSX']);
    $router->get('/testcriteria/history', [ApiMobileController::class, 'testCriteriaHistory']);
    $router->get('/machine/info', [ApiMobileController::class, 'getInfoMachine']);


    $router->get('ui/manufacturing', [ApiMobileController::class, 'uiManufacturing']);
    $router->get('ui/quality', [ApiMobileController::class, 'uiQuality']);


    //MATERIAL

    //LOT /PALLET

    $router->get('lot/list', [ApiMobileController::class, 'palletList']);
    $router->delete('pallet/destroy', [ApiMobileController::class, 'destroyPallet']);
    $router->post('lot/update-san-luong', [ApiMobileController::class, 'updateSanLuong']);
    $router->get('lot/check-san-luong', [ApiMobileController::class, 'checkSanLuong']);
    $router->post('lot/bat-dau-tinh-dan-luong', [ApiMobileController::class, 'batDauTinhSanLuong']);
    $router->get('lot/detail', [ApiMobileController::class, 'detailLot']);

    // Production-Process

    $router->post('lot/scanPallet', [ApiMobileController::class, 'scanPallet']);

    $router->post('lot/input', [ApiMobileController::class, 'inputPallet']);
    $router->get('line/overall', [ApiMobileController::class, 'lineOverall']);
    $router->get('line/user', [ApiMobileController::class, 'lineUser']);
    $router->post('line/assign', [ApiMobileController::class, 'lineAssign']);
    $router->get('line/table/list', [ApiMobileController::class, 'listTable']);
    $router->post('line/table/work', [ApiMobileController::class, 'lineTableWork']);

    $router->post('lot/intem', [ApiMobileController::class, 'inTem']);



    //QC
    $router->post('qc/scanPallet', [ApiMobileController::class, 'scanPalletQC']);

    $router->get('qc/test/list', [ApiMobileController::class, 'testList']);
    $router->post('qc/test/result', [ApiMobileController::class, 'resultTest']);
    $router->post('qc/error/result', [ApiMobileController::class, 'errorTest']);
    $router->get('qc/overall', [ApiMobileController::class, 'qcOverall']);

    $router->post('qc/update-temvang', [ApiMobileController::class, 'updateSoLuongTemVang']);
    $router->post('qc/intemvang', [ApiMobileController::class, 'inTemVang']);
    $router->get('qc/pallet/info', [ApiMobileController::class, 'infoQCPallet']);
    $router->get('qc/losx/detail', [ApiMobileController::class, 'detailLoSX']);

    //DASHBOARD


    $router->get('dashboard/giam-sat', [ApiMobileController::class, 'dashboardGiamSat']);
    $router->get('dashboard/giam-sat-chat-luong', [ApiMobileController::class, 'dashboardGiamSatChatLuong']);

    $router->get('dashboard/status', [ApiMobileController::class, 'dashboardKhiNen']);

    $router->get('dashboard/sensor', [ApiMobileController::class, 'dashboardSensor']);

    //Parameters
    $router->get('machine/parameters', [App\Admin\Controllers\ApiMobileController::class, 'getMachineParameters']);
    $router->post('machine/parameters/update', [App\Admin\Controllers\ApiMobileController::class, 'updateMachineParameters']);

    $router->get('lot/table-data-chon', [ApiMobileController::class, 'getTableAssignData']);


    $router->post('machine/machine-log/save', [ApiMobileController::class, 'logsMachine_save']);
    $router->post('update/test', [ApiMobileController::class, 'updateWarehouseEportPlan']);

    //Monitor 
    $router->get('/monitor/history', [ApiMobileController::class, 'historyMonitor']);

    $router->get('/info/chon', [ApiMobileController::class, 'infoChon']);

    $router->get('/iot/status', [ApiMobileController::class, 'statusIOT']);
    $router->get('/list-product', [ApiMobileController::class, 'listProduct']);
    $router->post('/tao-tem', [ApiMobileController::class, 'taoTem']);
});



Route::group([
    'prefix'        => "/api",
    'middleware'    => [],
    'as'            => "mobile/api" . '.',
], function (Router $router) {
    $router->post('/upload-ke-hoach-xuat-kho-tong', [ApiMobileController::class, 'uploadKHXKT']);
    $router->post('/upload-ke-hoach-san-xuat', [ApiMobileController::class, 'uploadKHSX']);
    $router->post('/upload-ton-kho', [ApiMobileController::class, 'uploadTonKho']);
    $router->post('/upload-buyer', [ApiController::class, 'uploadBUYER']);
    $router->post('/upload-layout', [ApiController::class, 'uploadLAYOUT']);
    $router->post('/lot/store', [ApiMobileController::class, 'storeLot']);
    $router->get('lot/list-table', [ApiMobileController::class, 'listLot']);
    $router->post('/upload-ke-hoach-xuat-kho', [ApiMobileController::class, 'uploadKHXK']);
    $router->get('/production-plan/list', [ApiMobileController::class, 'getListProductionPlan']);
    $router->get('/warehouse/list-export-plan', [ApiMobileController::class, 'getListWareHouseExportPlan']);

    //// ROUTE CỦA AN
    $router->get('line/list-machine', [ApiMobileController::class, 'getMachineOfLine']);
    $router->get('line/machine/check-sheet', [ApiMobileController::class, 'getChecksheetOfMachine']);
    $router->post('line/check-sheet-log/save', [ApiMobileController::class, 'lineChecksheetLogSave']);
    $router->get('line/error', [ApiMobileController::class, 'lineError']);
    $router->get('machine/overall', [ApiMobileController::class, 'machineOverall']);
    ///HẾT

    //EXPORT
    $router->get('/export/machine_error', [ApiUIController::class, 'exportMachineError']);
    $router->get('/export/thong-so-may', [ApiUIController::class, 'exportThongSoMay']);
    $router->get('/export/warehouse/history', [ApiUIController::class, 'exportHistoryWarehouse']);
    $router->get('/export/oqc', [ApiUIController::class, 'exportOQC']);
    $router->get('/export/qc-history', [ApiUIController::class, 'exportQCHistory']);
    $router->get('/export/report-qc', [ApiUIController::class, 'exportReportQC']);
    $router->get('/export/report-produce-history', [ApiUIController::class, 'exportReportProduceHistory']);
    $router->get('/export/warehouse/summary', [ApiUIController::class, 'exportSummaryWarehouse']);
    $router->get('/export/warehouse/bmcard', [ApiUIController::class, 'exportBMCardWarehouse']);
    $router->get('/export/kpi', [ApiUIController::class, 'exportKPI']);
    $router->get('/export/history-monitors', [ApiUIController::class, 'exportHistoryMonitors']);

    $router->get('ui/data-filter', [ApiUIController::class, 'getDataFilterUI']);
});


// Route::group([
//     'prefix'        => "/api",
//     'middleware'    => [],
//     'as'            => "/api" . '.',
// ], function (Router $router) {
//     $router->post('/login', [ApiMobileController::class, 'login']);
// });
Route::group([
    'prefix'        => "/api",
    'middleware'    => [],
    'as'            => '',
], function (Router $router) {
    $router->post('websocket', [ApiController::class, 'websocket']);
    $router->post('websocket-machine-status', [ApiController::class, 'websocketMachineStatus']);
    $router->post('websocket-machine-params', [ApiController::class, 'websocketMachineParams']);
});

Route::group([
    'prefix'        => "/api/oi",
    'middleware'    => "auth:sanctum",
    'as'            => '',
], function (Router $router) {
    $router->get('machine/list', [ApiController::class, 'listMachine']);

    $router->get('manufacture/tracking-status', [ApiController::class, 'getTrackingStatus']);
    $router->get('manufacture/current', [ApiController::class, 'getCurrentManufacturing']);
    $router->post('manufacture/start-produce', [ApiController::class, 'startProduce']);
    $router->post('manufacture/stop-produce', [ApiController::class, 'stopProduce']);
    $router->get('manufacture/overall', [ApiController::class, 'getManufactureOverall']);
    $router->get('manufacture/list-lot', [ApiController::class, 'listLotOI']);
    $router->get('manufacture/intem', [ApiController::class, 'inTem']);
    $router->post('manufacture/scan', [ApiController::class, 'scan'])->middleware('prevent-duplicate-requests');
    $router->post('manufacture/start-tracking', [ApiController::class, 'startTracking']);
    $router->post('manufacture/stop-tracking', [ApiController::class, 'stopTracking']);
    $router->post('manufacture/reorder-priority', [ApiController::class, 'reorderPriority']);
    $router->get('manufacture/paused-plan-list', [ApiController::class, 'getPausedPlanList']);
    $router->post('manufacture/pause-plan', [ApiController::class, 'pausePlan']);
    $router->post('manufacture/resume-plan', [ApiController::class, 'resumePlan']);
    $router->post('manufacture/update-quantity-info-cong-doan', [ApiController::class, 'updateQuantityInfoCongDoan'])->middleware('prevent-duplicate-requests');
    $router->post('manufacture/delete-paused-plan-list', [ApiController::class, 'deletePausedPlanList']);

    $router->post('manufacture/manual/input', [ApiController::class, 'manualInput']);
    $router->post('manufacture/manual/scan', [ApiController::class, 'scanManual'])->middleware('prevent-duplicate-requests');
    $router->get('manufacture/manual/list', [ApiController::class, 'manualList']);
    $router->post('manufacture/manual/print', [ApiController::class, 'manualPrintStamp']);

    $router->get('qc/check-permission', [ApiController::class, 'checkUserPermission']);
    $router->get('qc/line', [ApiController::class, 'getQCLine']);

    $router->get('pqc/error/list', [ApiController::class, 'getLoiNgoaiQuanPQC']);
    $router->get('pqc/checksheet/list', [ApiController::class, 'getLoiTinhNangPQCTest']);
    $router->post('pqc/save-result', [ApiController::class, 'saveQCResult'])->middleware('prevent-duplicate-requests');
    $router->get('pqc/lot/list', [ApiController::class, 'pqcLotList']);
    $router->get('pqc/overall', [ApiController::class, 'pqcOverall']);

    $router->get('iqc/error/list', [ApiController::class, 'getLoiNgoaiQuanIQC']);
    $router->get('iqc/checksheet/list', [ApiController::class, 'getLoiTinhNangIQC']);
    $router->post('iqc/save-result', [ApiController::class, 'saveIQCResult'])->middleware('prevent-duplicate-requests');
    $router->get('iqc/lot/list', [ApiController::class, 'iqcLotList']);
    $router->get('iqc/overall', [ApiController::class, 'iqcOverall']);

    $router->get('equipment/overall', [ApiController::class, 'overallMachine']);
    $router->get('equipment/mapping-list', [ApiController::class, 'getMappingList']);
    $router->get('equipment/error/log', [ApiController::class, 'errorMachineLog']);
    $router->get('equipment/error/list', [ApiController::class, 'errorMachineList']);
    $router->get('equipment/error/detail', [ApiController::class, 'errorMachineDetail']);
    $router->post('equipment/error/result', [ApiController::class, 'errorMachineResult']);
    $router->get('equipment/parameters', [ApiController::class, 'getMachineParameters']);
    $router->get('equipment/parameters/list', [ApiController::class, 'getMachineParameterList']);
    $router->post('equipment/parameters/save', [ApiController::class, 'saveMachineParameters'])->middleware('prevent-duplicate-requests');
    $router->get('equipment/mapping/list', [ApiController::class, 'getListMappingRequire']);
    $router->get('equipment/mapping/check-material', [ApiController::class, 'checkMapping']);
    $router->post('equipment/mapping/result', [ApiController::class, 'resultMapping'])->middleware('prevent-duplicate-requests');


    $router->get('warehouse/mlt/import/log', [ApiController::class, 'importMLTLog']);
    $router->get('warehouse/mlt/import/scan', [ApiController::class, 'importMLTScan']);
    $router->post('warehouse/mlt/import/save', [ApiController::class, 'importMLTSave'])->middleware('prevent-duplicate-requests');
    $router->post('warehouse/mlt/import/reimport', [ApiController::class, 'importMLTReimport'])->middleware('prevent-duplicate-requests');
    $router->get('warehouse/mlt/import/overall', [ApiController::class, 'importMLTOverall']);
    $router->post('warehouse/mlt/import/warehouse13', [ApiController::class, 'handleNGMaterial'])->middleware('prevent-duplicate-requests');;

    $router->get('warehouse/mlt/export/log-list', [ApiController::class, 'getExportMLTLogs']);
    $router->get('warehouse/mlt/export/scan', [ApiController::class, 'exportMLTScan']);
    $router->get('warehouse/mlt/export/result', [ApiController::class, 'updateExportMLTLogs'])->middleware('prevent-duplicate-requests');
    $router->get('warehouse/mlt/export/list', [ApiController::class, 'exportMLTList']);
    $router->post('warehouse/mlt/export/save', [ApiController::class, 'exportMLTSave'])->middleware('prevent-duplicate-requests');

    $router->get('warehouse/fg/list-pallet', [ApiController::class, 'listPallet']);
    $router->get('warehouse/fg/info-pallet', [ApiController::class, 'infoPallet']);
    // $router->get('warehouse/fg/overall', [ApiController::class, 'getOverallWarehouseFG']);
    $router->get('warehouse/fg/list', [ApiController::class, 'getWarehouseFGLogs']);
    $router->get('warehouse/fg/suggest-pallet', [ApiController::class, 'suggestPallet']);
    $router->get('warehouse/fg/quantity-lot', [ApiController::class, 'quantityLosx']);
    $router->post('warehouse/fg/store-pallet', [ApiController::class, 'storePallet'])->middleware('prevent-duplicate-requests');
    $router->post('warehouse/fg/update-pallet', [ApiController::class, 'updatePallet'])->middleware('prevent-duplicate-requests');
    $router->post('warehouse/fg/import/save', [ApiController::class, 'importFGSave'])->middleware('prevent-duplicate-requests');
    $router->get('warehouse/fg/import/logs', [ApiController::class, 'getLogImportWarehouseFG']);
    $router->get('warehouse/fg/overall', [ApiController::class, 'getFGOverall']);
    $router->get('warehouse/fg/check-losx', [ApiController::class, 'checkLosx']);

    $router->get('warehouse/fg/export/logs', [ApiController::class, 'getLogExportWarehouseFG']);
    $router->get('warehouse/fg/export/list-delivery-note', [ApiController::class, 'getDeliveryNoteList']);
    $router->get('warehouse/fg/export/check-pallet', [ApiController::class, 'checkLoSXPallet']);
    $router->post('warehouse/fg/export/handle-export-pallet', [ApiController::class, 'exportPallet'])->middleware('prevent-duplicate-requests');
    $router->get('warehouse/fg/export/download-delivery-note', [ApiController::class, 'exportWarehouseFGDeliveryNote']);
});

Route::group([
    'prefix'        => "/api/ui",
    'middleware'    => "auth:sanctum",
    'as'            => '',
], function (Router $router) {

    $router->get('customers', [ApiController::class, 'ui_getCustomers']); //Not modified
    $router->get('orders', [ApiController::class, 'ui_getOrders']); //Added pagination
    $router->get('lo_sx', [ApiController::class, 'ui_getLoSanXuat']); //Wrong Model name?

    $router->get('machine/list', [ApiController::class, 'listMachineUI']);
    $router->get('manufacture/line', [ApiController::class, 'lineList']);
    $router->get('manufacture/production-plan/list', [ApiController::class, 'productionPlan']);
    $router->post('manufacture/handle-plan', [ApiController::class, 'handlePlan']);
    $router->post('manufacture/production-plan/handle', [ApiController::class, 'handleProductionPlan']);
    $router->get('manufacture/production-plan/export', [ApiController::class, 'exportKHSX']);
    $router->post('manufacture/production-plan/export-preview-plan', [ApiController::class, 'exportPreviewPlan']);
    $router->post('manufacture/production-plan/export-preview-plan-xa-lot', [ApiController::class, 'exportPreviewPlanXaLot']);
    $router->get('manufacture/production-plan/export-xa-lot', [ApiController::class, 'exportKHXaLot']);
    $router->get('manufacture/produce-percent', [ApiController::class, 'producePercent']);
    $router->get('manufacture/produce-overall', [ApiController::class, 'produceOverall']);
    $router->get('manufacture/produce-table', [ApiController::class, 'produceHistory']);
    $router->get('export/produce/history', [ApiController::class, 'exportProduceHistory']);
    $router->delete('manufacture/production-histoy/delete/{id}', [ApiController::class, 'deleteProductionHistory']);

    $router->get('manufacture/order/list', [ApiController::class, 'getOrderList']);

    $router->get('manufacture/layout/list', [ApiController::class, 'listLayout']);
    $router->get('manufacture/drc/list', [ApiController::class, 'listDRC']);
    $router->get('manufacture/buyer/list', [ApiController::class, 'listBuyer']);

    $router->post('manufacture/handle-order', [ApiController::class, 'handleOrder']);
    $router->post('manufacture/create-plan', [ApiController::class, 'createProductionPlan']);

    $router->post('manufacture/tem/upload', [ApiController::class, 'uploadTem']);
    $router->get('manufacture/tem/list', [ApiController::class, 'listTem']);
    $router->post('manufacture/tem/update', [ApiController::class, 'updateTem']);
    $router->post('manufacture/tem/create-from-order', [ApiController::class, 'createStampFromOrder']);
    $router->delete('manufacture/tem/delete/{id}', [ApiController::class, 'deleteTem']);

    $router->get('quality/overall', [ApiController::class, 'qualityOverall']);
    $router->get('quality/table-error-detail', [ApiController::class, 'errorTable']);
    $router->post('quality/recheck', [ApiController::class, 'recheckQC']);
    $router->get('quality/error-trending', [ApiController::class, 'errorQC']);
    $router->get('quality/top-error', [ApiController::class, 'topErrorQC']);
    $router->get('quality/qc-history', [ApiController::class, 'qcHistory']);
    $router->get('quality/qc-history/export', [ApiController::class, 'exportQCHistory']);
    $router->get('quality/iqc-history', [ApiController::class, 'iqcHistory']);
    $router->get('quality/iqc-history/export', [ApiController::class, 'exportIQCHistory']);

    $router->get('equipment/performance', [ApiController::class, 'machinePerformance']);
    $router->get('equipment/error-machine-list', [ApiController::class, 'getErrorMachine']);
    $router->get('equipment/error-machine-list/export', [ApiController::class, 'exportErrorMachine']);
    $router->get('equipment/get-machine-param-logs', [ApiController::class, 'machineParameterTable']);
    $router->get('equipment/error-machine-frequency', [ApiController::class, 'errorMachineFrequency']);
    $router->get('equipment/parameter-machine-chart', [ApiController::class, 'getMachineParameterChart']);

    $router->get('warehouse/list-material-import', [ApiController::class, 'listMaterialImport']);
    $router->patch('warehouse/update-material-import', [ApiController::class, 'updateWarehouseMTLImport']);
    $router->post('warehouse/delete-material-import', [ApiController::class, 'deleteWarehouseMTLImport']);
    $router->post('warehouse/create-material-import', [ApiController::class, 'createWarehouseMTLImport']);
    $router->get('warehouse/list-material-export', [ApiController::class, 'listMaterialExport']);
    $router->get('warehouse/export-list-material-export', [ApiController::class, 'exportListMaterialExport']);
    $router->get('warehouse/import-material-ticket/export', [ApiController::class, 'exportWarehouseTicket']);
    $router->get('warehouse/vehicle-weight-ticket/export', [ApiController::class, 'exportVehicleWeightTicket']);

    $router->get('warehouse/fg/export/list', [ApiController::class, 'getWarehouseFGExportList']);
    $router->post('warehouse/fg/export/update', [ApiController::class, 'updateWarehouseFGExport']);
    $router->post('warehouse/fg/export/create', [ApiController::class, 'createWarehouseFGExport'])->middleware('prevent-duplicate-requests');;
    $router->delete('warehouse/fg/export/delete/{id}', [ApiController::class, 'deleteWarehouseFGExport']);
    $router->get('warehouse/list-pallet', [ApiController::class, 'getListPalletWarehouse']);
    $router->get('warehouse/fg/export/list/export', [ApiController::class, 'exportWarehouseFGExportList']);
    $router->post('warehouse/fg/update-export-log', [ApiController::class,'updateExportFGLog']);

    $router->get('item-menu', [ApiController::class, 'getUIItemMenu']);
    $router->get('warehouse/mtl/goods-receipt-note', [ApiController::class, 'getGoodsReceiptNote']);
    $router->patch('goods-receipt-note/update', [ApiController::class, 'updateGoodsReceiptNote']);
    $router->delete('goods-receipt-note/delete', [ApiController::class, 'deleteGoodsReceiptNote']);

    $router->get('warehouse/mlt/log', [ApiController::class, 'warehouseMLTLog']);
    $router->get('export/warehouse-mlt-logs', [ApiController::class, 'exportWarehouseMLTLog']);

    $router->get('warehouse/fg/log', [ApiController::class, 'warehouseFGLog']);
    $router->get('export/warehouse-fg-logs', [ApiController::class, 'exportWarehouseFGLog']);
    $router->get('warehouse/fg/export/log-list', [ApiController::class, 'warehouseFGExportList']);
    $router->get('warehouse/fg/export/plan/list', [ApiController::class, 'getWarehouseFGExportPlan']);
    $router->post('warehouse/fg/export/plan/divide', [ApiController::class, 'divideFGExportPlan']);

    $router->get('delivery-note/list', [DeliveryNoteController::class, 'getDeliveryNoteList']);
    $router->post('delivery-note/create', [DeliveryNoteController::class, 'createDeliveryNote']);
    $router->post('delivery-note/delete', [DeliveryNoteController::class, 'deleteDeliveryNote']);
    $router->patch('delivery-note/update', [DeliveryNoteController::class, 'updateDeliveryNote']);

    $router->get('lsx-pallet/list', [LSXPalletController::class, 'getLSXPallet']);
    // $router->post('lsx-pallet/create', [LSXPalletController::class, 'createLSXPallet']);
    // $router->post('lsx-pallet/delete', [LSXPalletController::class, 'deleteLSXPallet']);
    // $router->patch('lsx-pallet/update', [LSXPalletController::class, 'updateLSXPallet']);
    $router->get('lsx-pallet/export', [LSXPalletController::class, 'exportLSXPallet']);
    $router->get('lsx-pallet/print-pallet', [LSXPalletController::class, 'printPallet']);
});