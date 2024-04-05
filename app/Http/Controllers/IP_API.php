<?php

namespace App\Http\Controllers;

use App\Exports\ExportExcel;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Faker\Provider\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Contracts\Session\Session;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PHPUnit\Util\Test;

/* CORS API */

/* CORS API */

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

date_default_timezone_set('Asia/Bangkok');

class Ip_api extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return null;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function GetDataProduct()
    {
        $db = DB::connection('mysql_ip_demo')->table('ip_data')->join('ip_status', 'ip_data.status', '=', 'ip_status.id')->where('ip_data.active', '1')->where('ip_data.status', '>=', '8')->where('fk_table_more', NULL)->get();

        return json_encode($db);
    }

    public function CreateData(Request $request)
    {


        $data = $_POST;
        $date = date('Y-m-d H:i:s');
        $yearNow = date('Y');
        $time = date('H:i:s');
        $fullname_th = $_POST['product_type'];

        $db_query = DB::connection('mysql_ip_demo')->select('SELECT name FROM ip_type WHERE fullname_th = ?', [$fullname_th]);

        $file = $request->file('file');
        if ($file) :
            $originalName = $request->file->hashName();
            $path = "public/uploads/ip_demo/" . $db_query[0]->name . '/' . $originalName;
            Storage::disk('local')->put($path, file_get_contents($request->file));
        else :
            $originalName = '';
        endif;

        // $db_check_num_workId = DB::connection('mysql_ip_demo')->table('numworkid')->where('type', $db_query[0]->name)->where('year', $yearNow)->get();
        $db_check_num_workId = DB::connection('mysql_ip_demo')->select('SELECT * FROM numworkid WHERE type = ? AND year = ?', [$db_query[0]->name, $yearNow]);
        if ($db_check_num_workId) :
            $newnumworkId = $db_check_num_workId[0]->number + 1;
            if ($db_check_num_workId[0]->number >= 9) :
                $numWorkIdNow = '0' . $newnumworkId;
            elseif ($db_check_num_workId[0]->number >= 99) :
                $numWorkIdNow = $newnumworkId;
            else :
                $numWorkIdNow = '00' . $newnumworkId;
            endif;
            $dbUpdateNumWorkId = DB::connection('mysql_ip_demo')->table('numworkid')->where('type', $db_query[0]->name)->where('year', $yearNow)->update([
                'number' =>  $numWorkIdNow,
            ]);
            echo 'if';
        else :
            $numWorkIdNow = '001';
            $dbInsertNumWorkId = DB::connection('mysql_ip_demo')->table('numworkid')->insert([
                'type' => $db_query[0]->name,
                'number' => 1,
                'year' => $yearNow
            ]);
            echo 'else';
        endif;



        $db = DB::connection('mysql_ip_demo')->table('ip_data')->insert([
            'workid' => $_POST['workid'] . substr($yearNow, 2) . $numWorkIdNow,
            'date_create' => $date,
            // 'reqnum' => $_POST['reqnum'],
            'type' => $db_query[0]->name,
            'product_name' => $_POST['product_name'],
            'img' => $_POST['img'],
            'img_random' => $originalName,
            'country' => $_POST['country'],
            'statusreq' => 1,
            'product_type' => $_POST['product_type'],
            'description' => $_POST['description'],
            'linkother' => $_POST['linkother'],
            'important' => $_POST['important'],
            'work_start' => $_POST['workstart'] . ' ' . $time,
            'deadline' => $_POST['deadline'] . ' ' . $time,
            'operator' => $_POST['operator'],
            'status' => 1,
        ]);

        $dbSelectLast = DB::connection('mysql_ip_demo')->table('ip_data')->orderBy('id', 'DESC')->take(1)->get();
        $db_insert_doc = DB::connection('mysql_ip_demo')->table('ip_document')->insert([
            'ip_id' => $dbSelectLast[0]->id,
            'status_doc' => 'req_doc',
            'year' => $yearNow
        ]);
        $dbselectDocument = DB::connection('mysql_ip_demo')->table('ip_type')->join('master_document', 'master_document.document_type', '=', 'name')->where('name', $db_query[0]->name)->first();
        for ($i = 1; $i <= 11; $i++) :
            if ($dbselectDocument->{'form_' . $i} == 1) :
                $dbInsert_file = DB::connection('mysql_ip_demo')->table('ip_file')->insert([
                    'file_name' => '',
                    'file_name_random' => '',
                    'ip_workid' => $dbSelectLast[0]->workid,
                    'file_type' => '',
                    'file_status' => 'req_doc',
                    'typeInsert' => 'C',
                    'file_step' => 'wait',
                    'id_ip_file' => $dbSelectLast[0]->id,
                    'sfr_id' => '1',
                    'ms_doc_id' => $i,
                    'active' => '1',
                    'year' => $yearNow
                ]);
            endif;
        endfor;

        return json_encode('[CREATE] => OK');
    }

    public function get_ip_type()
    {
        $db = DB::connection('mysql_ip_demo')->select('SELECT fullname_th AS name,name AS code FROM ip_type WHERE active = 1');

        return json_encode($db);
    }


    public function GetData()
    {
        $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_data LEFT JOIN ip_edit_status ON edit_status = edit_id WHERE active = 1 AND status < 8');
        for ($i = 0; $i < count($db); $i++) :
            if (strlen($db[$i]->img_random) > 0 && $db[$i]->img_random !== 'undefined') :

                $img = $db[$i]->img_random;
                $image = storage_path('app/public/uploads/ip_demo/' . $db[$i]->type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . $img);
                /* set statusname */
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
                $db[$i]->status_name = $db_status[0]->name;
            /* set statusname */
            else :
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
                $db[$i]->status_name = $db_status[0]->name;
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . 'noimage.jpg');
            endif;
        endfor;
        return json_encode($db);
    }

    // public function uploadFile_Ip_Demp(Request $request)
    // {
    //     $file = $request->file('file');
    //     $originalName = $request->file->getClientOriginalName();
    //     $path = "public/uploads/ip_demo/" . $originalName;
    //     Storage::disk('local')->put($path, file_get_contents($request->file));
    // }

    public function ManageDoc()
    {
        $Id = $_POST['Id'];
        $skip = $_POST['Skip'];
        // $db = DB::connection('mysql_ip_demo')->select('SELECT id,img,status,product_name FROM ip_data WHERE id = ?', [$Id]);
        if ($skip === 'patent_description') :
            $db = DB::connection('mysql_ip_demo')->table('ip_document')->insert([
                'ip_id' => $Id,
                'status_doc' => 'Consider_req_doc',
                'year' => date('Y')
            ]);
            return json_encode('[INSERTFILEOTHER => OK');
        else :
            $dbData = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->first();
            $Data = $dbData;
            /* ทำการเปลี่ยน status บันทึกข้อมูลใหม่เก็บข้อมูลเก่า */
            if ($dbData->status < 3) :
                $dbUpdate = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->update(['active' => 0]);
                $dbInsert = DB::connection('mysql_ip_demo')->table('ip_data')->insert([
                    'workid' => $Data->workid,
                    'type' => $Data->type,
                    'date_create' => $Data->date_create,
                    'reqnum' => $Data->reqnum,
                    'product_name' => $Data->product_name,
                    'img' => $Data->img,
                    'img_random' => $Data->img_random,
                    'country' => $Data->country,
                    'statusreq' => $Data->statusreq,
                    'product_type' => $Data->product_type,
                    'description' => $Data->description,
                    'linkother' => $Data->linkother,
                    'important' => $Data->important,
                    'operator' => $Data->operator,
                    'work_start' => date("Y-m-d H:i:s"),
                    'status' => '3'
                ]);
                /* ทำการเปลี่ยน status บันทึกข้อมูลใหม่เก็บข้อมูลเก่า */

                $dbNewData = DB::connection('mysql_ip_demo')->table('ip_data')->where('workid', $Data->workid)->where('active', 1)->first();
                // $dbUpdatePkFile = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $Id)->update(['ip_ip_file' => $dbNewData->id]);

                $db = DB::connection('mysql_ip_demo')->table('ip_document')->insert([
                    'ip_id' => $dbNewData->id,
                    'status_doc' => 'req_doc',
                    'year' => date('Y')
                ]);
                $db_doc = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_document WHERE status_doc = ? AND ip_id = ?', ['req_doc', $dbNewData->id]);
                if ($db_doc) :
                    return json_encode(true); ///
                else :
                    return json_encode(false);
                endif;
            else :
                return json_encode('[ERROR] => MULTIPLE DATA STEP');
            endif;
        endif;
    }
    public function GetDataDoc()
    {
        // $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file JOIN ip_data ON id_ip_file = ip_data.id JOIN status_file_req ON sfr_id = status_file_req.id JOIN document_file ON ms_doc_id = document_file.id WHERE IN ip_file.file_step = ? AND ip_file.active = ?', ['wait', '1']);
        $db = DB::connection('mysql_ip_demo')->table('ip_file')->join('ip_data', 'id_ip_file', '=', 'ip_data.id')->join('status_file_req', 'sfr_id', '=', 'status_file_req.id')->join('document_file', 'ms_doc_id', '=', 'document_file.id')->whereIn('ip_file.file_step', ['wait', 'Reject'])->where('ip_file.active', '1')->groupBy('ip_workid')->get();
        for ($i = 0; $i < count($db); $i++) :
            if (strlen($db[$i]->img_random) > 0 && $db[$i]->img_random !== 'undefined') :

                $img = $db[$i]->img_random;
                $image = storage_path('app/public/uploads/ip_demo/' . $db[$i]->type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . $img);
                /* set statusname */
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
                $db[$i]->status_name = $db_status[0]->name;
            /* set statusname */
            else :
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . 'noimage.jpg');
            endif;
        endfor;
        return json_encode($db);
    }
    public function GetDataDocDialog()
    {
        // $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file JOIN ip_data ON id_ip_file = ip_data.id JOIN status_file_req ON sfr_id = status_file_req.id JOIN document_file ON ms_doc_id = document_file.id WHERE IN ip_file.file_step = ? AND ip_file.active = ?', ['wait', '1']);
        $id = $_POST['id'];
        $dbselect = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $id)->where('active', '1')->first();
        $db = DB::connection('mysql_ip_demo')->table('ip_file')->join('ip_data', 'id_ip_file', '=', 'ip_data.id')->join('status_file_req', 'sfr_id', '=', 'status_file_req.id')->join('document_file', 'ms_doc_id', '=', 'document_file.id')->whereIn('ip_file.file_step', ['wait', 'Reject'])->where('ip_workid', $dbselect->workid)->whereIn('typeInsert', ['C', 'U'])->where('ip_file.active', '1')->get();
        for ($i = 0; $i < count($db); $i++) :
            if (strlen($db[$i]->img_random) > 0 && $db[$i]->img_random !== 'undefined') :

                $img = $db[$i]->img_random;
                $image = storage_path('app/public/uploads/ip_demo/' . $db[$i]->type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . $img);
                /* set statusname */
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
                $db[$i]->status_name = $db_status[0]->name;
            /* set statusname */
            else :
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . 'noimage.jpg');
            endif;
        endfor;
        return json_encode($db);
    }


    public function getFile_req()
    {
        $Id = $_POST['Id'];
        $doc_Id = $_POST['doc_Id'];
        $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file WHERE file_status = ? AND id_ip_file = ? AND active = ?', [$doc_Id, $Id, '1']);
        return json_encode($db);
    }
    public function getFile_req_document()
    {
        $Id = $_POST['Id'];
        $doc_Id = $_POST['doc_Id'];
        $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file WHERE id_ip_file = ? AND active = ?', [$Id, '1']);
        return json_encode($db);
    }

    public function uploadFile_Attach(Request $request)
    {

        $Id_ip_file = $_POST['id_ip_file'];
        $Id_ms = $_POST['ms_doc_id'];
        $file_name = $_POST['file_name'];
        $req_file = $_POST['status_doc'];
        $file_step = $_POST['file_step'];
        $filePaths = [];

        // $Id_Before = $dbWorkId;
        // var_dump($request->file('files'));

        $getId_Ms = DB::connection('mysql_ip_demo')->table('document_file')->where('status_document_file', $file_step)->first();
        $dbWorkId = DB::connection('mysql_ip_demo')->table('ip_file')->join('ip_data',  'id_ip_file', '=', 'ip_data.id')->where('id_ip_file', $Id_ip_file)->where('ms_doc_id', $getId_Ms->id)->where('ip_file.active', 1)->where('ip_data.active', 1)->first();
        $db_check = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file WHERE ms_doc_id = ? AND id_ip_file = ? AND active = ? ', [$getId_Ms->id, $Id_ip_file, '1']);
        if ($db_check) :
            // $db_update = DB::connection('mysql_ip_demo')->table('ip_file')->where('ms_doc_id', $getId_Ms->id)->where('id_ip_file', $Id_ip_file)->update(
            //     [
            //         'active' => '0'
            //     ]
            // );
            $typeinsert = 'I';
        else :
            $typeinsert = 'I';
        endif;
        $file_name_array = [];
        $file_name_array[] = $request->input('file_name');

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $key => $file) {
                $originalName = $file->hashName();
                $path = "public/uploads/ip_demo/Attachments/" . $dbWorkId->type . '/' . $dbWorkId->workid . '/' . $originalName;
                Storage::disk('local')->put($path, file_get_contents($file));

                DB::connection('mysql_ip_demo')->table('ip_file')->insert(
                    [
                        // 'file_name' => $request->input('file_name'),
                        'file_name_random' => $originalName,
                        // 'workid' => $WorkIdImport,
                        'file_type' => $file->getClientOriginalExtension(),
                        'file_status' => $req_file,
                        'id_ip_file' => $Id_ip_file,
                        'file_step' => 'wait',
                        'typeInsert' => $typeinsert,
                        'ip_workid' => $db_check[0]->ip_workid,
                        // 'id_ip_file' => $Id_ip_file,
                        'ms_doc_id' => $getId_Ms->id,
                        'sfr_id' => '2',
                        'year' => date('Y'),
                        'lastupdate' => 'jakkawan.s'
                    ]
                );
            }
        }

        return response()->json(['message' => 'Files uploaded successfully']);
    }

    public function Submit_Document()
    {
        $Id = $_POST['Id'];
        $db_clear = DB::connection('mysql_ip_demo')->table('ip_document')->where('ip_id', $Id)->update(['status_doc' => 'Consider_req_doc']);
        return json_encode('[SUBMITFORM] => OK');
    }
    public function SearchPOA()
    {
        $Id = $_POST['Id'];
        $db = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $Id)->where('active', '1')->where('file_status', 'Approve_file')->get();

        if (isset($db)) :
            return json_encode($db);
        else :
            return json_encode('NO DATA');
        endif;
    }

    public function getDataConsider()
    {
        $dbNew = array();
        $test = 'no';
        $db = DB::connection('mysql_ip_demo')->table('ip_data')->join('ip_file', 'ip_data.id', '=', 'ip_file.id_ip_file')->leftjoin('ip_edit_status', 'edit_status', '=', 'edit_id')->join('ip_status', 'ip_data.status', '=', 'ip_status.id')->where('ip_file.file_status', 'consider_file_approve')->where('ip_file.active', '1')->get();
        for ($i = 0; $i < count($db); $i++) :
            if (strlen($db[$i]->img_random) > 0 && $db[$i]->img_random !== 'undefined') :
                $img = $db[$i]->img_random;
                $image = storage_path('app/public/uploads/ip_demo/' . $db[$i]->type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . $img);
                /* set statusname */
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
                $db[$i]->status_name = $db_status[0]->name;
            /* set statusname */
            else :
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . 'noimage.jpg');
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
                $db[$i]->status_name = $db_status[0]->name;
            endif;
            $db[$i]->edit_name = 'ไฟล์แก้ไข';
            if ($db[$i]->edit_status === null) :
                $db[$i]->typeQuery = 'File';
            else :
                $db[$i]->typeQuery = 'FileEdit';
            endif;
            $dbNew[] = $db[$i];
            $test = '1';
        endfor;
        $dbApproveDoc = DB::connection('mysql_ip_demo')->table('ip_data')->where('status', '4')->where('active', '1')->get();
        for ($i_ApproveDoc = 0; $i_ApproveDoc < count($dbApproveDoc); $i_ApproveDoc++) :
            if (strlen($dbApproveDoc[$i_ApproveDoc]->img_random) > 0 && $dbApproveDoc[$i_ApproveDoc]->img_random !== 'undefined') :
                $img = $dbApproveDoc[$i_ApproveDoc]->img_random;
                $image = storage_path('app/public/uploads/ip_demo/' . $dbApproveDoc[$i_ApproveDoc]->type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $dbApproveDoc[$i_ApproveDoc]->img_base64 = $base64;
                $dbApproveDoc[$i_ApproveDoc]->file_img = asset($image . $img);
                /* set statusname */
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$dbApproveDoc[$i_ApproveDoc]->status]);
                $dbApproveDoc[$i_ApproveDoc]->status_name = $db_status[0]->name;
            /* set statusname */
            else :
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $dbApproveDoc[$i_ApproveDoc]->img_base64 = $base64;
                $dbApproveDoc[$i_ApproveDoc]->file_img = asset($image . 'noimage.jpg');
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$dbApproveDoc[$i_ApproveDoc]->status]);
                $dbApproveDoc[$i_ApproveDoc]->status_name = $db_status[0]->name;
            endif;
            $dbApproveDoc[$i_ApproveDoc]->typeQuery = 'Form';

            $dbNew[] = $dbApproveDoc[$i_ApproveDoc];
            $test = '2';
        endfor;

        $dbTableMore = DB::connection('mysql_ip_demo')->table('ip_data')->leftjoin('ip_edit_status', 'edit_status', '=', 'edit_id')->join('ip_table_more', 'fk_table_more', '=', 'tm_id')->whereIn('tm_status', [1, 3, 'Law', 'Success'])->where('ip_data.active', '1')->get();
        for ($i_TableMore = 0; $i_TableMore < count($dbTableMore); $i_TableMore++) :
            if (strlen($dbTableMore[$i_TableMore]->img_random) > 0 && $dbTableMore[$i_TableMore]->img_random !== 'undefined') :
                $img = $dbTableMore[$i_TableMore]->img_random;
                $image = storage_path('app/public/uploads/ip_demo/' . $dbTableMore[$i_TableMore]->type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $dbTableMore[$i_TableMore]->img_base64 = $base64;
                $dbTableMore[$i_TableMore]->file_img = asset($image . $img);
                /* set statusname */
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$dbTableMore[$i_TableMore]->status]);
                $dbTableMore[$i_TableMore]->status_name = $db_status[0]->name;
            /* set statusname */
            else :
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $dbTableMore[$i_TableMore]->img_base64 = $base64;
                $dbTableMore[$i_TableMore]->file_img = asset($image . 'noimage.jpg');
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$dbTableMore[$i_TableMore]->status]);
                $dbTableMore[$i_TableMore]->status_name = $db_status[0]->name;
            endif;
            if ($dbTableMore[$i_TableMore]->tm_status === '3') :
                $dbTableMore[$i_TableMore]->typeQuery = 'EditApprove';
            else :
                $dbTableMore[$i_TableMore]->typeQuery = 'Edit';
            endif;
            $dbNew[] = $dbTableMore[$i_TableMore];
            $test = '3';

        endfor;
        // echo $test;
        return json_encode($dbNew);
    }

    public function ConsiderApproveFile()
    {
        $Id = $_POST['Id'];
        $db = DB::connection('mysql_ip_demo')->table('ip_document')->where('ip_id', $Id)->where('status_doc', 'Consider')->update(['status_doc' => 'ApproveFile']);
        $db2 = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $Id)->where('file_status', 'POWER_OF_ATTORNEY')->update(['status' => 'ApproveFile']);
    }

    public function ManageDraft()
    {
        $Id = $_POST['Id'];
        $db = DB::connection('mysql_ip_demo')->table('ip_data')->select('SELECT * FROM ip_data JOIN ip_file ON id = id_ip_file')->where('id', $Id)->get();
        return json_encode($db);
    }

    public function ManageConsider()
    {
        $id_ip_file = $_POST['id_ip_file'];
        $ms_doc_id = $_POST['ms_doc_id'];
        $status = $_POST['status'];
        $yearNow = date('Y');
        if ($status === 'Approve' || $status === 'ApproveFileEdit') :
            $dbCheckStats = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $id_ip_file)->where('active', '1')->first();
            if ($dbCheckStats->status === '4') :
                $updateStatusData = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $id_ip_file)->where('active', '1')->update(
                    [
                        'active' => 0
                    ]
                );
                $ipDataInsert = DB::connection('mysql_ip_demo')->table('ip_data')->insert(
                    [
                        'workid' => $dbCheckStats->workid,
                        'fk_table_more' => $dbCheckStats->fk_table_more,
                        'type' => $dbCheckStats->type,
                        'date_create' => $dbCheckStats->date_create,
                        'reqnum' => $dbCheckStats->reqnum,
                        'product_name' => $dbCheckStats->product_name,
                        'img' => $dbCheckStats->img,
                        'img_random' => $dbCheckStats->img_random,
                        'country' => $dbCheckStats->country,
                        'statusreq' => $dbCheckStats->statusreq,
                        'product_type' => $dbCheckStats->product_type,
                        'description' => $dbCheckStats->description,
                        'linkother' => $dbCheckStats->linkother,
                        'important' => $dbCheckStats->important,
                        'work_start' => $dbCheckStats->work_start,
                        'deadline' => $dbCheckStats->deadline,
                        'operator' => $dbCheckStats->operator,
                        'active' => '1',
                        'status' => '7'
                    ]
                );
                $dbNew = DB::connection('mysql_ip_demo')->table('ip_data')->where('workid', $dbCheckStats->workid)->where('active', '1')->first();
                $updateIdFileActive = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $dbCheckStats->id)->where('active', '1')->update(
                    [
                        'id_ip_file' => $dbNew->id
                    ]
                );
                return json_encode('status update => OK');
            else :
                $DefaultData = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $id_ip_file)->where('ms_doc_id', $ms_doc_id)->where('file_status', 'consider_file_approve')->where('active', '1')->first();
                $upDateData = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $id_ip_file)->where('ms_doc_id', $ms_doc_id)->where('file_status', 'consider_file_approve')->where('active', '1')->update(
                    [
                        'active' => '0'
                    ]
                );
                $DataNew = DB::connection('mysql_ip_demo')->table('ip_file')->insert(
                    [
                        'file_name' => $DefaultData->file_name,
                        'file_name_random' => $DefaultData->file_name_random,
                        'file_type' => $DefaultData->file_type,
                        'file_status' => 'Approve_file',
                        'file_step' => 'Approve',
                        'id_ip_file' => $DefaultData->id_ip_file,
                        'ip_workid' => $DefaultData->ip_workid,
                        'typeInsert' => $DefaultData->typeInsert,
                        'sfr_id' => '4',
                        'ms_doc_id' => $DefaultData->ms_doc_id,
                        'active' => '1',
                        'year' => $yearNow,
                    ]
                );
            endif;
        else :
            $DefaultData = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $id_ip_file)->where('ms_doc_id', $ms_doc_id)->where('file_status', 'consider_file_approve')->first();
            $upDateData = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $id_ip_file)->where('ms_doc_id', $ms_doc_id)->where('file_status', 'consider_file_approve')->update(
                [
                    'active' => '0'
                ]
            );
            $DataNew = DB::connection('mysql_ip_demo')->table('ip_file')->insert(
                [
                    'file_name' => $DefaultData->file_name,
                    'file_name_random' => $DefaultData->file_name_random,
                    'file_type' => $DefaultData->file_type,
                    'file_status' => 'Reject_file',
                    'file_step' => 'Reject',
                    'id_ip_file' => $DefaultData->id_ip_file,
                    'sfr_id' => '3',
                    'ms_doc_id' => $DefaultData->ms_doc_id,
                    'active' => '1',
                    'year' => $DefaultData->year,
                ]
            );
        endif;

        $GetDataLoop = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $id_ip_file)->first();
        $dbselectDocument = DB::connection('mysql_ip_demo')->table('ip_type')->join('master_document', 'master_document.document_type', '=', 'name')->where('name', $GetDataLoop->type)->first();
        $stackNum = 1;
        $stackDB = 1;
        for ($i = 1; $i <= 11; $i++) :
            if ($dbselectDocument->{'form_' . $i} == 1) :
                $stackNum += 1;
                $CheckApprove = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $id_ip_file)->where('ms_doc_id', $i)->where('active', '1')->where('file_status', 'Approve_file')->first();
                if ($CheckApprove) :
                    $stackDB += 1;
                endif;
            endif;
        endfor;
        if ($stackNum === $stackDB) :

            if ($status === 'ApproveFileEdit') :
            else :
                DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $id_ip_file)->update(
                    [
                        'active' => '0'
                    ]
                );
                $ipDataInsert = DB::connection('mysql_ip_demo')->table('ip_data')->insertGetId(
                    [
                        'workid' => $GetDataLoop->workid,
                        'type' => $GetDataLoop->type,
                        'date_create' => $GetDataLoop->date_create,
                        'product_name' => $GetDataLoop->product_name,
                        'img' => $GetDataLoop->img,
                        'img_random' => $GetDataLoop->img_random,
                        'country' => $GetDataLoop->country,
                        'statusreq' => $GetDataLoop->statusreq,
                        'product_type' => $GetDataLoop->product_type,
                        'description' => $GetDataLoop->description,
                        'linkother' => $GetDataLoop->linkother,
                        'important' => $GetDataLoop->important,
                        'work_start' => $GetDataLoop->work_start,
                        'deadline' => $GetDataLoop->deadline,
                        'operator' => $GetDataLoop->operator,
                        'active' => '1',
                        'status' => '4'
                    ]
                );
                DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $GetDataLoop->id)->where('active', '1')->update(
                    [
                        'id_ip_file' => $ipDataInsert
                    ]
                );
            endif;
            $dbDataMore = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $id_ip_file)->where('active', '1')->first();
            if ($dbDataMore->fk_table_more) :
                DB::connection('mysql_ip_demo')->table('ip_table_more')->where('tm_id', $dbDataMore->fk_table_more)->update(
                    [
                        'tm_status' => '3'
                    ]
                );
            endif;
        endif;

        return response()->json(['success' => true], 201);;
    }

    public function GetDataLaw()
    {
        $dbAll = array();
        $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_data WHERE ip_data.status = ? AND active = ? AND LENGTH(reqnum) < 1', ['7', '1']);
        for ($i = 0; $i < count($db); $i++) :
            if (strlen($db[$i]->img_random) > 0 && $db[$i]->img_random !== 'undefined') :

                $img = $db[$i]->img_random;
                $image = storage_path('app/public/uploads/ip_demo/' . $db[$i]->type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . $img);
                /* set statusname */
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
                $db[$i]->status_name = $db_status[0]->name;
                $db[$i]->typeQuery = 'addReqNum';
                $dbAll[] = $db[$i];
            /* set statusname */
            else :
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . 'noimage.jpg');
                $db[$i]->typeQuery = 'addReqNum';
                $dbAll[] = $db[$i];
            endif;
        endfor;

        $dbEdit = DB::connection('mysql_ip_demo')->table('ip_data')->join('ip_table_more', 'fk_table_more', '=', 'tm_id')->where('active', '1')->where('tm_status', 'Law')->get();
        for ($i = 0; $i < count($dbEdit); $i++) :
            if (strlen($dbEdit[$i]->img_random) > 0 && $dbEdit[$i]->img_random !== 'undefined') :

                $img = $dbEdit[$i]->img_random;
                $image = storage_path('app/public/uploads/ip_demo/' . $dbEdit[$i]->type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $dbEdit[$i]->img_base64 = $base64;
                $dbEdit[$i]->file_img = asset($image . $img);
                /* set statusname */
                $dbEdit_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$dbEdit[$i]->status]);
                $dbEdit[$i]->status_name = $dbEdit_status[0]->name;
                $dbEdit[$i]->typeQuery = 'editData';
                $dbAll[] = $dbEdit[$i];
            /* set statusname */
            else :
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $dbEdit[$i]->img_base64 = $base64;
                $dbEdit[$i]->file_img = asset($image . 'noimage.jpg');
                $dbEdit[$i]->typeQuery = 'editData';
                $dbAll[] = $dbEdit[$i];
            endif;
        endfor;

        return json_encode($dbAll);
    }

    public function getFile_Law()
    {
        $Id = $_POST['Id'];
        $doc_Id = $_POST['doc_Id'];
        $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file WHERE id_ip_file = ? AND active = ?', [$Id, '1']);
        return json_encode($db);
    }

    public function getFileWorkId()
    {
        $workId = $_POST['workId'];
        $ms_doc_id = $_POST['ms_doc_id'];
        $status = $_POST['status'];
        $dbGetId = DB::connection('mysql_ip_demo')->table('ip_data')->where('workid', $workId)->where('active', 1)->first();
        if ($status < 4) :
            $dbData = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file LEFT JOIN document_file ON ms_doc_id = document_file.id WHERE ip_workid = ? AND ms_doc_id = ? AND file_status = ? AND ip_file.active = ?', [$workId, $ms_doc_id, 'consider_file_approve', '1']);
        else :
            $dbData = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file LEFT JOIN document_file ON ms_doc_id = document_file.id WHERE ip_workid = ? AND ip_file.active = ?', [$workId, '1']);
        endif;
        for ($i = 0; $i < count($dbData); $i++) :
            if ($dbData[$i]->file_type == 'doc') :
                $contentType = 'application/msword';
            elseif ($dbData[$i]->file_type == 'pdf') :
                $contentType = 'application/pdf';
            else :
                $contentType = 'image/jpeg';
            endif;

            $fileName = $dbData[$i]->file_name_random;
            $image = storage_path('app/public/uploads/ip_demo/Attachments/' . $dbGetId->type . '/' . $workId . '/');
            $base64 = base64_encode(file_get_contents($image . $fileName));
            $dbData[$i]->file_base64 = $base64;
            $dbData[$i]->contentType = $contentType;
            // $dbData[$i]->file_address = asset($image . $fileName); 
            // $dbData[$i]->file_address = 'http://127.0.0.1:8000/storage/uploads/ip_demo/Attachments/IC/ICBT-08-24001/dwPf22mmLEw27onfwz7x9srVGf8PptvxTmP4BL8U.pdf';
            $dbData[$i]->file_address = env('APP_URL') . 'storage/uploads/ip_demo/Attachments/' . $dbGetId->type . '/' . $dbGetId->workid . '/' . $fileName;

        // $dbData[$i]->file_address = $fileName;
        endfor;
        return json_encode($dbData);
    }

    public function DownloadForm($name)
    {
        if ($name == 'หนังสือมอบอำนาจ') :
            $file = 'หนังสือมอบอำนาจ.doc';
        elseif ($name == 'หนังสือโอนสิทธิ') :
            $file = 'หนังสือโอนสิทธิบัตรการประดิษฐ์.doc';
        elseif ($name == 'คำขอการประดิษฐ์') :
            $file = 'คำขอการประดิษฐ์.pdf';
        elseif ($name == 'คำพรรณนา') :
            $file = 'คำพรรณาสิทธิบัตรการออกแบบ.docx';
        endif;
        // return Storage::url($image . '1.pdf');
        // return Storage::download($image);
        return response()->download(storage_path('app/public/uploads/template_ip/document/' . $file));
    }

    public function Submit_Form()
    {
        $Id = $_POST['Id'];
        $dbData = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->first();
        $Data = $dbData;
        if ($dbData->status < 4) :
            $dbUpdate = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->update(['active' => 0]);
            $dbInsert = DB::connection('mysql_ip_demo')->table('ip_data')->insert([
                'workid' => $Data->workid,
                'type' => $Data->type,
                'date_create' => $Data->date_create,
                'reqnum' => $Data->reqnum,
                'product_name' => $Data->product_name,
                'img' => $Data->img,
                'img_random' => $Data->img_random,
                'country' => $Data->country,
                'statusreq' => $Data->statusreq,
                'product_type' => $Data->product_type,
                'description' => $Data->description,
                'linkother' => $Data->linkother,
                'important' => $Data->important,
                'operator' => $Data->operator,
                'work_start' => $Data->work_start,
                'deadline' => date("Y-m-d H:i:s"),
                'status' => '4'
            ]);

            $dbNewData = DB::connection('mysql_ip_demo')->table('ip_data')->where('workid', $Data->workid)->where('active', 1)->first();
            $dbUpdatePkFile = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $Id)->update(['id_ip_file' => $dbNewData->id]);

            $db = DB::connection('mysql_ip_demo')->table('ip_document')->insert([
                'ip_id' => $dbNewData->id,
                'status_doc' => 'Consider_form_approve',
                'year' => date('Y')
            ]);
            return json_encode('[UPDATE] => OK');
        else :
            return json_encode('[ERROR] => MULTIPLE INSERT DATA');
        endif;
        // $db_update_status = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->update(['status' => '6']);
    }

    public function InsertReqNum()
    {
        $ReqNum = $_POST['Data'];
        $id = $_POST['Id'];
        $dataDecode = json_decode($ReqNum);
        $convertdatereq = Carbon::parse($dataDecode->datereq);
        $convertregisdate = Carbon::parse($dataDecode->regis_date);
        $convertreexpries_date = Carbon::parse($dataDecode->expries_date);
        $dbUpdate = DB::connection('mysql_ip_demo')->table('ip_data')->insert(
            [
                'workid' => $dataDecode->workid,
                'type' => $dataDecode->type,
                'date_create' => $dataDecode->date_create,
                'reqnum' => $dataDecode->reqnum,
                'product_name' => $dataDecode->product_name,
                'img' => $dataDecode->img,
                'img_random' => $dataDecode->img_random,
                'country' => $dataDecode->country,
                'statusreq' => $dataDecode->statusreq,
                'product_type' => $dataDecode->product_type,
                'description' => $dataDecode->description,
                'linkother' => $dataDecode->linkother,
                'important' => $dataDecode->important,
                'operator' => $dataDecode->operator,
                'work_start' => $dataDecode->work_start,
                'deadline' => $dataDecode->work_start,
                'datereq' => $convertdatereq->format('Y-m-d'),
                'regis_date' => $convertregisdate->format('Y-m-d'),
                'expries_date' => $convertreexpries_date->format('Y-m-d'),
                'status' => '8'
            ]
        );

        $dbUpdateBefore = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $id)->update(['active' => 0]);

        return json_encode($dbUpdate);


        // $Id = $_POST['Id'];
        // $dbData = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->where('active', 1)->first();
        // $Data = $dbData;
        // $dbUpdate = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->where('active', 1)->update(['active' => 0]);
        // // $db_update_status = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->update([
        // //     'status' => '8'
        // // ]);
        // if ($dbData->status < 8) :
        //     $dbInsert = DB::connection('mysql_ip_demo')->table('ip_data')->insert([
        //         'workid' => $Data->workid,
        //         'type' => $Data->type,
        //         'date_create' => $Data->date_create,
        //         'reqnum' => $Data->reqnum,
        //         'product_name' => $Data->product_name,
        //         'img' => $Data->img,
        //         'img_random' => $Data->img_random,
        //         'country' => $Data->country,
        //         'statusreq' => $Data->statusreq,
        //         'product_type' => $Data->product_type,
        //         'description' => $Data->description,
        //         'linkother' => $Data->linkother,
        //         'important' => $Data->important,
        //         'operator' => $Data->operator,
        //         'work_start' => $Data->work_start,
        //         'reqnum' => $ReqNum,
        //         'status' => '8'
        //     ]);
        //     return json_encode('[INSERT REQNUM] => OK');
        // else :
        //     return json_encode('[ERROR] => MULTIPLE INSERT DATA');
        // endif;
    }

    public function getProductGroup()
    {
        $ProductGroup = DB::connection('mysql_ip_demo')->select('SELECT name_group as label,pdg_id as code FROM ip_product_group');
        $SubProductGroup = DB::connection('mysql_ip_demo')->select('SELECT name_sub_product as label,code_spdg as code,spd_pdg_id FROM ip_sub_product_group');

        foreach ($ProductGroup as  $value) :
            foreach ($SubProductGroup as $key => $valueSub) :
                if ($value->code === (int)$valueSub->spd_pdg_id) :
                    $itemsArray[] = ['label' => $valueSub->label, 'value' => $valueSub->code];
                endif;
            endforeach;
            $value->items = $itemsArray;

            $data[] = $value;
        endforeach;
        // echo '<pre>';
        // echo json_encode($data);
        // echo '</pre>';
        return json_encode($data);
    }
    public function getsubGroupIntellectual()
    {
        $Type = $_POST['Type'];
        $SubGroupIntellectual = DB::connection('mysql_ip_demo')->select('SELECT name_sub_intellectual as name, code FROM ip_sub_group_intellectual WHERE active = 1 AND type_pg = ? ', [$Type]);

        return json_encode($SubGroupIntellectual);
    }
    public function Test()
    {
        return DB::connection('mysql_ip_demo')->select('SELECT workid,product_name FROM ip_data');
    }


    public function ExportExcel(Request $request)
    {
        $type = $request->type;

        $db = DB::connection('mysql_ip_demo')->table('ip_data')->where('type', $type)->where('active', '1')->get();

        if ($db) :
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $rows = 2;
            $sheet->setCellValue('A1', 'ลำดับ');
            $sheet->setCellValue('B1', 'ชื่อสิ่งประดิษฐ์');
            $sheet->setCellValue('C1', 'ภาพสิ่งประดิษฐ์');
            $sheet->setCellValue('D1', 'เลขที่คำขอ');
            $sheet->setCellValue('E1', 'วันที่ยื่นคำขอ');
            $sheet->setCellValue('F1', 'เลขที่สิทธิบัตร');
            $sheet->setCellValue('G1', 'สถานะปัจจุบัน');
            $sheet->setCellValue('H1', 'สถานะต่อไป');
            $sheet->setCellValue('I1', 'วัันที่ออกสิทธิบัตร');
            $sheet->setCellValue('J1', 'สิทธิบัตรหมดอายุ');

            for ($i = 0; $i < count($db); $i++) :
                $sheet->setCellValue('A' . $rows, $i + 1);
                $sheet->setCellValue('B' . $rows, $db[$i]->product_name);
                $sheet->setCellValue('D' . $rows, $db[$i]->reqnum);
                $sheet->setCellValue('E' . $rows, $db[$i]->datereq);
                $sheet->setCellValue('F' . $rows, $db[$i]->patentnum);
                $sheet->setCellValue('G' . $rows, $db[$i]->statusnow);
                $sheet->setCellValue('H' . $rows, $db[$i]->statusnext);
                $sheet->setCellValue('I' . $rows, $db[$i]->regis_date);
                $sheet->setCellValue('J' . $rows, $db[$i]->expries_date);
                if ($db[$i]->img_random) :
                    $Image_path = storage_path('app/public/uploads/ip_demo/' . $type . '/' . $db[$i]->img_random);
                else :
                    $Image_path = storage_path('app/public/uploads/ip_demo/noimage.jpg');
                endif;
                $drawing = new Drawing();

                $drawing->setWorksheet($sheet);
                $drawing->setPath($Image_path)->setHeight(100)->setCoordinates('C' . $rows)->setOffsetX(20)->setOffsetY(20);

                $sheet->getRowDimension($rows)->setRowHeight(120);
                $sheet->getColumnDimension('C')->setWidth(50);
                for ($j = 'A'; $j !=  $spreadsheet->getActiveSheet()->getHighestColumn(); $j++) {
                    if ($j !== 'C') :
                        $sheet->getColumnDimension($j)->setAutoSize(true);
                    endif;
                }
                $rows++;
            endfor;

            header('Content-Type: applocation/vnd.openxmlformats-officedocument.spreadsheets');
            header('Content-Disposition: attachment;filename="Report.xlsx"');
            header('Cache-Control: max-age=0');

            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            ob_end_clean();
            $writer->save('php://output');
        else :
            return json_encode(false);
        endif;
    }

    public function SendDocument()
    {
        $Id = $_POST['Id'];
        $dbData = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->first();
        $Data = $dbData;
        if ($dbData->status < 7) :
            $dbUpdate = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->update(['active' => 0]);
            $dbInsert = DB::connection('mysql_ip_demo')->table('ip_data')->insert([
                'workid' => $Data->workid,
                'type' => $Data->type,
                'date_create' => $Data->date_create,
                'reqnum' => $Data->reqnum,
                'product_name' => $Data->product_name,
                'img' => $Data->img,
                'img_random' => $Data->img_random,
                'country' => $Data->country,
                'statusreq' => $Data->statusreq,
                'product_type' => $Data->product_type,
                'description' => $Data->description,
                'linkother' => $Data->linkother,
                'important' => $Data->important,
                'operator' => $Data->operator,
                'work_start' => $Data->work_start,
                'deadline' => date("Y-m-d H:i:s"),
                'status' => '7'
            ]);
            $dbNewData = DB::connection('mysql_ip_demo')->table('ip_data')->where('workid', $dbData->workid)->where('active', 1)->first();
            $dbUpdatePkFile = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $Id)->update(['id_ip_file' => $dbNewData->id]);

            return json_encode('[SendDocuemnt] => OK');
        else :
            return json_encode('[ERROR] => MULTIPLE INSERT DATA');
        endif;
    }

    public function ApproveFile_Dialog()
    {
        $Id = $_POST['Id'];
        if (isset($_POST['Status'])) :
            $Status = $_POST['Status'];
        endif;
        $db = DB::connection('mysql_ip_demo')->table('ip_file')->where('if_id', $Id)->update([
            'file_status' => $Status
        ]);
        return json_encode('OK');
    }

    public function Login()
    {
        $user = $_POST['username'];
        $password = $_POST['password'];

        $db = DB::connection('mysql_ip_demo')->table('user')->where('username', $user)->where('password', $password)->first();

        return json_encode($db);
    }

    public function getmasterDoc()
    {
        $key = $_POST['key'];
        $reqnum = $_POST['reqnum'];


        $dbselectDocument = DB::connection('mysql_ip_demo')->table('ip_data')->join('master_document', 'master_document.document_type', '=', 'ip_data.type')->where('ip_data.reqnum', $reqnum)->where('ip_data.active', '1')->first();
        if ($key === '1') :
            if ($dbselectDocument) :
                for ($i = 1; $i <= 11; $i++) :
                    if ($dbselectDocument->{'form_' . $i} == 1) :
                        $select = DB::connection('mysql_ip_demo')->table('document_file')->where('id', $i)->first();
                        $dataForm[] = $select;
                    endif;
                endfor;
            else :
                $dataForm = 'not found';
            endif;
        elseif ($key === '2') :
            if ($dbselectDocument) :
                $dataForm = 'pay';
            else :
                $dataForm = 'not found';
            endif;
        elseif ($key === '3') :
            if ($dbselectDocument) :
                $dataForm = 'oppose';
            else :
                $dataForm = 'not found';
            endif;
        else :
            $dataForm = null;
        endif;
        return json_encode($dataForm);
    }

    public function CreateDataMore(Request $request)
    {
        $data = json_decode($_POST['data']);
        $converteceiptdate = Carbon::parse($data->eceipt_date);
        $convertdatedoc = Carbon::parse($data->datedoc);
        $convertstartdate = Carbon::parse($data->startdate);
        $convertenddate = Carbon::parse($data->enddate);

        $file = $request->file('file');
        $yearNow = date('Y');
        $hashName = $request->file->hashName();
        $originalName = $file->getClientOriginalName();
        $path = "public/uploads/ip_demo/Attachments/Letter/" . $yearNow . '/' . $data->reqnum . '/' . $hashName;
        Storage::disk('local')->put($path, file_get_contents($request->file));

        $dbMore = DB::connection('mysql_ip_demo')->table('ip_table_more')->insertGetId(
            [
                'tm_reqnum' => $data->reqnum,
                'tm_title' => $data->title,
                'tm_title_type' => $data->title_type,
                'tm_from' => $data->from,
                'tm_eceiptdate' => $converteceiptdate,
                'tm_category' => $data->category,
                'tm_patentnum' => $data->patentnum,
                'tm_datedoc' => $convertdatedoc,
                'tm_dateday' => $data->dateday,
                'tm_startdate' => $convertstartdate,
                'name_file_letter' => $originalName,
                'name_file_letter_random' => $hashName,
                'tm_enddate' => $convertenddate,
                'tm_status' => '1',
                'tm_comment' => $data->comment,
                'tm_userlastupdate' => 'jakkawan.s'
            ]
        );
        $dbMain = DB::connection('mysql_ip_demo')->table('ip_data')->where('reqnum', $data->reqnum)->where('active', '1')->first();
        // $dbMain_Update = DB::connection('mysql_ip_demo')->table('ip_data')->where('reqnum', $data->reqnum)->whereNotIn('fk_table_more', [null])->where('edit_status', $data->title_type)->where('active', 1)->update([
        //     'active' => '0'
        // ]);

        $dbMain_Insert = DB::connection('mysql_ip_demo')->table('ip_data')->insertGetId(
            [
                'fk_table_more' => $dbMore,
                'workid' => $dbMain->workid,
                'type' => $dbMain->type,
                'date_create' => $dbMain->date_create,
                'reqnum' => $data->reqnum,
                'patentnum' => $dbMain->patentnum,
                'product_name' => $dbMain->product_name,
                'img' => $dbMain->img,
                'img_random' => $dbMain->img_random,
                'country' => $dbMain->country,
                'statusreq' => $dbMain->statusreq,
                'product_type' => $dbMain->product_type,
                'description' => $dbMain->description,
                'linkother' => $dbMain->linkother,
                'important' => $dbMain->important,
                'work_start' => $dbMain->work_start,
                'deadline' => $dbMain->deadline,
                'operator' => $dbMain->operator,
                'statusnow' => $dbMain->statusnow,
                'statusnext' => $dbMain->statusnext,
                'active' => '1',
                'status' => $dbMain->status,
                'edit_status' => $data->title_type,
                'datereq' => $dbMain->datereq,
                'regis_date' => $dbMain->regis_date,
                'expries_date' => $dbMain->expries_date
            ]
        );

        $dataFileupdate = DB::connection('mysql_ip_demo')->table('ip_file')->where('ip_workid', $dbMain->workid)->where('active', '1')->update(
            [
                'id_ip_file' => $dbMain_Insert
            ]
        );
        return json_encode($data);
    }

    public function setFileFromEdit()
    {
        $fk_table_more = $_POST['fk_table_more'];
        $yearnow = date('Y');
        $id_doc_file = json_decode($_POST['id_doc_file']);
        $edit_id = $_POST['edit_id'];
        $dbSelect = DB::connection('mysql_ip_demo')->table('ip_data')->where('fk_table_more', $fk_table_more)->where('edit_status', $edit_id)->where('active', 1)->first();

        DB::connection('mysql_ip_demo')->table('ip_file')->where('ip_workid', $dbSelect->workid)->where('typeInsert', 'C')->where('active', '1')->update(
            [
                'active' => '0'
            ]
        );
        for ($i_insert = 0; $i_insert < count($id_doc_file); $i_insert++) :
            DB::connection('mysql_ip_demo')->table('ip_file')->where('ip_workid', $dbSelect->workid)->where('ms_doc_id', $id_doc_file[$i_insert])->update(
                [
                    'active' => '0'
                ]
            );
            $dbInsert = DB::connection('mysql_ip_demo')->table('ip_file')->insert(
                [
                    'ip_workid' => $dbSelect->workid,
                    'file_status' => 'req_doc',
                    'file_step' => 'wait',
                    'id_ip_file' => $dbSelect->id,
                    'typeInsert' => 'U',
                    'sfr_id' => '8',
                    'ms_doc_id' => $id_doc_file[$i_insert],
                    'year' => $yearnow,
                    'active' => '1'
                ]
            );
            $dbUpdateMore = DB::connection('mysql_ip_demo')->table('ip_table_more')->where('tm_id', $fk_table_more)->where('tm_title_type', $edit_id)->update(['tm_status' => '2']);
        endfor;
        return json_encode($id_doc_file);
    }
    public function approveEdit()
    {
        $fk_table_more = $_POST['fk_table_more'];
        $edit_id = $_POST['edit_id'];
        $dbSelectStatus = DB::connection('mysql_ip_demo')->table('ip_table_more')->where('tm_id', $fk_table_more)->first();


        if ($dbSelectStatus->tm_status === '3') :
            $dbMain = DB::connection('mysql_ip_demo')->table('ip_data')->where('fk_table_more', $fk_table_more)->where('edit_status', $edit_id)->where('active', '1')->first();
            // $dbUpdate = DB::connection('mysql_ip_demo')->table('ip_data')->where('fk_table_more', $fk_table_more)->where('edit_status', $edit_id)->where('active', '1')->update(
            //     [
            //         'active' => '0'
            //     ]
            // );
            $dbUpdateMain = DB::connection('mysql_ip_demo')->table('ip_data')->where('workid', $dbMain->workid)->whereNull('fk_table_more')->where('active', '1')->update(['active' => '0']);
            // $dbInsert = DB::connection('mysql_ip_demo')->table('ip_data')->insertGetId(
            //     [
            //         'fk_table_more' => null,
            //         'workid' => $dbMain->workid,
            //         'type' => $dbMain->type,
            //         'date_create' => $dbMain->date_create,
            //         'reqnum' => $reqnum,
            //         'patentnum' => $dbMain->patentnum,
            //         'product_name' => $dbMain->product_name,
            //         'img' => $dbMain->img,
            //         'img_random' => $dbMain->img_random,
            //         'country' => $dbMain->country,
            //         'statusreq' => $dbMain->statusreq,
            //         'product_type' => $dbMain->product_type,
            //         'description' => $dbMain->description,
            //         'linkother' => $dbMain->linkother,
            //         'important' => $dbMain->important,
            //         'work_start' => $dbMain->work_start,
            //         'deadline' => $dbMain->deadline,
            //         'operator' => $dbMain->operator,
            //         'statusnow' => $dbMain->statusnow,
            //         'statusnext' => $dbMain->statusnext,
            //         'active' => '1',
            //         'status' => $dbMain->status,
            //         'edit_status' => null,
            //         'datereq' => $dbMain->datereq,
            //         'regis_date' => $dbMain->regis_date,
            //         'expries_date' => $dbMain->expries_date
            //     ]
            // );
            // DB::connection('mysql_ip_dmeo')->table('ip_file')->where('id_ip_file',$dbMain->id)->update(['id_ip_file' => $dbInsert]);
            DB::connection('mysql_ip_demo')->table('ip_table_more')->where('tm_id', $fk_table_more)->where('tm_title_type', $edit_id)->update(
                [
                    'tm_status' => 'Law'
                ]
            );
        else :
            DB::connection('mysql_ip_demo')->table('ip_table_more')->where('tm_id', $fk_table_more)->where('tm_title_type', $edit_id)->update(
                [
                    'tm_status' => '2'
                ]
            );
        endif;
        return json_encode(true);
    }

    public function GetDataEdit()
    {
        // $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file JOIN ip_data ON id_ip_file = ip_data.id JOIN status_file_req ON sfr_id = status_file_req.id JOIN document_file ON ms_doc_id = document_file.id WHERE IN ip_file.file_step = ? AND ip_file.active = ?', ['wait', '1']);
        $db = DB::connection('mysql_ip_demo')->table('ip_data')->leftJoin('ip_edit_status', 'edit_status', '=', 'edit_id')->join('ip_table_more', 'fk_table_more', '=', 'tm_id')->whereNotIn('tm_status', ['Success'])->where('active', '1')->get();
        for ($i = 0; $i < count($db); $i++) :
            if (strlen($db[$i]->img_random) > 0 && $db[$i]->img_random !== 'undefined') :

                $img = $db[$i]->img_random;
                $image = storage_path('app/public/uploads/ip_demo/' . $db[$i]->type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . $img);
                /* set statusname */
                $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
                $db[$i]->status_name = $db_status[0]->name;
            /* set statusname */
            else :
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . 'noimage.jpg');
            endif;
        endfor;

        return json_encode($db);
    }
    public function updateFormEdit(Request $request)
    {
        $data = $_POST;
        $yearNow = date('Y');
        $file = $request->file('file');
        $hashName = $request->file->hashName();
        $originalName = $request->file->getClientOriginalName();
        if ($data['tm_title_type'] === '2') :
            $path = "public/uploads/ip_demo/Attachments/PR/" . $yearNow . '/' . $data['workid'] . '/' . $hashName;
        else :
            $path = "public/uploads/ip_demo/Attachments/Oppose/" . $yearNow . '/' . $data['workid'] . '/' . $hashName;
        endif;
        Storage::disk('local')->put($path, file_get_contents($request->file));

        if ($data['tm_title_type'] === '2') :
            $dbUpdate = DB::connection('mysql_ip_demo')->table('ip_table_more')->where('tm_id', $data['tm_id'])->where('tm_title_type', $data['tm_title_type'])->where('tm_status', '2')->update(
                [
                    'tm_num_pr' => $data['tm_num_pr'],
                    'tm_filename_pr' => $originalName,
                    'tm_filename_pr_random' => $hashName,
                    'tm_status' => '3'
                ]
            );
        else :
            $dbUpdate = DB::connection('mysql_ip_demo')->table('ip_table_more')->where('tm_id', $data['tm_id'])->where('tm_title_type', $data['tm_title_type'])->where('tm_status', '2')->update(
                [
                    'tm_filename_pr' => $originalName,
                    'tm_filename_pr_random' => $hashName,
                    'tm_status' => '3'
                ]
            );
        endif;
        return response()->json(['success' => true], 201);
    }

    public function getFileDataSelect()
    {
        $tm_id = $_POST['tm_id'];
        $yearNow = date('Y');
        $url = Storage::path('file.jpg');


        $dbData = DB::connection('mysql_ip_demo')->table('ip_table_more')->join('ip_data', 'tm_id', '=', 'fk_table_more')->where('tm_id', $tm_id)->first();
        if ($dbData->tm_title_type === 2) :
            $Folder = 'PR';
        else :
            $Folder = 'Letter';
        endif;
        if (strlen($dbData->tm_filename_pr) > 0 && $dbData->tm_filename_pr !== 'undefined') :

            $File = $dbData->tm_filename_pr;
            $image = storage_path('app/public/uploads/ip_demo/Attachments/' . $Folder . '/' . $yearNow . '/' . $dbData->workid . '/');
            $base64 = file_get_contents($image . $File);
            $dbData->FileBase64 = $base64;
            $file_path = asset($image . $File);
            /* set statusname */
            $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$dbData->status]);
            $dbData->status_name = $db_status[0]->name;
        /* set statusname */
        else :
            $image = storage_path('app/public/uploads/ip_demo/');
            $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
            $dbData->FileBase64 = $base64;
            $dbData->file_path = asset($image . 'noimage.jpg');
        endif;

        return json_encode($file_path);
    }

    public function approveEditLaw()
    {
        $fk_table_more = $_POST['fk_table_more'];

        $dbSelectStatus = DB::connection('mysql_ip_demo')->table('ip_table_more')->where('tm_id', $fk_table_more)->update(['tm_status' => 'Success']);

        $dbMain = DB::connection('mysql_ip_demo')->table('ip_data')->where('fk_table_more', $fk_table_more)->where('active', '1')->first();

        DB::connection('mysql_ip_demo')->table('ip_data')->where('fk_table_more', $fk_table_more)->where('active', '1')->update(['active' => '0']);

        $dbInsert = DB::connection('mysql_ip_demo')->table('ip_data')->insertGetId(
            [
                'fk_table_more' => null,
                'workid' => $dbMain->workid,
                'type' => $dbMain->type,
                'date_create' => $dbMain->date_create,
                'reqnum' => $dbMain->reqnum,
                'patentnum' => $dbMain->patentnum,
                'product_name' => $dbMain->product_name,
                'img' => $dbMain->img,
                'img_random' => $dbMain->img_random,
                'country' => $dbMain->country,
                'statusreq' => $dbMain->statusreq,
                'product_type' => $dbMain->product_type,
                'description' => $dbMain->description,
                'linkother' => $dbMain->linkother,
                'important' => $dbMain->important,
                'work_start' => $dbMain->work_start,
                'deadline' => $dbMain->deadline,
                'operator' => $dbMain->operator,
                'statusnow' => $dbMain->statusnow,
                'statusnext' => $dbMain->statusnext,
                'active' => '1',
                'status' => $dbMain->status,
                'edit_status' => null,
                'datereq' => $dbMain->datereq,
                'regis_date' => $dbMain->regis_date,
                'expries_date' => $dbMain->expries_date
            ]
        );

        DB::connection('mysql_ip_demo')->table('ip_file')->where('ip_workid', $dbMain->workid)->where('active', '1')->update(['id_ip_file' => $dbInsert]);

        return response()->json(['success' => true], 201);;
    }

    public function getDataFileShow()
    {
        $id = $_POST['id'];
        $dbselect = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $id)->where('active', '1')->first();

        $db = DB::connection('mysql_ip_demo')->table('ip_file')->where('typeInsert', 'I')->where('ip_workid', $dbselect->workid)->where('active', '1')->get();

        return json_encode($db);
    }

    public function submitfile_to_consider()
    {
        $id = $_POST['id'];

        $db = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $id)->where('typeInsert', 'I')->whereNotIn('file_status', ['Approve_file'])->where('active', '1')->update(
            [
                'file_status' => 'consider_file_approve',
            ]
        );
        return response()->json(['message' => 'Update Success'], 200);
    }
}
// power of attorney
