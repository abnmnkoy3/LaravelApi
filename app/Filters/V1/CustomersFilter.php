<?php

namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;


class CustomersFilter extends ApiFilter
{
    // protected $safeParms = 'PRnumber';
    // protected $type = 'Type';
    // protected $columnMap = 'postal_code';
    // protected $columnMap2 = 'type';

    protected $safeParms = [
        'Type' => ['eq'],
        'postalcode' => ['eq']
    ];

    protected $columnMap = [
        'postalcode' => 'postal_code',
        'Type' => 'type',
    ];

    protected $operatorMap = [
        'eq' => '=',
        'lt' => '<'
    ];


    public function transform(Request $request)
    {
        $eloQuery = [];

        foreach ($this->safeParms as $parm => $operators) {
            $query = $request->query($parm);
            // $query2 = $request->query($this->type);

            if (!isset($query)) {
                continue;
            }

            $column = $this->columnMap[$parm] ?? $parm; //[$this->safeParms] ?? $this->safeParms;
            // $column2 = $this->columnMap2; //[$this->safeParms] ?? $this->safeParms;

            foreach ($operators as $operator) {
                if (isset($query[$operator])) {
                    // if($column == 'Type') {
                    $eloQuery[] = [$column, $query[$operator]];
                    // }
                }else{
                    $eloQuery[] = [
                        'Nodata' => 'Not Found'
                    ];
                }
            }
        }

        return $eloQuery;
    }

    
}
