<?php

// use App\Http\Controllers\Api\V1\CustomerController;

// use App\Http\Controllers\Api\V1\CustomerController;

use App\Http\Controllers\ProductController;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Ip_api;
use App\Http\Controllers\Law_api;
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

header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, PATCH, DELETE');
header('Access-Control-Allow-Headers: Accept, Content-Type, X-Auth-Token, Origin, Authorization');

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


// Route::get('/UpdStatusPR/{postal_code}/{type}', [ProductController::class, 'UpdStatusPR']);

// Route::get('/dashbord_rm/{postal_code}', [ProductController::class, 'dashbord_rm']);

// Route::get('/Api_JobTagAndProdId/{JT_PID}', [ProductController::class, 'Api_JobTagOrProdId']);

// Route::get('/Api_JobTagOrProdId_Demo/{JT_PID}', [ProductController::class, 'Api_JobTagOrProdId_Demo']);
Route::post('/addProduct', [ProductController::class, 'addProduct']);
Route::post('/uploadFile', [ProductController::class, 'uploadFile']);
Route::post('/getData', [ProductController::class, 'getData']);
Route::post('/editProduct', [ProductController::class, 'editProduct']);
Route::get('/getsumAll', [ProductController::class, 'getsumAll']);
Route::get('/getNametype', [ProductController::class, 'getNametype']);
Route::get('/getYearAll', [ProductController::class, 'getYearAll']);
Route::get('/test', [ProductController::class, 'test']);
Route::get('/totalAlls', [ProductController::class, 'totalAlls']);
Route::post('/importData', [ProductController::class, 'importData']);
Route::post('/getfileOther', [ProductController::class, 'getfileOther']);
Route::post('/addfileOther', [ProductController::class, 'addfileOther']);
Route::post('/uploadFileOther', [ProductController::class, 'uploadFileOther']);
Route::post('/insert_step_1', [ProductController::class, 'insert_step_1']);
Route::post('/get_datastep_1', [ProductController::class, 'get_datastep_1']);
// Route::middleware(['cors'])->group(function () {
Route::post('/getdataEdit', [ProductController::class, 'getdataEdit']);
Route::post('/getSession', [ProductController::class, 'getSession']);
Route::post('/deleteForm', [ProductController::class, 'deleteForm']);
Route::post('/getstepNow', [ProductController::class, 'getstepNow']);
Route::post('/step_sup', [ProductController::class, 'step_sup']);
Route::post('/step_mgr', [ProductController::class, 'step_mgr']);
Route::post('/step_eva', [ProductController::class, 'step_eva']);
Route::post('/step_cmt', [ProductController::class, 'step_cmt']);
Route::post('/cancelKaizen', [ProductController::class, 'cancelKaizen']);
Route::post('/update_reject', [ProductController::class, 'update_reject']);
Route::post('/rejectKaizen', [ProductController::class, 'rejectKaizen']);
Route::post('/comment_reject', [ProductController::class, 'comment_reject']);
// });

Route::post('/import_file', [ProductController::class, 'import_file']);
Route::get('/get_Sup', [ProductController::class, 'get_Sup']);
Route::get('/get_ass_mgr', [ProductController::class, 'get_ass_mgr']);
//IP API //
Route::get('/GetDataProduct', [Ip_api::class, 'GetDataProduct']);

Route::post('/CreateData', [Ip_api::class, 'CreateData']);
Route::get('/get_ip_type', [Ip_api::class, 'get_ip_type']);
Route::get('/GetData', [Ip_api::class, 'GetData']);
Route::get('/GetDataDoc', [Ip_api::class, 'GetDataDoc']);
Route::get('/GetDataEdit', [Ip_api::class, 'GetDataEdit']);
Route::get('/GetDataConsider', [Ip_api::class, 'GetDataConsider']);
Route::post('/uploadFile_Ip_Demp', [Ip_api::class, 'uploadFile_Ip_Demp']);
Route::post('/DataManage', [Ip_api::class, 'DataManage']);
Route::post('/ManageDoc', [Ip_api::class, 'ManageDoc']);
Route::post('/uploadFile_Attach', [Ip_api::class, 'uploadFile_Attach']);
Route::post('/SearchPOA', [Ip_api::class, 'SearchPOA']);
Route::post('/ConsiderApproveFile', [Ip_api::class, 'ConsiderApproveFile']);
Route::post('/ManageConsider', [Ip_api::class, 'ManageConsider']);
Route::get('/GetDataLaw', [Ip_api::class, 'GetDataLaw']);
Route::get('/DownloadForm/{name}', [Ip_api::class, 'DownloadForm']);
Route::post('/getFile_req', [Ip_api::class, 'getFile_req']);
Route::post('/getFile_req_document', [Ip_api::class, 'getFile_req_document']);
Route::post('/getFile_Law', [Ip_api::class, 'getFile_Law']);
Route::post('/getFileWorkId', [Ip_api::class, 'getFileWorkId']);
Route::post('/Submit_Document', [Ip_api::class, 'Submit_Document']);
Route::post('/Submit_Form', [Ip_api::class, 'Submit_Form']);
Route::post('/InsertReqNum', [Ip_api::class, 'InsertReqNum']);
Route::post('/SendDocument', [Ip_api::class, 'SendDocument']);
Route::post('/ApproveFile_Dialog', [Ip_api::class, 'ApproveFile_Dialog']);

Route::get('/getProductGroup', [Ip_api::class, 'getProductGroup']);
Route::post('/getsubGroupIntellectual', [Ip_api::class, 'getsubGroupIntellectual']);
Route::get('/ExportExcel/{type}', [Ip_api::class, 'ExportExcel']);
Route::post('/Login', [Ip_api::class, 'Login']);

Route::post('/GetDataReportLaw', [Law_api::class, 'GetDataReportLaw']);
Route::post('/editFile_attach', [Law_api::class, 'editFile_attach']);
Route::post('/test_fetcharray', [Law_api::class, 'test_fetcharray']);
Route::post('/updateDataLaw', [Law_api::class, 'updateDataLaw']);
Route::post('/getmasterDoc', [IP_api::class, 'getmasterDoc']);
Route::post('/CreateDataMore', [IP_api::class, 'CreateDataMore']);
Route::post('/setFileFromEdit', [IP_api::class, 'setFileFromEdit']);
Route::post('/approveEdit', [IP_api::class, 'approveEdit']);

Route::post('/updateFormEdit', [IP_api::class, 'updateFormEdit']);
Route::post('/getFileDataSelect', [IP_api::class, 'getFileDataSelect']);
Route::post('/approveEditLaw', [IP_api::class, 'approveEditLaw']);
Route::post('/GetDataDocDialog', [IP_api::class, 'GetDataDocDialog']);
Route::post('/getDataFileShow', [IP_api::class, 'getDataFileShow']);

Route::post('/submitfile_to_consider', [IP_api::class, 'submitfile_to_consider']);
//IP API //
// Route::posProductController::class, 'savefile']);t('/savefile', [
//