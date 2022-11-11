<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Subscriber;
use App\Models\ServiceSubsciption;
use App\Models\ServicePlan;
use App\Models\ServiceSubsHistory;
use App\Models\ServiceSubsTransaction;


use Illuminate\Http\Request;
use ApiHelper;
use App\Mail\StatusChangeMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\StatusUpdateMail;
use Carbon\Carbon;



class ServiceSubsciptionController extends Controller
{
    public $page = 'user';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update'; 


    //This Function is used to show the list of subscriptions
    public function index(Request $request)
    { 
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBy = $request->sortBy;
        $ASCTYPE = $request->orderBy;
        $customer_id=$request->customer_id;

        /*Fetching Subscription data*/ 
        $subscription_query = ServiceSubsciption::query();
        /*Checking if search data is not empty*/
        if(!empty($search))
            $subscription_query = $subscription_query
        ->where("subscription_unique_id","LIKE", "%{$search}%");

        if(!empty( $customer_id))
        $subscription_query = $subscription_query
        ->where('customer_id', $customer_id);


        /* order by sorting */
        if(!empty($sortBy) && !empty($ASCTYPE))
            $subscription_query = $subscription_query->orderBy($sortBy,$ASCTYPE);
        else
            $subscription_query = $subscription_query->orderBy('subscription_id','DESC');

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;

        $subscription_count = $subscription_query->count();

        $subscription_list = $subscription_query->skip($skip)->take($perPage)->get();
        /*Checking if subscription list is not empty*/
        if (!empty($subscription_list)) { 
            
            $subscription_list->map(function($data){
                /* Checking if subscriber name is not empty*/
                //  $sub_status = ['InActive','Active','Limited','Blocked'];
                // if(!empty($sub_status))
                //  $data->status = $sub_status[(int)$data->status];
               
                if(!empty($data->customer_details)){
                    $data->subscriber_first_name = $data->customer_details->first_name;

                    $data->subscriber_email = $data->customer_details->email;
                }
                   
                

                $transaction_details = $data->subscription_transaction()->where('payment_status', '2')->first(); 
                if($transaction_details == null){
                    $transaction_details = $data->subscription_transaction()->where('payment_status', '1')->first();
                    if($transaction_details == null) {
                        $transaction_details = $data->subscription_transaction()->where('payment_status', '0')->first();
                        if($transaction_details == null){
                            $transaction_details = $data->subscription_transaction()->where('payment_status', '3')->first();
   
                        }
                    }
                }

                $payment_st = ['Pending','Paid','Success','Failed'];
                $approve_st = ['Pending','Approved','Reject'];

                if(!empty($transaction_details)){
                    $data->payment_date = $transaction_details->created_at;
                    $data->subscriber_approval_status = $approve_st[(int)$transaction_details->approval_status];
                    $data->approval_date = $transaction_details->approved_at;
                    $data->payment_status = $payment_st[(int)$transaction_details->payment_status];

                    if($transaction_details->subscription_history_details){
                        $data->subscriber_plan_name = $transaction_details->subscription_history_details->plan_name;
                        $data->subscriber_plan_duration = $transaction_details->subscription_history_details->plan_duration;
                    }
                }
                
                /* Checking subscription status*/
                return $data;
            });
        }


        
        /*Binding data into $res variable*/
        $res = [
            'data'=>$subscription_list,
            'current_page'=>$current_page,
            'total_records'=>$subscription_count,
            'total_page'=>ceil((int)$subscription_count/(int)$perPage),
            'per_page'=>$perPage,
        ];

        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
    
    //This Function is used to show the particular subscription data
    public function edit(Request $request){

        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $subscription_id = $request->subscription_id;
        $data=ServiceSubsciption::where('subscription_id',$subscription_id)->first();
       // $data = Subscription::with('subscription_history')->where('subscription_id', $subscription_id)->first();
          return ApiHelper::JSON_RESPONSE(true,$data,'');
     
    }
   
    
    //This Function is used to get the details of subscription data
     public function details(Request $request){
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $subscription_id = $request->subscription_id;
        $data = ServiceSubsciption::with('customer_details','subscription_history','subscription_transaction')->where('subscription_id', $subscription_id)->first();

       // $data->subscriber_business = $data->subscriber_business;
        //$data->subscriber_business_info = $data->subscriber_business->business_info;
      
        if(!empty($data->subscription_history)){
            $data->subscription_history = $data->subscription_history->map(function($history){
                if($history->approval_status == 0){
                    $history->approval_status = 'Pending';
                }else{
                    $history->approval_status = 'Approved';
                }
                $history->subscription_transaction = $history->subscription_transaction->map(function($transaction){
                    if(!empty($transaction->payment_method)){
                        $transaction->payment_method_name = $transaction->payment_method->method_key;
                    }
                    $payment_st = ['Pending','Paid','Success','Failed'];
                  //  $approve_st = ['Pending','Approved','Reject'];
                    $transaction->payment_status = $payment_st[(int)$transaction->payment_status];
                 //   $transaction->approval_status = $approve_st[(int)$transaction->approval_status];
                    return $transaction;
                });

                return $history;
            });
        }else{
            $data->subscription_history = $data->subscription_history;
        }

        
        return ApiHelper::JSON_RESPONSE(true,$data,'');
    }
    
    //This Function is used to get the change the subscription status
    public function changeStatus(Request $request)
    {

         $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $subscription_id = $request->subscription_id;
        $status = $request->status;
        $sub_data = ServiceSubsciption::where('subscription_id', $subscription_id)->first();
           
            if($sub_data->status =='0'){
                $data = ServiceSubsciption::where('subscription_id', $subscription_id)->update(['status'=> '0']);
                $status = 'InActive';
            }elseif($sub_data->status =='1'){
                $data = ServiceSubsciption::where('subscription_id', $subscription_id)->update(['status'=> '1']);
                $status = 'Active';
            }elseif($sub_data->status =='2'){
                $data = ServiceSubsciption::where('subscription_id', $subscription_id)->update(['status'=> '2']);
                $status = 'Limited';
            }{
                $data = ServiceSubsciption::where('subscription_id', $subscription_id)->update(['status'=> '3']);
                $status = 'Blocked';
            }
        return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_STATUS_UPDATE');
    }

    public function approveStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $subs_txn_id = $request->subs_txn_id;
        $sub_data = ServiceSubsTransaction::find($subs_txn_id);
        $sub_data->approval_status = $request->approval_status;
        if($sub_data->approval_status==1)
        
        $sub_data->approved_at=Carbon::now();

        $sub_data->save();
        if(!empty( $sub_data))
        {
            $sub_data =ServiceSubsHistory::find($sub_data->subs_history_id); 
            $sub_data->approval_status=$request->approval_status;
            $sub_data->expired_at= Carbon::now()->addMonths($sub_data->plan_duration);
            $sub_data->save();
        


       
        //     $sub1_data =ServiceSubsciption::find($sub_data->subscription_id); 
        //     $sub1_data->subs_history_id= $sub_data->subs_history_id;
        //   //  $sub1_data->status=1;
        //     $sub1_data->approved_at=Carbon::now();
        //     $sub1_data->expired_at=  Carbon::now()->addMonths($sub_data->plan_duration);
            
        //    // $sub1_data->save();
        //     $sub_data=$sub1_data->save();
        }
    
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }


  






  

    public function history($id)
    {
       $subscription_history = ServiceSubsHistory::where('subs_history_id',$id)->first();
       if(!empty($subscription_history->subscription_transaction)){
            if($subscription_history->subscription_transaction->payment_status == 0){
                $subscription_history->payment_status = 'Pending';
            }elseif($subscription_history->subscription_transaction->payment_status == 1){
                $subscription_history->payment_status = 'Paid';
            }elseif($subscription_history->subscription_transaction->payment_status == 2){
                $subscription_history->payment_status = 'Success';
            }else{
                $subscription_history->payment_status = 'Failed';
            }
        }
        return ApiHelper::JSON_RESPONSE(true,$subscription_history,''); 
    }

   
}
