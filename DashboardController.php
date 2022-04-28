<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockOrder;
use App\Models\CustomerPayment;
use App\Models\Stocklist;
use App\Models\Customer;
use DB;
use Carbon\Carbon;



class DashboardController extends Controller
{
    public function dash(Request $request)
    {

     
      $search = $request->date;
           $year = $search;  
  
      $month = range(1,12);
      $filterList = [];
      $amountfilterList = [];
  
      
      foreach($month as $mon){
        if($mon > 9 )
          $filterdate = $year.'-'.$mon;
        else
          $filterdate = $year.'-0'.$mon;
          $total_count = StockOrder::orderBy('id','asc')->where('is_admin_approved','=','1')->where('order_date','like', $filterdate.'%')->count();
        // $filterList[$mon] = $total_count;
        array_push($filterList, $total_count);
        

        $total_amount = StockOrder::orderBy('id','asc')->where('is_admin_approved','=','1')->where('order_date','like', $filterdate.'%')->sum('total_amount');
      
        array_push($amountfilterList,(int) $total_amount);
      
  
      }
        
  
      
        $orderlist= Stockorder::all()->take(-5);
      

        $customerlist =CustomerPayment::all()->take(-5);
        // dd($customerlist->customerdetail->customer_name);

        $previous_week = strtotime("-1 week +1 day");

        $start_week = strtotime("last sunday midnight",$previous_week);
        $end_week = strtotime("next saturday",$start_week);

        $start_ = date("Y-m-d",$start_week);
        $end_week = date("Y-m-d",$end_week);
       
        
        $previous_month = strtotime("-1 month +1 day");

        $start_month = strtotime("last month midnight",$previous_month);
        $end_month = strtotime("next month",$start_month);

        $start_month = date("Y-m-d",$start_month);
        $end_month = date("Y-m-d",$end_month);
      

        $previous_year = strtotime("-1 year +1 day");

        $start_year = strtotime("last year midnight",$previous_year);
        $end_year = strtotime("next year",$start_year);

        $start_year = date("Y-m-d",$start_year);
        $end_year = date("Y-m-d",$end_year);

      foreach($orderlist as $key=>$order){

        $product_id = $order->product_id;
        $product_details = Stocklist::find($product_id);


        $customer_id  = $order->customer_id;
        $customer_details = Customer::find($customer_id);


        $orderlist[$key]->product = $product_details;
        $orderlist[$key]->customer = $customer_details;

      }

    // /dd($orderlist);
      return view('/dashboard',compact('orderlist','search','amountfilterList','filterList','customerlist','end_week','end_month','end_year'));




    }



    public function dashboard_search_date(Request $request){
        $search = $request->date;
        $month = $request->month;
        $orderlist = StockOrder::query();
     
       if(!empty($search)){
           $orderlist=$orderlist->where('order_date','>=', $search);
          if(!empty($month))     
          $orderlist=$orderlist->where('order_date','>=', $month);
       }
   
      $orderlist=$orderlist->paginate(5)->setPath(url('dashboard'));
      

        return view('dashboard2', compact('orderlist','search','month'));

    }
   public function chartfilter(){

   
    return view('/dashboard',compact('amountList','filterList'));

   }
}
