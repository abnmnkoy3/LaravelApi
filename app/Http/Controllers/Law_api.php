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

class Law_api extends Controller
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


    public function GetDataReportLaw()
    {
        $type = $_POST['type'];
        if (strlen($type) > 0) :
            $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM data_law WHERE type = ?', [$type]);
        else :
            $db = DB::connection('mysql_ip_demo')->select('SELECT * FROM data_law WHERE type = ?', ['']);
        endif;

        for ($i = 0; $i < count($db); $i++) :
            if (strlen($db[$i]->img) > 0 && $db[$i]->img !== 'undefined') :

                $img = $db[$i]->img;
                $image = storage_path('app/public/uploads/image_law/' . $type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $db[$i]->img_base64 = $base64;
                $db[$i]->file_img = asset($image . $img);
            /* set statusname */
            // $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
            // $db[$i]->status_name = $db_status[0]->name;
            /* set statusname */
            else :
                // $db_status = DB::connection('mysql_ip_demo')->select('SELECT * FROM ip_status WHERE id = ?', [$db[$i]->status]);
                // $db[$i]->status_name = $db_status[0]->name;
                $image = storage_path('app/public/uploads/ip_demo/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $db[$i]->img_base64 = $base64;
                $db[$i]->img = 'noimage.jpg';
                $db[$i]->file_img = $image . '/' . 'noimage.jpg';;
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

    public function editFile_attach(Request $request)
    {

        $id = $_POST['id'];

        $file = $request->file('file');
        // $name = $file->getName();
        $Extension = $file->getClientOriginalExtension();
        $originalName = $request->file->hashName();

        $db = DB::connection('mysql_ip_demo')->table('data_law')->where('id', $id)->first();
        $dbUpdate = DB::connection('mysql_ip_demo')->table('data_law')->where('id', $id)->update(
            [
                'img' => $db->reqnum . '.' . $Extension,
            ]
        );

        $path = "public/uploads/image_law/" . $db->type . '/' . $db->reqnum . '.' . $Extension;

        Storage::disk('local')->put($path, file_get_contents($request->file));

        return json_encode($db);
    }

    public function test_fetcharray()
    {
        $data = $_POST['data'];
        return json_decode($data);
    }

    public function updateDataLaw()
    {
        $id = $_POST['id'];
        $db = DB::connection('mysql_ip_demo')->table('data_law')->where('id', $id)->first();
        if (strlen($_POST['datereq']) > 0  && $_POST['datereq'] !== 'null') {
            $datereq = $_POST['datereq'];
        } else {
            $datereq = $db->datereq;
        }
        if (strlen($_POST['issuedate']) > 0 && $_POST['issuedate'] !== 'null') {
            $issuedate = $_POST['issuedate'];
        } else {
            $issuedate = $db->issuedate;
        }
        if (strlen($_POST['expiredate']) > 0  && $_POST['expiredate'] !== 'null') {
            $expiredate = $_POST['expiredate'];
        } else {
            $expiredate = $db->expiresdate;
        }
        $db = DB::connection('mysql_ip_demo')->table('data_law')->where('id', $id)->update(
            [
                'datereq' => $datereq,
                'issuedate' => $issuedate,
                'expiresdate' => $expiredate
            ]
        );

        return json_encode($db);
    }
}



    // // export รูปออก
    // $mandalas_path = base_path('storage\app\public\uploads\image_law\signature.png');
    // $drawing = new Drawing();
    // $drawing->setWorksheet($sheet);
    // $drawing->setPath($mandalas_path)
    //     ->setHeight(150)
    //     ->setCoordinates('B5')
    //     ->setOffsetX(20)
    //     ->setOffsetY(20);
    // $sheet->getRowDimension(10)
    //     ->setRowHeight(150);
    // $sheet->getColumnDimension('B')
    //     ->setWidth(50);
    // // export รูปออก