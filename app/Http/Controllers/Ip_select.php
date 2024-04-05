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

class Ip_select extends Controller
{
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

            // $dbselect = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $db[$i]->id)->where('active', '1')->first();
            $db_docname = DB::connection('mysql_ip_demo')->table('ip_file')->join('ip_data', 'id_ip_file', '=', 'ip_data.id')->join('status_file_req', 'sfr_id', '=', 'status_file_req.id')->join('document_file', 'ms_doc_id', '=', 'document_file.id')->whereIn('ip_file.file_step', ['wait', 'Reject'])->where('ip_workid', $db[$i]->workid)->whereIn('typeInsert', ['C', 'U'])->where('ip_file.active', '1')->get();
            $db[$i]->docname = $db_docname;

            for ($j = 0; $j < count($db_docname); $j++) :
                $dbfile = DB::connection('mysql_ip_demo')->table('ip_file')->where('typeInsert', 'I')->where('ip_workid', $db_docname[$j]->workid)->where('ms_doc_id', $db_docname[$j]->ms_doc_id)->where('active', '1')->first();
                $db[$i]->docfile[] = $dbfile;
            endfor;
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
            $dbData = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_file LEFT JOIN document_file ON ms_doc_id = document_file.id WHERE ip_workid = ? AND ip_file.active = ? AND typeInsert = ?', [$workId, '1','I']);
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

    public function getDataFileShow()
    {
        $id = $_POST['id'];
        $dbselect = DB::connection('mysql_ip_demo')->table('ip_data')->where('id', $id)->where('active', '1')->first();

        $db = DB::connection('mysql_ip_demo')->table('ip_file')->where('typeInsert', 'I')->where('ip_workid', $dbselect->workid)->where('active', '1')->get();

        return json_encode($db);
    }
}
