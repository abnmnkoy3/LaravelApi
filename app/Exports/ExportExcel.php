<?php

namespace App\Exports;

use App\Models\ExportData;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ExportExcel implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return collect(ExportData::getAllData());
        // return ExportData::all();
    }
    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('signature');
        $drawing->setDescription('This is my signatuer');
        // $drawing->setPath(public_path('/uploads/signatures'));
        $drawing->setPath(storage_path('app/public/uploads/ip_demo/cert_2801.jpg'));
        $drawing->setHeight(90);
        $drawing->setCoordinates('B3');

        return $drawing;
    }

    public function headings(): array
    {
        return [
            'datecreate',
            'workid',
            'product_name',
            'img',
            'product_type',
            'description',
            'date_start',
            'deadline',
            'postdate',
            'status',
        ];
    }
}
