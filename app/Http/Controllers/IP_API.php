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
        return json_encode('[CREATE] => OK');
    }

    public function get_ip_type()
    {
        $db = DB::connection('mysql_ip_demo')->select('SELECT fullname_th AS name,name AS code FROM ip_type WHERE active = 1');

        return json_encode($db);
    }


    public function GetData()
    {
        $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_data WHERE active = 1');
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
        $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_document JOIN ip_data ON ip_id = ip_data.id  WHERE ip_document.status_doc = ?', ['req_doc']);
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


    public function uploadFile_Attach(Request $request)
    {


        $Id = $_POST['Id'];
        $file_name = $_POST['file_name'];
        $req_file = $_POST['status_doc'];
        $file_step = $_POST['file_step'];

        $dbWorkId = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->where('active', 1)->first();

        $file = $request->file('file');
        $originalName = $request->file->hashName();

        $path = "public/uploads/ip_demo/Attachments/" . $dbWorkId->type . '/' . $dbWorkId->workid . '/' . $originalName;
        Storage::disk('local')->put($path, file_get_contents($request->file));

        $db_check = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file WHERE file_status = ? AND file_step = ? AND id_ip_file = ? AND active = ?', [$req_file, $file_step, $Id, '1']);
        if ($db_check) :
            $db_update = DB::connection('mysql_ip_demo')->table('ip_file')->where('file_status', $req_file)->where('file_step', $file_step)->where('id_ip_file', $Id)->update(
                [
                    'active' => '0'
                ]
            );
        endif;

        $db = DB::connection('mysql_ip_demo')->table('ip_file')->insert(
            [
                'file_name' => $file_name,
                'file_name_random' => $originalName,
                'file_type' => $request->file->getClientOriginalExtension(),
                'file_status' => $req_file,
                'id_ip_file' => $Id,
                'file_step' => $file_step,
                'year' => date('Y'),
                'lastupdate' => 'jakkawan.s'
            ]
        );
        return json_encode($db);
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
        $db = DB::connection('mysql_ip_demo')->table('ip_document')->where('ip_id', $Id)->whereIn('status_doc', ['req_doc', 'Approve_req'])->first();

        if (isset($db)) :
            return json_encode($db);
        else :
            return json_encode($db);
        endif;
    }

    public function getDataConsider()
    {
        $db = DB::connection('mysql_ip_demo')->table('ip_data')->join('ip_document', 'ip_data.id', '=', 'ip_document.ip_id')->whereIn('ip_document.status_doc', ['Consider_req_doc', 'Consider_form_approve'])->get();
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
        $Id = $_POST['Id'];
        $Id_doc = $_POST['Id_doc'];
        $dbSearchStatus = DB::connection('mysql_ip_demo')->table('ip_document')->where('id_id', $Id_doc)->first();
        if ($dbSearchStatus->status_doc === 'Consider_req_doc') :
            $ApproveDoc = DB::connection('mysql_ip_demo')->table('ip_document')->where('id_id', $Id_doc)->where('status_doc', 'Consider_req_doc')->update([
                'status_doc' => 'Approve_req',
            ]);

            $ApproveFile = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $Id)->where('file_status', 'req_doc')->update([
                'file_step' => 'Approve'
            ]);
        else :
            $dbData = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->first();
            $Data = $dbData;
            /* ทำการเปลี่ยน status บันทึกข้อมูลใหม่เก็บข้อมูลเก่า */
            if ($dbData->status < 5) :
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
                    'status' => '5'
                ]);
                /* ทำการเปลี่ยน status บันทึกข้อมูลใหม่เก็บข้อมูลเก่า */

                $dbNewData = DB::connection('mysql_ip_demo')->table('ip_data')->where('workid', $Data->workid)->where('active', 1)->first();

                $dbUpdatePkFile = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $Id)->update(['id_ip_file' => $dbNewData->id]);

                $db_doc_2 = DB::connection('mysql_ip_demo')->table('ip_document')->where('id_id', $Id_doc)->where('status_doc', 'Consider_form_approve')->update([
                    'status_doc' => 'Approve_submit_form',
                ]);
            else :
                return json_encode('[ERROR] => MULTIPLE INSERT DATA');
            endif;
        endif;
        // $db = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->where('status', '4')->update([
        //     'status' => '5',
        // ]);

        // $db_update2 = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->where('status', '6')->update([
        //     'status' => '7',
        // ]);

        // $db_doc = DB::connection('mysql_ip_demo')->table('ip_document')->where('id_id', $Id_doc)->where('status_doc', 'Consider_req_doc')->update([
        //     'status_doc' => 'Approve_req',
        // ]);



        // $db_file = DB::connection('mysql_ip_demo')->table('ip_file')->where('id_ip_file', $Id)->where('file_status', 'req_doc')->update([
        //     'file_step' => 'Approve'
        // ]);
        // return json_encode($db);

        return json_encode('[APPROVE] => OK');
    }

    public function GetDataLaw()
    {
        $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_data WHERE ip_data.status = ?', ['7']);
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
        $dbGetId = DB::connection('mysql_ip_demo')->table('ip_data')->where('workid', $workId)->where('active', 1)->first();

        $dbData = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file WHERE id_ip_file = ?', [$dbGetId->id]);

        for ($i = 0; $i < count($dbData); $i++) :
            if ($dbData[$i]->file_type == 'doc') :
                $contentType = 'application/msword';
            elseif ($dbData[$i]->file_type == 'pdf') :
                $contentType = 'application/pdf';
            // elseif ($dbData[$i]->file_type == 'xslx') :
            //     $contentType = 'application/pdf';
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
        $ReqNum = $_POST['ReqNum'];
        $Id = $_POST['Id'];
        $dbData = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->where('active', 1)->first();
        $Data = $dbData;
        $dbUpdate = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->where('active', 1)->update(['active' => 0]);
        // $db_update_status = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $Id)->update([
        //     'status' => '8'
        // ]);
        if ($dbData->status < 8) :
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
                'reqnum' => $ReqNum,
                'status' => '8'
            ]);
            return json_encode('[INSERT REQNUM] => OK');
        else :
            return json_encode('[ERROR] => MULTIPLE INSERT DATA');
        endif;
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
        $SubGroupIntellectual = DB::connection('mysql_ip_demo')->select('SELECT name_sub_intellectual as name, code FROM ip_sub_group_intellectual WHERE active = 1 ');

        return json_encode($SubGroupIntellectual);
    }
    public function Test()
    {
        return DB::connection('mysql_ip_demo')->select('SELECT workid,product_name FROM ip_data');
    }


    public function ExportExcel(Request $request)
    {
        // var_dump($db[0]);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()();
        $sheet->setCellValue('A', 'Hello');

        $Image_path = storage_path('app/public/uploads/ip_demo/');

        $drawing = new Drawing();

        $drawing->setWorksheet($sheet);
        $drawing->setPath($Image_path)->setHeight(100)->setCoordinates('A3');

        $sheet->getRowDimension(10)->setRowHeight(200);
        // $db = DB::connection('mysql_ip_demo')->select('SELECT workid,product_name FROM ip_data');
        return Excel::download(new ExportExcel, 'users.xlsx');
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
}
// power of attorney
