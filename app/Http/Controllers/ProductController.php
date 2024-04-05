<?php

namespace App\Http\Controllers;

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
// use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/* CORS API */

/* CORS API */

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");


class ProductController extends Controller
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

    public function getData(Request $request)
    {
        $data = $_POST;
        if ($data['Type'] !== 'trademark') :
            $query = DB::connection('mysql_law')->select('SELECT * FROM petty_patent WHERE type = ? ', [$data['Type'], $data['Type']]);
        else :
            $query = DB::connection('mysql_law')->select('SELECT * FROM trade_mark WHERE type = ? ', [$data['Type']]);
        endif;
        for ($i = 0; $i < count($query); $i++) :
            if (strlen($query[$i]->img) > 0 && $query[$i]->img !== 'undefined') :
                $Type = $query[$i]->type;
                $img = $query[$i]->img;
                $image = storage_path('app/public/uploads/' . $Type . '/');
                $base64 = base64_encode(file_get_contents($image . $img));
                $query[$i]->img_base64 = $base64;
                $query[$i]->file_img = asset($image . $img);
            else :
                $Type = $query[$i]->type;
                $image = storage_path('app/public/uploads/' . $Type . '/');
                $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                $query[$i]->img_base64 = $base64;
                $query[$i]->file_img = asset($image . 'noimage.jpg');
            endif;
        endfor;
        return json_encode($query);
    }

    function thaiMonthToNumber($month)
    {
        $thaiMonths = [
            'ม.ค.' => 1, 'ก.พ.' => 2, 'มี.ค.' => 3, 'เม.ย.' => 4, 'พ.ค.' => 5, 'มิ.ย.' => 6,
            'ก.ค.' => 7, 'ส.ค.' => 8, 'ก.ย.' => 9, 'ต.ค.' => 10, 'พ.ย.' => 11, 'ธ.ค.' => 12,
        ];

        return $thaiMonths[$month];
    }

    /**
     * Display a listing of the resource.  
     *
     * @return \Illuminate\Http\Response
     */

    public function addProduct()
    {
        $fromData = $_POST;
        if ($fromData['Type'] !== 'trademark') :
            $db = DB::connection('mysql_law')->table('trade_mark')->insert([
                'name' => $fromData['name'],
                'name_create' => $fromData['name_create'],
                'type' => $fromData['Type'],
                'img' => $fromData['image'],
                'num_req' => $fromData['num_req'],
                'date_req' => $fromData['date_req'],
                'num_patent' => $fromData['num_patent'],
                'status_now' => $fromData['status_now'],
                'status_next' => $fromData['status_next'],
                'status' => '1',
                'regis_date' => $fromData['regis_date'],
                'patent_expire' => $fromData['patent_expire']
            ]);
            if ($db) :
                return json_encode(200); ///
            else :
                return json_encode('Error');
            endif;
        else :
            $db = DB::connection('mysql_law')->table('trade_mark')->insert([
                'img' => $fromData['image'],
                'type' => $fromData['Type'],
                'regis_num' => $fromData['regis_num'],
                'request_num' => $fromData['request_num'],
                'regis_date' => $fromData['regis_date'],
                'type_num' => $fromData['type_num'],
                'description' => $fromData['description'],
                'lastrenew_date' => $fromData['lastrenew_date'],
                'nextrenew_date' => $fromData['nextrenew_date'],
                'expire_date' => $fromData['expire_date'],
                'remark' => $fromData['remark'],
                'status' => '1',
            ]);
            if ($db) :
                return json_encode(200);
            else :
                return json_encode('Error');
            endif;
        endif;
    }

    public function uploadFile(Request $request)
    {
        $file = $request->file('file');
        if ($_POST['Type'] !== 'trademark') :
            $originalName = $request->file->getClientOriginalName();
            $path = "public/uploads/" . $_POST['Type'] . "/" . $originalName;
            Storage::disk('local')->put($path, file_get_contents($request->file));
        else :
            $originalName = $request->file->getClientOriginalName();
            if (isset($originalName)) :
                $path = "public/uploads/trademark/" . $originalName;
                Storage::disk('local')->put($path, file_get_contents($request->file));
            endif;
        endif;
    }


    public function editProduct()
    {
        $fromData = $_POST;
        if ($fromData['Type'] !== 'trademark') {
            $db = DB::connection('mysql_law')->table('petty_patent')->where('id', $fromData['Id'])->update([
                'name' => $fromData['name'],
                'name_create' => $fromData['name_create'],
                'type' => $fromData['Type'],
                'img' => $fromData['image'],
                'num_req' => $fromData['num_req'],
                'date_req' => Date($fromData['date_req']),
                'num_patent' => $fromData['num_patent'],
                'status_now' => $fromData['status_now'],
                'status_next' => $fromData['status_next'],
                'status' => '1',
                'regis_date' => $fromData['regis_date'],
                'patent_expire' => $fromData['patent_expire']
            ]);
            if ($db) :
                return json_encode(200);
            else :
                return json_encode('Error');
            endif;
        } else {
            $db = DB::connection('mysql_law')->table('trade_mark')->where('id', $fromData['Id'])->update([
                'img' => $fromData['image'],
                'type' => $fromData['Type'],
                'regis_num' => $fromData['regis_num'],
                'request_num' => $fromData['request_num'],
                'regis_date' => $fromData['regis_date'],
                'type_num' => $fromData['type_num'],
                'description' => $fromData['description'],
                'lastrenew_date' => $fromData['lastrenew_date'],
                'nextrenew_date' => $fromData['nextrenew_date'],
                'status' => '1',
                'expire_date' => $fromData['expire_date'],
                'remark' => $fromData['remark']
            ]);
            if ($db) :
                return json_encode(200);
            else :
                return json_encode('Error');
            endif;
        }
    }

    public function getsumAll()
    {
        $sumAll = array();
        $db1 =  DB::connection('mysql_law')->select('SELECT YEAR(regis_date)as yeardiff,COUNT(*) as countdiff,type FROM `trade_mark`  GROUP BY YEAR(regis_date),type');
        // $db1 =  DB::select('SELECT YEAR(regis_date)as yeardiff,COUNT(*) as countdiff,nametype FROM `trade_mark` JOIN `name_type` ON type = type_check GROUP BY YEAR(regis_date),type');
        if ($db1) :
            foreach ($db1 as $query1) :
                array_push($sumAll, ['year' => $query1->yeardiff, 'count' => $query1->countdiff, 'types' => $query1->type]);
            endforeach;
        endif;
        $db2 = DB::connection('mysql_law')->select('SELECT YEAR(regis_date)as yeardiff,COUNT(*) as countdiff,type FROM `petty_patent` GROUP BY YEAR(regis_date),type');
        if ($db2) :
            foreach ($db2 as $query2) :
                array_push($sumAll, ['year' => $query2->yeardiff, 'count' => $query2->countdiff, 'types' => $query2->type]);
            endforeach;
        endif;


        return json_encode($sumAll);
    }
    public function getNametype()
    {
        $Nametype = array();
        $db_nametype = DB::connection('mysql_law')->select('SELECT * FROM `name_type`');
        $db1 =  DB::connection('mysql_law')->select('SELECT YEAR(regis_date)as yeardiff,COUNT(*) as countdiff,type FROM `trade_mark`  GROUP BY YEAR(regis_date),type');

        if ($db_nametype) :
            foreach ($db_nametype as $keys => $queryname) :
                $Nametype[$queryname->type_check] = ['keys' => $keys + 1, 'Nametype' => $queryname->nametype, 'type_check' => $queryname->type_check];
            endforeach;
        endif;
        return json_encode($Nametype);
    }


    public function getYearAll()
    {
        $yearAll = array();
        $yearAlls = array();
        $year_db1 = DB::connection('mysql_law')->select('SELECT YEAR(regis_date) as year1s FROM `trade_mark` GROUP BY YEAR(regis_date)');

        if ($year_db1) :
            foreach ($year_db1 as $year_1) :
                array_push($yearAll, $year_1->year1s);
            endforeach;
        endif;

        $year_db2 = DB::connection('mysql_law')->select('SELECT YEAR(regis_date) as year2s FROM `petty_patent` GROUP BY YEAR(regis_date)');
        if ($year_db2) :
            foreach ($year_db2 as $year_2) :
                array_push($yearAll, $year_2->year2s);
            endforeach;
        endif;
        $yearAll = array_unique($yearAll);
        $year_collect = collect($yearAll);
        $yearAll_new = $year_collect->sort();
        foreach ($yearAll_new as $yearAll) :
            array_push($yearAlls, $yearAll);
        endforeach;

        return $yearAlls;
    }

    public function test()
    {
        $test_year = $this->getYearAll();
        $totals = array();
        foreach ($test_year as $years) :
            $select_count_year = DB::connection('mysql_law')->select('SELECT COUNT(*) as counts,type as types,YEAR(regis_date) as years FROM petty_patent WHERE YEAR(regis_date) = ? GROUP BY type,YEAR(regis_date)', [$years]);
            foreach ($select_count_year as $keys => $count_year) :
                if ($count_year->years ==  $years) :
                    if (isset($totals[$count_year->types])) :
                        $totals[$count_year->types] += array($count_year->years => $count_year->counts);
                    else :
                        $totals[$count_year->types] = array($count_year->years => $count_year->counts);
                    endif;
                endif;
            endforeach;

            $select_count_year_trademark = DB::connection('mysql_law')->select('SELECT COUNT(*) as counts,type as types,YEAR(regis_date) as years FROM trade_mark WHERE YEAR(regis_date) = ? GROUP BY type,YEAR(regis_date)', [$years]);
            foreach ($select_count_year_trademark as $keys => $count_year) :
                if (isset($totals[$count_year->types])) :
                    $totals[$count_year->types] += array($count_year->years => $count_year->counts);
                else :
                    $totals[$count_year->types] = array($count_year->years => $count_year->counts);
                endif;
            endforeach;
        endforeach;
        return $totals;
    }

    public function totalAlls()
    {
        $total = array();
        $db_1 = DB::connection('mysql_law')->select('SELECT COUNT(*)as totals,type as types FROM trade_mark GROUP BY type');
        $db_2 = DB::connection('mysql_law')->select('SELECT COUNT(*)as totals,type as types FROM petty_patent GROUP BY type');

        $db_type = DB::connection('mysql_law')->select('SELECT type_check FROM name_type ');

        foreach ($db_type as $db_type_check) :
            foreach ($db_1 as $db_one) :
                if ($db_type_check->type_check === $db_one->types) :
                    $total[] =  $db_one->totals;
                // else :
                //     $total[] = 0;
                endif;
            endforeach;
        endforeach;

        foreach ($db_type as $db_type_check2) :
            foreach ($db_2 as $db_two) :
                if ($db_type_check2->type_check === $db_two->types) :
                    $total[] =  $db_two->totals;
                // else :
                //     $total[] = 0;
                endif;

            endforeach;
        endforeach;
        return $total;
    }


    function convertThaiDateToGregorian($thaiDate)
    {
        // Split the Thai date by '/'
        $parts = explode(' ', $thaiDate);
        // var_dump($parts);
        // Extract day, month, and year
        $day = intval($parts[0]);
        $month = $this->thaiMonthToNumber($parts[1]);
        $year = intval('25' . $parts[2]) - 543; // Convert Buddhist era to Gregorian

        // Create Carbon object
        $carbonDate = Carbon::createFromDate($year, $month, $day);

        // Format the date
        return $carbonDate->format('Y-m-d');
    }

    public function importData(Request $request)
    {
        $the_file = $request->file('file');
        $type_post = $_POST['Type'];
        $product_type = $_POST['product_type'];

        $data = array();

        if ($product_type === 'TM') :
            //spreadsheet
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->getActiveSheet();
            $row_limit    = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range(5, $row_limit);
            $startcount = 5;

            $drawings = $sheet->getDrawingCollection();

            foreach ($drawings as $drawing) :
                $coordinates = $drawing->getCoordinates();
                $drawing_path = $drawing->getPath();
                $drawing_name = $drawing->getName();
                $extension = pathinfo($drawing_path, PATHINFO_EXTENSION);

                $img_url = "public/uploads/image_law/{$product_type}/{$drawing_name}";
                $img_path = storage_path($img_url);

                $contents = file_get_contents($drawing_path);
                Storage::disk('local')->put($img_url, file_get_contents($drawing_path));
                // file_put_contents($img_path, $contents);
                if (substr($coordinates, 0, 1) === 'B') :
                    $dataDrawing[] = [
                        'cell' => substr($coordinates, 0, 1),
                        'row' => substr($coordinates, 1),
                        'drawing_name' => $drawing_name,
                        'img_path' => $img_path
                    ];
                endif;
            // return json_encode($img_path);
            endforeach;
            //spreadsheet
            $Num = 0;
            $patentnum = '';
            $reqnum = '';
            $datereq = '';
            $category = '';
            $product_name = '';
            $renewdate = '';
            $issuedate = '';
            $expiresdate = '';
            $remark = '';
            $numberrow = 0;
            foreach ($row_range as $row) {

                $patentnum              .= $sheet->getCell('E' . $row)->getValue();
                $reqnum            .= $sheet->getCell('F' . $row)->getValue();
                $category               .= $sheet->getCell('H' . $row)->getValue();
                $product_name            .=  $sheet->getCell('I' . $row)->getValue();
                if (strlen($sheet->getCell('G' . $row)->getFormattedValue()) > 0) :
                    $datereq       .= $sheet->getCell('G' . $row)->getFormattedValue();
                endif;
                if (strlen($sheet->getCell('J' . $row)->getFormattedValue()) > 0) :
                    $renewdate   .= $sheet->getCell('J' . $row)->getFormattedValue();
                endif;
                if (strlen($sheet->getCell('K' . $row)->getFormattedValue()) > 0) :
                    $issuedate   .= $sheet->getCell('K' . $row)->getFormattedValue();
                endif;
                if (strlen($sheet->getCell('L' . $row)->getFormattedValue()) > 0) :
                    $expiresdate      .= $sheet->getCell('L' . $row)->getFormattedValue();
                endif;
                $remark                 .= $sheet->getCell('M' . $row)->getValue();
                $Num++;
                if ($Num === 5) :
                    $data[] = [
                        'img' => $dataDrawing[$numberrow]['drawing_name'],
                        // 'img' => '',
                        'patentnum' => $patentnum,
                        'reqnum' => $reqnum,
                        'datereq' => $datereq,
                        'category' => $category,
                        'product_name' => $product_name,
                        'renewdate' => $renewdate,
                        'issuedate' => $issuedate,
                        'expiresdate' => $expiresdate,
                        'remark' => $remark,
                        'type' => $product_type,
                    ];
                    $Num = 0;
                    $patentnum = '';
                    $reqnum = '';
                    $category = '';
                    $product_name = '';
                    $datereq = '';
                    $renewdate = '';
                    $issuedate = '';
                    $expiresdate = '';
                    $remark = '';
                    $numberrow++;
                endif;
                $startcount++;
            }
        elseif ($product_type === 'PT') :
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->getActiveSheet();
            $row_limit    = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range(5, $row_limit);
            $startcount = 5;

            $drawings = $sheet->getDrawingCollection();

            foreach ($drawings as $drawing) :
                $coordinates = $drawing->getCoordinates();
                $drawing_path = $drawing->getPath();
                $drawing_name = $drawing->getName();
                $extension = pathinfo($drawing_path, PATHINFO_EXTENSION);

                $img_url = "public/uploads/image_law/{$product_type}/{$drawing_name}";
                $img_path = storage_path($img_url);

                $contents = file_get_contents($drawing_path);
                Storage::disk('local')->put($img_url, file_get_contents($drawing_path));
                // file_put_contents($img_path, $contents);
                if (substr($coordinates, 0, 1) === 'C') :
                    $dataDrawing[] = [
                        'cell' => substr($coordinates, 0, 1),
                        'row' => substr($coordinates, 1),
                        'drawing_name' => $drawing_name,
                        'img_path' => $img_path
                    ];
                endif;
            // return json_encode($img_path);
            endforeach;
            //spreadsheet
            $Num = 0;
            $patentnum = '';
            $reqnum = '';
            $datereq = '';
            $category = '';
            $product_name = '';
            $renewdate = '';
            $issuedate = '';
            $expiresdate = '';
            $remark = '';
            $statusnow = '';
            $statusnext = '';
            $numberrow = 0;
            foreach ($row_range as $row) {

                $patentnum              .= $sheet->getCell('F' . $row)->getValue();
                $reqnum            .= $sheet->getCell('D' . $row)->getValue();
                $statusnow            .= $sheet->getCell('G' . $row)->getValue();
                $statusnext  .= $sheet->getCell('H' . $row)->getValue();
                // $category               .= $sheet->getCell('H' . $row)->getValue();
                $product_name            .=  $sheet->getCell('B' . $row)->getValue();
                if (strlen($sheet->getCell('E' . $row)->getFormattedValue()) > 0) :
                    $datereq       .= $sheet->getCell('E' . $row)->getFormattedValue();
                endif;
                // if (strlen($sheet->getCell('J' . $row)->getFormattedValue()) > 0) :
                //     $renewdate   .= $sheet->getCell('J' . $row)->getFormattedValue();
                // endif;
                if (strlen($sheet->getCell('I' . $row)->getFormattedValue()) > 0) :
                    $issuedate   .= $sheet->getCell('I' . $row)->getFormattedValue();
                endif;
                if (strlen($sheet->getCell('J' . $row)->getFormattedValue()) > 0) :
                    $expiresdate      .= $sheet->getCell('J' . $row)->getFormattedValue();
                endif;
                // $remark                 .= $sheet->getCell('M' . $row)->getValue();
                $Num++;
                if ($Num === 4) :
                    $data[] = [
                        'img' => $dataDrawing[$numberrow]['drawing_name'],
                        // 'img' => '',
                        'patentnum' => $patentnum,
                        'reqnum' => $reqnum,
                        'datereq' => $datereq,
                        'category' => $category,
                        'product_name' => $product_name,
                        'renewdate' => $renewdate,
                        'issuedate' => $issuedate,
                        'statusnow' => $statusnow,
                        'statusnext' => $statusnext,
                        'expiresdate' => $expiresdate,
                        'remark' => $remark,
                        'type' => $product_type,
                    ];
                    $Num = 0;
                    $patentnum = '';
                    $reqnum = '';
                    $category = '';
                    $product_name = '';
                    $datereq = '';
                    $renewdate = '';
                    $issuedate = '';
                    $statusnow = '';
                    $statusnext = '';
                    $expiresdate = '';
                    $remark = '';
                    $numberrow++;
                endif;
                $startcount++;
            }
        elseif ($product_type === 'PT2') :
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->getActiveSheet();
            $row_limit    = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range(5, $row_limit);
            $startcount = 5;

            $drawings = $sheet->getDrawingCollection();

            foreach ($drawings as $drawing) :
                $coordinates = $drawing->getCoordinates();
                $drawing_path = $drawing->getPath();
                $drawing_name = $drawing->getName();
                $extension = pathinfo($drawing_path, PATHINFO_EXTENSION);

                $img_url = "public/uploads/image_law/{$product_type}/{$drawing_name}";
                $img_path = storage_path($img_url);

                $contents = file_get_contents($drawing_path);
                Storage::disk('local')->put($img_url, file_get_contents($drawing_path));
                // file_put_contents($img_path, $contents);
                if (substr($coordinates, 0, 1) === 'C') :
                    $dataDrawing[] = [
                        'cell' => substr($coordinates, 0, 1),
                        'row' => substr($coordinates, 1),
                        'drawing_name' => $drawing_name,
                        'img_path' => $img_path
                    ];
                endif;
            // return json_encode($img_path);
            endforeach;
            //spreadsheet
            $Num = 0;
            $patentnum = '';
            $reqnum = '';
            $datereq = '';
            $category = '';
            $product_name = '';
            $renewdate = '';
            $issuedate = '';
            $expiresdate = '';
            $remark = '';
            $statusnow = '';
            $statusnext = '';
            $numberrow = 0;
            foreach ($row_range as $row) {

                $patentnum              .= $sheet->getCell('F' . $row)->getValue();
                $reqnum            .= $sheet->getCell('D' . $row)->getValue();
                $statusnow            .= $sheet->getCell('G' . $row)->getValue();
                $statusnext  .= $sheet->getCell('H' . $row)->getValue();
                // $category               .= $sheet->getCell('H' . $row)->getValue();
                $product_name            .=  $sheet->getCell('B' . $row)->getValue();
                if (strlen($sheet->getCell('E' . $row)->getFormattedValue()) > 0) :
                    $datereq       .= $sheet->getCell('E' . $row)->getFormattedValue();
                endif;
                // if (strlen($sheet->getCell('J' . $row)->getFormattedValue()) > 0) :
                //     $renewdate   .= $sheet->getCell('J' . $row)->getFormattedValue();
                // endif;
                if (strlen($sheet->getCell('I' . $row)->getFormattedValue()) > 0) :
                    $issuedate   .= $sheet->getCell('I' . $row)->getFormattedValue();
                endif;
                if (strlen($sheet->getCell('J' . $row)->getFormattedValue()) > 0) :
                    $expiresdate      .= $sheet->getCell('J' . $row)->getFormattedValue();
                endif;
                // $remark                 .= $sheet->getCell('M' . $row)->getValue();
                $Num++;
                if ($Num === 4) :
                    $data[] = [
                        'img' => $dataDrawing[$numberrow]['drawing_name'],
                        // 'img' => '',
                        'patentnum' => $patentnum,
                        'reqnum' => $reqnum,
                        'datereq' => $datereq,
                        'category' => $category,
                        'product_name' => $product_name,
                        'renewdate' => $renewdate,
                        'issuedate' => $issuedate,
                        'statusnow' => $statusnow,
                        'statusnext' => $statusnext,
                        'expiresdate' => $expiresdate,
                        'remark' => $remark,
                        'type' => $product_type,
                    ];
                    $Num = 0;
                    $patentnum = '';
                    $reqnum = '';
                    $category = '';
                    $product_name = '';
                    $datereq = '';
                    $renewdate = '';
                    $issuedate = '';
                    $statusnow = '';
                    $statusnext = '';
                    $expiresdate = '';
                    $remark = '';
                    $numberrow++;
                endif;
                $startcount++;
            }
        elseif ($product_type === 'PT3') :
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->getActiveSheet();
            $row_limit    = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range(5, $row_limit);
            $startcount = 5;

            $drawings = $sheet->getDrawingCollection();

            foreach ($drawings as $drawing) :
                $coordinates = $drawing->getCoordinates();
                $drawing_path = $drawing->getPath();
                $drawing_name = $drawing->getName();
                $extension = pathinfo($drawing_path, PATHINFO_EXTENSION);

                $img_url = "public/uploads/image_law/{$product_type}/{$drawing_name}";
                $img_path = storage_path($img_url);

                $contents = file_get_contents($drawing_path);
                Storage::disk('local')->put($img_url, file_get_contents($drawing_path));
                // file_put_contents($img_path, $contents);
                if (substr($coordinates, 0, 1) === 'C') :
                    $dataDrawing[] = [
                        'cell' => substr($coordinates, 0, 1),
                        'row' => substr($coordinates, 1),
                        'drawing_name' => $drawing_name,
                        'img_path' => $img_path
                    ];
                endif;
            // return json_encode($img_path);
            endforeach;
            //spreadsheet
            $Num = 0;
            $patentnum = '';
            $reqnum = '';
            $datereq = '';
            $category = '';
            $product_name = '';
            $renewdate = '';
            $issuedate = '';
            $expiresdate = '';
            $remark = '';
            $statusnow = '';
            $statusnext = '';
            $numberrow = 0;
            foreach ($row_range as $row) {

                $patentnum              .= $sheet->getCell('F' . $row)->getValue();
                $reqnum            .= $sheet->getCell('D' . $row)->getValue();
                $statusnow            .= $sheet->getCell('G' . $row)->getValue();
                $statusnext  .= $sheet->getCell('H' . $row)->getValue();
                // $category               .= $sheet->getCell('H' . $row)->getValue();
                $product_name            .=  $sheet->getCell('B' . $row)->getValue();
                if (strlen($sheet->getCell('E' . $row)->getFormattedValue()) > 0) :
                    $datereq       .= $sheet->getCell('E' . $row)->getFormattedValue();
                endif;
                // if (strlen($sheet->getCell('J' . $row)->getFormattedValue()) > 0) :
                //     $renewdate   .= $sheet->getCell('J' . $row)->getFormattedValue();
                // endif;
                if (strlen($sheet->getCell('I' . $row)->getFormattedValue()) > 0) :
                    $issuedate   .= $sheet->getCell('I' . $row)->getFormattedValue();
                endif;
                if (strlen($sheet->getCell('J' . $row)->getFormattedValue()) > 0) :
                    $expiresdate      .= $sheet->getCell('J' . $row)->getFormattedValue();
                endif;
                // $remark                 .= $sheet->getCell('M' . $row)->getValue();
                $Num++;
                if ($Num === 4) :
                    $data[] = [
                        'img' => $dataDrawing[$numberrow]['drawing_name'],
                        // 'img' => '',
                        'patentnum' => $patentnum,
                        'reqnum' => $reqnum,
                        'datereq' => $datereq,
                        'category' => $category,
                        'product_name' => $product_name,
                        'renewdate' => $renewdate,
                        'issuedate' => $issuedate,
                        'statusnow' => $statusnow,
                        'statusnext' => $statusnext,
                        'expiresdate' => $expiresdate,
                        'remark' => $remark,
                        'type' => $product_type,
                    ];
                    $Num = 0;
                    $patentnum = '';
                    $reqnum = '';
                    $category = '';
                    $product_name = '';
                    $datereq = '';
                    $renewdate = '';
                    $issuedate = '';
                    $statusnow = '';
                    $statusnext = '';
                    $expiresdate = '';
                    $remark = '';
                    $numberrow++;
                endif;
                $startcount++;
            }
        endif;
        // return json_encode($data);
        if ($product_type == 'TM' || $product_type == 'PT' || $product_type == 'PT2' || $product_type == 'PT3') :
            DB::connection('mysql_ip_demo')->table('data_law')->insert($data);
        endif;
        return json_encode(['message => successful']);
    }

    public function getfileOther()
    {
        $type_id = $_POST['type_id'];
        $type = $_POST['Type'];
        $db =  DB::connection('mysql_law')->select('SELECT * FROM `file_other` WHERE type_id = ?', [$type_id]);
        for ($i = 0; $i < count($db); $i++) :
            $Type = $db[$i]->type;
            $filename = $db[$i]->name;
            $file = storage_path('app/public/uploads/file_other/' . $type . '/');
            $base64 = base64_encode(file_get_contents($file . $filename));
            $db[$i]->img_base64 = $base64;
            $db[$i]->file_img = asset($file . $filename);
        endfor;
        return json_encode($db);
    }

    public function addfileOther()
    {
        $type_id = $_POST['type_id'];
        $type = $_POST['Type'];
        $name_file = $_POST['file_name'];
        $db = DB::connection('mysql_law')->table('file_other')->insert([
            'name' => $name_file,
            'type' => $type,
            'status' => '1',
            'type_id' => $type_id
        ]);
        return json_encode($db);
    }

    public function uploadFileOther(Request $request)
    {
        $file = $request->file('file');
        $originalName = $request->file->getClientOriginalName();
        $path = "public/uploads/file_other/" . $_POST['Type'] . "/" . $originalName;
        Storage::disk('local')->put($path, file_get_contents($request->file));
    }

    // DATE_NOW;

    // DATE_EXPIRE;

    // RESULT = ["DATE_NOW DIFF DATE_EXPIRE"];

    // RESULT <= "31";

    // ALERT[" THIS PATENT MONTH EXPIRE < 1 MONTH"];



    ///// KAIZEN //////

    public function insert_step_1()
    {
        $data = $_POST;
        // $createby = $data['createby'];
        $dataCreate = '';
        $date_now = date('Y-m-d');
        if (isset($data['sig_create'])) :
            $dataCreate = $data['sig_create'];
        endif;
        $docnumber = 'Kz-0001';
        $db = DB::connection('mysql_kaizen')->table('kaizen_data')->insert([
            'createby' => $data['createby'],
            'sig_create' => $dataCreate,
            'date_createby' => $date_now,
            'docnumber' => $docnumber,
            'img' => $data['img'],
            'img_after' => $data['img_after'],
            'title' => $data['title'],
            'division' => $data['division'],
            'before_data' => $data['textbefore'],
            'after_data' => $data['textafter'],
            'step_now' => $data['step_now'],
            'employee_number' => $data['employee_number'],
            'status' => $data['status'],
        ]);
        return json_encode('[INSERT] => OK');
    }

    public function get_datastep_1()
    {
        $data = '117556';
        $data_page_type = 'my_kaizen';
        if ($data_page_type === 'my_kaizen') :
            $db = DB::connection('mysql_kaizen')->select('SELECT * FROM kaizen_data LEFT JOIN status_kaizen ON kaizen_data.status = status_code WHERE employee_number = ?', [$data]);
        elseif ($data_page_type === 'inbox') :
            $db = DB::connection('mysql_kaizen')->select('SELECT * FROM kaizen_data LEFT JOIN status_kaizen ON kaizen_data.status = status_code WHERE step_now = ?', [$data]);
        elseif ($data_page_type === 'all_kaizen') :
            $db = DB::connection('mysql_kaizen')->select('SELECT * FROM kaizen_data LEFT JOIN status_kaizen ON kaizen_data.status = status_code');
        endif;
        return json_encode($db);
    }

    public function getdataEdit()
    {
        $data = $_POST['id_kaizen'];
        $em_Id = $_POST['em_id'];
        $fullname = $_POST['firstname'] . ' ' . $_POST['lastname'];
        $datecreate = $_POST['datecreate'];
        $division = $_POST['division'];
        // var_dump($data);
        $db = DB::connection('mysql_kaizen')->select('SELECT * FROM kaizen_data LEFT JOIN score_kaizen ON kaizen_data.id = kz_id WHERE kaizen_data.id = ?', [$data]);
        if ($db) :
            for ($i = 0; $i < count($db); $i++) :
                if (strlen($db[$i]->img) > 0 && $db[$i]->img !== 'undefined') :
                    $img = $db[$i]->img;
                    $image = storage_path('app/public/uploads/Kaizen/');
                    $base64 = base64_encode(file_get_contents($image . $img));
                    $db[$i]->img = $base64;
                    $db[$i]->file_img = asset($image . $img);
                else :
                    $image = storage_path('app/public/uploads/Kaizen/');
                    $base64 = base64_encode(file_get_contents($image . 'noimage.jpg'));
                    $db[$i]->img_base64 = $base64;
                    $db[$i]->file_img = asset($image . 'noimage.jpg');
                endif;

                if (strlen($db[$i]->img_after) > 0 && $db[$i]->img_after !== 'undefined') :
                    $img_after = $db[$i]->img_after;
                    $image_after = storage_path('app/public/uploads/Kaizen/');
                    $base64 = base64_encode(file_get_contents($image_after . $img_after));
                    $db[$i]->img_after = $base64;
                    $db[$i]->file_img_after = asset($image_after . $img_after);
                else :
                    $image_after = storage_path('app/public/uploads/Kaizen/');
                    $base64 = base64_encode(file_get_contents($image_after . 'noimage.jpg'));
                    $db[$i]->img_after_base64 = $base64;
                    $db[$i]->file_img_after = asset($image_after . 'noimage.jpg');
                endif;

            endfor;


            $year_create = substr($db[0]->date_createby, 0, 4);
            $month_create = substr($db[0]->date_createby, 5, 2);
            $day_create = substr($db[0]->date_createby, 8, 2);
            $date_create_new = $day_create . '/' . $month_create . '/' . $year_create;
            $db[0]->date_createby = $date_create_new;

            $year_sup = substr($db[0]->date_sig_sup, 0, 4);
            $month_sup = substr($db[0]->date_sig_sup, 5, 2);
            $day_sup = substr($db[0]->date_sig_sup, 8, 2);
            $date_sup_new = $day_sup . '/' . $month_sup . '/' . $year_sup;
            $db[0]->date_sig_sup = $date_sup_new;

            $year_mgr = substr($db[0]->date_sig_mgr, 0, 4);
            $month_mgr = substr($db[0]->date_sig_mgr, 5, 2);
            $day_mgr = substr($db[0]->date_sig_mgr, 8, 2);
            $date_mgr_new = $day_mgr . '/' . $month_mgr . '/' . $year_mgr;
            $db[0]->date_sig_mgr = $date_mgr_new;

            $year_eva = substr($db[0]->date_sig_eva, 0, 4);
            $month_eva = substr($db[0]->date_sig_eva, 5, 2);
            $day_eva = substr($db[0]->date_sig_eva, 8, 2);
            $date_eva_new = $day_eva . '/' . $month_eva . '/' . $year_eva;
            $db[0]->date_sig_eva = $date_eva_new;

            $year_committee = substr($db[0]->date_sig_committee, 0, 4);
            $month_committee = substr($db[0]->date_sig_committee, 5, 2);
            $day_committee = substr($db[0]->date_sig_committee, 8, 2);
            $date_committee_new = $day_committee . '/' . $month_committee . '/' . $year_committee;
            $db[0]->date_sig_committee = $date_committee_new;

            $datajson = $db[0];
        else :
            $datajson = [
                "id" => null,
                "createby" => $fullname,
                "date_createby" => $datecreate,
                "title" => "",
                "division" => $division,
                "before_data" => "",
                "after_data" => "",
                "employee_number" => $em_Id,
                "effects_q" => "",
                "effects_c" => "",
                "effects_d" => "",
                "effects_s" => "",
                "effects_e" => "",
                "comment_ass" => "",
                "comment_status" => "",
                "concern" => "",
                "score1_1_1" => "",
                "score1_1_2" => "",
                "score1_2_1" => "",
                "score1_2_2" => "",
                "score1_3_1" => "",
                "score1_3_2" => "",
                "score1_4_1" => "",
                "score1_4_2" => "",
                "score1_5_1" => "",
                "score1_5_2" => "",
                "score2_1_1" => "",
                "score2_1_2" => "",
                "score2_2_1" => "",
                "score2_2_2" => "",
                "score2_3_1" => "",
                "score2_3_2" => "",
                "total1_1" => "",
                "total1_2" => "",
                "total2_1" => "",
                "total2_2" => "",
                "totalsum1" => "",
                "totalsum2" => "",
                "status" => "",
            ];
        endif;

        return json_encode($datajson);
    }
    public function deleteForm()
    {
        $data = $_POST['deleteID'];

        $db = DB::connection('mysql_kaizen')->table('kaizen_data')->where('id', $data)->delete();

        return json_encode('Delete Success');
    }

    public function cancelKaizen()
    {
        $em_id = $_POST['employee_number'];
        $id_kaizen = $_POST['id_kaizen'];

        $db_search = DB::connection('mysql_kaizen')->select('SELECT * FROM kaizen_data WHERE id = ? AND employee_number = ?', [$id_kaizen, $em_id]);

        if (isset($db_search)) :
            $db = DB::connection('mysql_kaizen')->table('kaizen_data')->where('id', $id_kaizen)->update(['status' => 'C']);
        else :
            $db = null;
        endif;

        return json_encode($db);
    }


    public function getstepNow()
    {
        $data = $_POST['employee_number'];

        $db = DB::connection('mysql_kaizen')->select('SELECT * FROM kaizen_data WHERE step_now = ?', [$data]);

        return json_encode($db);
    }

    public function step_sup()
    {
        $date_now = date('Y-m-d');
        $dataid = $_POST['id'];
        $data_sup = $_POST['sig_sup'];
        $data_status = $_POST['status'];
        $data_step_now = $_POST['step_now'];
        $db = DB::connection('mysql_kaizen')->table('kaizen_data')->where('id', $dataid)->update(['sig_sup' => $data_sup, 'step_now' => $data_step_now, 'status' => $data_status, 'date_sig_sup' => $date_now]);
        return json_encode($db);
    }

    public function step_mgr()
    {
        $date_now = date('Y-m-d');
        $dataid = $_POST['id'];
        $data_mgr = $_POST['sig_mgr'];
        $data_status = $_POST['status'];
        $data_step_now = $_POST['step_now'];
        $db = DB::connection('mysql_kaizen')->table('kaizen_data')->where('id', $dataid)->update(['effects_q' => $_POST['effects_q'], 'effects_c' => $_POST['effects_c'], 'effects_d' => $_POST['effects_d'], 'effects_s' => $_POST['effects_s'], 'effects_e' => $_POST['effects_e'], 'comment_ass' => $_POST['comment_ass'], 'sig_mgr' => $data_mgr, 'step_now' => $data_step_now, 'status' => $data_status, 'date_sig_mgr' => $date_now]);
        $db = DB::connection('mysql_kaizen')->table('score_kaizen')->insert([
            'kz_id' => $dataid,
            'score1_1_1' => $_POST['score1_1_1'],
            'score1_2_1' => $_POST['score1_2_1'],
            'score1_3_1' => $_POST['score1_3_1'],
            'score1_4_1' => $_POST['score1_4_1'],
            'score1_5_1' => $_POST['score1_5_1'],
            'score2_1_1' => $_POST['score2_1_1'],
            'score2_2_1' => $_POST['score2_2_1'],
            'score2_3_1' => $_POST['score2_3_1'],
            'total1_1' => $_POST['total1_1'],
            'total2_1' => $_POST['total2_1'],
            'totalsum1' => $_POST['totalsum1'],
        ]);

        return json_encode($db);
    }

    public function step_eva()
    {
        $date_now = date('Y-m-d');
        $dataid = $_POST['id'];
        $data_eva = $_POST['sig_eva'];
        $data_status = $_POST['status'];
        $data_step_now = $_POST['step_now'];
        $db = DB::connection('mysql_kaizen')->table('kaizen_data')->where('id', $dataid)->update(['comment_ok_notok' => $_POST['comment_ok_notok'], 'comment_status' => $_POST['comment_status'], 'sig_eva' => $data_eva, 'step_now' => $data_step_now, 'status' => $data_status, 'date_sig_eva' => $date_now]);
        return json_encode($db);
    }

    public function update_reject()
    {
        $data = $_POST;
        $dataID = $_POST['dataID'];
        // $createby = $data['createby'];
        $dataCreate = '';
        $date_now = date('Y-m-d');
        if (isset($data['sig_create'])) :
            $dataCreate = $data['sig_create'];
        endif;
        $docnumber = 'Kz-0001';
        $db = DB::connection('mysql_kaizen')->table('kaizen_data')->where('id', $dataID)->update(['title' => $_POST['title'], 'division' => $_POST['division'], 'before_data' => $_POST['textbefore'], 'after_data' => $_POST['textafter'], 'createby' => $_POST['createby'], 'employee_number' => $_POST['employee_number'], 'step_now' => $_POST['step_now'], 'status' => $_POST['status'], 'sig_create' => $_POST['sig_create']]);

        return json_encode('[UPDATE] => OK');
    }

    public function step_cmt()
    {
        $date_now = date('Y-m-d');
        $dataid = $_POST['id'];
        $sig_cmt = $_POST['sig_cmt'];
        $data_status = $_POST['status'];
        $money = $_POST['money'];
        $sum_money = $_POST['sum_money'];
        $data_step_now = $_POST['step_now'];
        $db = DB::connection('mysql_kaizen')->table('kaizen_data')->where('id', $dataid)->update(['step_now' => $data_step_now, 'status' => $data_status, 'sig_committee' => $sig_cmt, 'money' => $money, 'sum_money' => $sum_money, 'date_sig_committee' => $date_now]);
        $db = DB::connection('mysql_kaizen')->table('score_kaizen')->where('kz_id', $dataid)->update([
            'score1_1_2' => $_POST['score1_1_2'],
            'score1_2_2' => $_POST['score1_2_2'],
            'score1_3_2' => $_POST['score1_3_2'],
            'score1_4_2' => $_POST['score1_4_2'],
            'score1_5_2' => $_POST['score1_5_2'],
            'score2_1_2' => $_POST['score2_1_2'],
            'score2_2_2' => $_POST['score2_2_2'],
            'score2_3_2' => $_POST['score2_3_2'],
            'total1_2' => $_POST['total1_2'],
            'total2_2' => $_POST['total2_2'],
            'totalsum2' => $_POST['totalsum2'],
        ]);

        return json_encode($db);
    }

    public function rejectKaizen()
    {
        $data = $_POST;

        $dataID = $_POST['id'];

        $db = DB::connection('mysql_kaizen')->table('kaizen_data')->where('id', $dataID)->update(['sig_sup' => NULL, 'date_sig_sup' => NULL, 'sig_mgr' => NULL, 'date_sig_mgr' => NULL, 'sig_eva' => NULL, 'date_sig_eva' => NULL, 'sig_committee' => NULL, 'date_sig_committee' => NULL, 'status' => 'R', 'step_now' => NULL]);

        return json_encode('[REJECT] => OK');
    }

    public function comment_reject()
    {
        $data = $_POST;

        $db = DB::connection('mysql_kaizen')->table('comment_reject')->insert([
            'kz_id' => $_POST['kz_id'],
            'comment' => $_POST['comment'],
            'name_comment' => $_POST['name_comment']
        ]);
        return json_encode('[REJECT] => INSERT COMMENT OK');
    }

    public function import_file(Request $request)
    {
        $file = $request->file('file');
        $originalName = $request->file->getClientOriginalName();
        $path = "public/uploads/Kaizen/" . $originalName;

        $file_after = $request->file('file_after');
        $originalName_after = $request->file->getClientOriginalName();
        $path_after = "public/uploads/Kaizen/" . $originalName_after;

        Storage::disk('local')->put($path, file_get_contents($request->file));
        Storage::disk('local')->put($path_after, file_get_contents($request->file_after));
    }


    public function get_Sup()
    {
        $db = DB::connection('mysql_kpi')->table('employee')->select('Em_Firstname as name', 'Em_Lastname as lastname', 'Em_Employee_Number as code')->where('Em_Dept', 'Management Information System')->whereIn('Em_Level', [5, 6])->get();

        for ($i = 0; $i < count($db); $i++) :
            $db[$i]->name = $db[$i]->code . ' ' . $db[$i]->name . ' ' . $db[$i]->lastname;
        endfor;

        return json_encode($db);
    }
    public function get_ass_mgr()
    {
        $db = DB::connection('mysql_kpi')->table('employee')->select('Em_Firstname as name', 'Em_Lastname as lastname', 'Em_Employee_Number as code')->where('Em_Dept', 'Management Information System')->whereIn('Em_Level', [9, 10])->get();

        for ($i = 0; $i < count($db); $i++) :
            $db[$i]->name = $db[$i]->code . ' ' . $db[$i]->name . ' ' . $db[$i]->lastname;
        endfor;

        return json_encode($db);
    }
}
