<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use DB;
use Illuminate\Support\Facades\DB;

class ExportData extends Model
{
    use HasFactory;

    public static function getAllData()
    {
        $result = DB::connection('mysql_ip_demo')->select('SELECT date_create,workid,product_name,img,product_type,description,work_start,deadline FROM ip_data');
        return $result;
    }
}
