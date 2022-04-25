<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\CustomerState;
use App\Http\Requests;
use App\Models\StockOrder;
use App\Models\CustomerPayment;
use App\Models\Stocklist;

use File;
use ZipArchive;



use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;



class CustomerListController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {  
       $search = $request->input('search');
      $customerlist=  StockOrder::query()->with('customer_data','product');

      if($search)
      {
         $customerlist=$customerlist->where('otr_price','Like','%'.$search.'%')
           ->orWhereHas('customer_data',function ($customerlist)use($search)
        {
          $customerlist->where('customer_name','Like','%'.$search.'%')->orWhere('customer_email','Like','%'.$search.'%')->orWhere('customer_phone','Like','%'.$search.'%');
            })->orWhereHas('product',function ($customerlist)use($search)
              {
                  $customerlist->where('ProChassisNo','Like','%'.$search.'%');
              });
      }
      $customerlist=$customerlist->paginate(8);

      return view('customer.index',compact('customerlist','search'));

    }


    public function dash(Request $request)
    {
        $orderlist= Stockorder::all()->take(-5);
        $customerlist =CustomerPayment::all()->take(-5);

        $previous_week = strtotime("-1 week +1 day");

        $start_week = strtotime("last sunday midnight",$previous_week);
        $end_week = strtotime("next saturday",$start_week);

        $start_week = date("Y-m-d",$start_week);
        $end_week = date("Y-m-d",$end_week);
        

      foreach($orderlist as $key=>$order){

        $product_id = $order->product_id;
        $product_details = Stocklist::find($product_id);


        $customer_id  = $order->customer_id;
        $customer_details = Customer::find($customer_id);

        $orderlist[$key]->product = $product_details;
        $orderlist[$key]->customer = $customer_details;

      }

      return view('/dashboard',compact('orderlist','customerlist','end_week'));




    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   
     $statelist=CustomerState::all();
     return view('customer.create',compact('statelist'));
    }


   public function get($customer_id)
   {   
    $customer=Customer::find($customer_id);
    $doclist = CustomerDocument::where('customer_id',$customer_id)->get();
    foreach ($doclist as $key => $doc) {
      $doclist[$key]['document_url']  = Storage::url('storage/app/public/'.$doc->document_url);
     }


    return view('customer.document',compact('doclist'));
   }

    public function order($customer_id){
  
       $customer=Customer::find($customer_id);
        $order_id=$customer->order->id;
        $order=StockOrder::find($order_id);
        $product_id=$order->product_id;
        $pro_id=Stocklist::find($product_id);
        $approved_document=$pro_id->approved_document;
     
      return Storage::download($approved_document);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
     
      $customer=Customer::create([
      'customer_name'=>$request->customer_name,
      'customer_email'=>$request->customer_email,
      'customer_phone'=>$request->customer_phone,
      'my_kad'=>$request->my_kad,
      'buss_reg'=>$request->buss_reg,
      'business_name'=>$request->business_name,
      'address'=>$request->address,
      'state'=>$request->state_name,

    ]);
      
     $extensionPassport = $request->file('passport',)->getClientOriginalExtension(); 
     $uploadPassport = $request->file('passport')->store('attachment');
     $customer_document=CustomerDocument::create([
      'customer_id'=>$customer->customer_id,
      'document_ext'=>$extensionPassport, 
      'document_type'=>'passport',
      'document_url'=>$uploadPassport,
    ]);

     $dlextension=$request->file('driving_license',)->getClientOriginalExtension();
     $uploadLicense=$request->file('driving_license')->store('attachment');

     $customer_document=CustomerDocument::create([
      'customer_id'=>$customer->customer_id,
      'document_ext'=>$dlextension, 
      'document_type'=>'driving_license',
      'document_url'=>$uploadLicense,
    ]);
     $slextension=$request->file('salary_slip',)->getClientOriginalExtension();
     $Uploadsalaryslip=$request->file('salary_slip')->store('attachment');

     $customer_document=CustomerDocument::create([
      'customer_id'=>$customer->customer_id,
      'document_ext'=>$slextension, 
      'document_type'=>'salary_slip',
      'document_url'=>$Uploadsalaryslip,
    ]);
     $accextension=$request->file('account_statement',)->getClientOriginalExtension();
     $uploadsaving=$request->file('account_statement')->store('attachment');

     $customer_document=CustomerDocument::create([
      'customer_id'=>$customer->customer_id,
      'document_ext'=>$accextension, 
      'document_type'=>'account_statement',
      'document_url'=>$uploadsaving,
    ]);
     $epfextension=$request->file('epf_statement',)->getClientOriginalExtension();
     $uploadepf= $request->file('epf_statement')->store('attachment');


     $customer_document=CustomerDocument::create([
      'customer_id'=>$customer->customer_id,
      'document_ext'=>$epfextension, 
      'document_type'=>'epf_statement',
      'document_url'=>$uploadepf,
    ]);


     return redirect('customer/')->with('success','Register successfully!');
   }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $orderlist= Stockorder::find($id);
      $paymentlist=CustomerPayment::all(); 
      return view('customer.details',compact('orderlist','paymentlist'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($customer_id)
    {
      $customer=Customer::find($customer_id);
      $statelist=CustomerState::all();

      return view('customer.edit', compact('customer','statelist'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
      $request->validate([
        'customer_name' => 'required|string|max:255',
        'customer_email' => 'required|string|email|max:255',
        'customer_phone'=>'digits:10',

     ]);
    Customer::where('customer_id',$request->customer_id)->update([
      'customer_name'=>$request->customer_name,
      'customer_email'=>$request->customer_email,
      'customer_phone'=>$request->customer_phone,
      'my_kad'=>$request->my_kad,
      'buss_reg'=>$request->buss_reg,
      'business_name'=>$request->business_name,
      'address'=>$request->address,
      'state'=>$request->state_name,

      ]);

      return redirect('customer.deatils');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
   /** public function destroy($customer_id)
    {
      $customer=Customer::find($customer_id);
      $customer->delete();
      return redirect('customer/');



    }*/
    public function download(Request $request ,$customer_id){

     $customer=Customer::find($customer_id);
     $doclist = CustomerDocument::where('customer_id',$customer_id)->get();
     $zip      = new ZipArchive;
     $fileName = $customer->customer_name.'_document.zip';
     if ($zip->open(public_path($fileName), ZipArchive::CREATE) === TRUE) {
      if(!empty($doclist)){
        foreach ($doclist as $key => $doclist) {
          $fullpath = storage_path('app/public/attachment'.$doclist->document_url);
          //dd($fullpath);
          $initial_name =  basename($fullpath);
          $zip->addFile($fullpath,$initial_name);
        }
        $zip->close();
      }

    }
    return response()->download(public_path($fileName));
  }

  public function paymentstore(Request $request )
  {
   $uploadloan='';
   if($request->file('letter'))
   {
     $uploadloan= $request->file('letter')->store('attachment');  
   }

   if($request->payment_type=='loan'){

    $request->validate([
      'financier' => 'required|string|max:255',
      'pic_name' => 'required',
      'pic_number'=>'required',
      'issue_date'=>'required',
      'payment_amount'=>'required',
      'validity'=>'required',

    ]);
    
    $customerdetails=CustomerPayment::create([
      'customer_id'=>$request->customer_id,
      'order_id'=>$request->order_id,
      'financier'=>$request->financier,
      'payment_type'=>$request->payment_type,
      'pic_name'=>$request->pic_name,

      'pic_number'=>$request->pic_number,
      'issue_date'=>$request->issue_date,
      'validity'=>$request->validity,
      'payment_amount'=>$request->payment_amount,
      'document_url'=>$uploadloan,
      
    ]);
 }
  elseif ($request->payment_type=='cash') {

   $request->validate([
    'transfer_via' => 'required|string|max:255',
    'payor_name' => 'required',
    'transfer_date'=>'required',
    'cash_amount'=>'required',
     
  ]);

   $customerdetails=CustomerPayment::create([ 
     'customer_id'=>$request->customer_id,
     'order_id'=>$request->order_id,
     'payment_type'=>$request->payment_type,
     'transfer_via'=>$request->transfer_via,
     'payor_name'=>$request->payor_name,
     'transfer_date'=>$request->transfer_date,
     'payment_amount'=>$request->cash_amount,
     'document_url'=>$uploadloan,

   ]);
  } 
   return redirect()->back()->with('status',"Payment successfully !");
  }

  public function paymentedit($paymenmt_id)
 {

 $payment=CustomerPayment::find($paymenmt_id);


 return view('payment.edit', compact('payment'));

 }
 public function paymentupdate(Request $request)
 { 
  $uploadloan='';
  if($request->file('letter'))
  {
   $uploadloan= $request->file('letter')->store('attachment');
  }
   if($request->payment_type=='loan'){

  $request->validate([
    'financier' => 'required|string|max:255',
    'pic_name' => 'required',
    'pic_number'=>'required',
    'issue_date'=>'required',
    'payment_amount'=>'required',
    'validity'=>'required',

  ]);

  CustomerPayment::where('payment_id',$request->payment_id)->update([

   'customer_id'=>$request->customer_id,
   'financier'=>$request->financier,
   'payment_type'=>$request->payment_type,
   'pic_name'=>$request->pic_name,
   
   'pic_number'=>$request->pic_number,
   'issue_date'=>$request->issue_date,
   'validity'=>$request->validity,
   'payment_amount'=>$request->payment_amount,
   'document_url'=>$uploadloan,

 ]);
}

elseif ($request->payment_type=='cash') {

 $request->validate([
  'transfer_via' => 'required|string|max:255',
  'payor_name' => 'required',
  'transfer_date'=>'required',
  'payment_amount'=>'required',

]);
 CustomerPayment::where('payment_id',$request->payment_id)->update([
  'customer_id'=>$request->customer_id,
  'payment_type'=>$request->payment_type,
  'transfer_via'=>$request->transfer_via,
  'payor_name'=>$request->payor_name,
  'transfer_date'=>$request->transfer_date,
  'payment_amount'=>$request->payment_amount,
  'document_url'=>$uploadloan,


]);
}


return redirect('customer/');

}

 public function paymentdownload($payment_id)
 {
  $payment=CustomerPayment::find($payment_id);
  $paymentlist=CustomerPayment::where('payment_id',$payment_id)->get();    
  if(!empty($paymentlist))
  {
   foreach ($paymentlist as $key => $pay) {
     $fullpath=($pay->document_url);
     if(Storage::exists($fullpath))
     {
      return Storage::download($fullpath);
    }
 }

}

}

public function delete($payment_id)
{
  $customer=CustomerPayment::find($payment_id);
  $customer->delete();
  return redirect('customer/');

}

public function barchart()
{
  $customerlist = Customer::where("COUNT(*) as count")
  ->whereYear('created_at', date('Y'))
  ->groupBy(\DB::raw("Month(created_at)"))
  ->pluck('count');

  return view('dash.dashboard', compact('customerlist'));
}


}


