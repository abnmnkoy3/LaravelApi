<?php
namespace App\Filters\V1;

use Illuminate\Http\Request;
use App\Filters\ApiFilter;


class InvoicesFilter extends ApiFilter {

    protected $safeParms = [
        'customerId' => ['eq'],
        'amount' => ['eq']
    ];

    protected $columnMap = [
        'customerId' => 'customer_Id',
    ];

    protected $operatorMap = [
        'eq' => '=',
        'lt' => '<'
    ];



}