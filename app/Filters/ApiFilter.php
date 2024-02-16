<?php
namespace App\Filters;

use Illuminate\Http\Request;

class ApiFilter {
    protected $safeParms = [];

    protected $columnMap = [];

    protected $operatorMap = [];

    public function transform(Request $request){
        $eloQuery = [];

        foreach ($this->safeParms as $parm => $operators){
            $query = $request->query($parm);
            // $query2 = $request->query($this->type);

            if(!isset($query)){
                continue;
            }

            $column = $this->columnMap[$parm] ?? $parm; //[$this->safeParms] ?? $this->safeParms;
            // $column2 = $this->columnMap2; //[$this->safeParms] ?? $this->safeParms;

            foreach ($operators as $operator){
                if(isset($query[$operator])){
                        $eloQuery[] = [$column,$this->operatorMap[$operator], $query[$operator]];
                }
                // else{
                //     $eloQuery[] = 'notfound';
                // }
            }
        }

        return $eloQuery;
    }
}