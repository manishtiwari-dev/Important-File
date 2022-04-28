<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BusinessInfo;
use App\Models\Countries;
use App\Models\States;
use App\Models\SubscriptionTransaction;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $planlist=Plan::all();
        //dd($planlist);

        return view('account.plan',compact('planlist'));
    }


    public function billing()
    {
       
        $country=Countries::all();
          $state=States::all();
        return view('subscription.billing',compact('country','state'));
    }
     

    public function Update(Request $request)
    {
       $request->validate([
         'address'=>'required',
         'city'=>'required',
         'country'=>'required',
         'pincode'=>'required',
         'phone'=>'required',
         'gst'=>'required',
       ]);

       $user = BusinessInfo::updateOrCreate(['business_id'   => Auth::user()->business_id],
        ['billing_contact_name' => request()->name,
        'billing_email' => request()->email,
        'billing_street_address' => request()->address,
        'billing_city' => request()->city,
        'billing_state' => request()->billing_state,
        'billing_country' => request()->country,
        'billing_zipcode' => request()->pincode,
        'billing_phone' => request()->phone,
        'billing_gst' => request()->gst,
        'status' => 1]
       );

       return redirect()->back()->with('message', 'Successfully!');
    }

    public function getState(Request $request)
    {
          $data = States::select('state_name','state_id')->where("countries_id",$request->countries_id)->get();
         return response()->json($data);
    }


     public function invoice()
    {
       $invoicelist=SubscriptionTransaction::all();
        return view('subscription.invoice',compact('invoicelist'));
    }



      public function paymentMethod(Request $request)
    {
       
        $paymentlist=PaymentMethod::all();
        return view('subscription.paymentMethod',compact('paymentlist'));
    }
}
