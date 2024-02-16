<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Controllers\Controller;
// use App\Http\Resources\V1\CustomerResource;
use App\Http\Resources\V1\CustomerCollection;
use Illuminate\Http\Request;
use App\Filters\V1\CustomersFilter;
// use App\Http\Requests\V1\UpdateCustomerRequest;
// use GuzzleHttp\Psr7\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {


        $filter = new CustomersFilter();
        $queryItems = $filter->transform($request);
        // // $p = Customer::all();

        // Customer::where($queryItems);
        // return Customer::all();
        if (count($queryItems) == 0) {
            // print("test");`
            return new CustomerCollection(Customer::paginate());
        } else {
            $customer = Customer::where($queryItems)->paginate();
            $id = new CustomerCollection($customer->appends($request->query()));

            if ($id != null) {
                // $sss = Customer::find();
                // $sss-> email = "testtawdwda";
                // $sss->save();

                // $update->email = 'testtttt';
                // $update->save();

            }
            return response($id);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProduct()
    {
        return response()->json(
            [
                'status' => 200,
                'message' => "response success",

            ]
        );
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage. 
     *
     * @param  \App\Http\Requests\StoreCustomerRequest  $request 
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCustomerRequest $request)
    {
        // Log::info($request->get('PRNumber'));
        $value = 0;

        if ($request->get('name') != "" || $request->get('name') != null) {
            $value = 1;
        }


        return response()->json(
            [
                'status' => 200,
                'message' => "response success",
                'data' => [
                    "id" => $value
                ]
            ]
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function show(Customer $customer)
    {
        // return $customer->postal_code;
        // return new CustomerResource($customer);
        // $p = Customer::find($customer);
        // return $p;
        // return Controller::find($customer);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function edit(Customer $customer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateCustomerRequest  $request
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $product = Customer::find($customer);
        $product->update($request->all());
        return $product;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Customer  $customer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Customer $customer)
    {
        //
    }


    public function getData($postal_code)
    {
        return Customer::find($postal_code);
    }

    // public function update(Request $request, $id)
    // {
    //     //
    //     $product = Product::find($id);
    //     $product->update($request->all());
    //     return $product;
    // }
}
