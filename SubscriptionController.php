<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanToIndustry;
use App\Models\SubscriptionHistory;
use App\Models\SubscriptionTransaction;
use App\Models\Industry;
use App\Models\Super\IndustryCategory;
use App\Models\SubscriberBusiness;
use App\Models\UserBusiness;

use Illuminate\Http\Request;
use ApiHelper;
use App\Mail\ApproveMail;
use Illuminate\Support\Facades\Mail;
use App\Jobs\ApproveUpdateMail;
use Carbon\Carbon;



class SubscriptionController extends Controller
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
        $business_id=$request->business_id;


        /*Fetching Subscription data*/ 
        $subscription_query = Subscription::query();
        /*Checking if search data is not empty*/

        if(!empty($search))
        {
            $subscription_query=$subscription_query->where("db_suffix","LIKE", "%{$search}%")
            ->orWhereHas('subscriber_business',function ($subscription_query)use($search)
            {
            $subscription_query->where("business_name","LIKE", "%{$search}%")->orWhere("business_email", "LIKE", "%{$search}");
            })->orWhereHas('industry_category_details',function ($subscription_query)use($search)
            {
            $subscription_query->where("category_name","LIKE", "%{$search}%");
            })->orWhereHas('industry_details',function ($subscription_query)use($search)
            {
            $subscription_query->where("industry_name","LIKE", "%{$search}%");
            });
        }
 
        
        if(!empty( $business_id))
        $subscription_query = $subscription_query
        ->where('business_id', $business_id);


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

                $data->account_type = ($data->account_type == 0)?'Test':( ($data->account_type == 1) ? 'Demo':'Live');

                $data->payment_link = ApiHelper::landingDomainUrl();

                if(!empty($data->industry_details)){
                    $data->subscriber_industry = $data->industry_details->industry_name;
                }

                if(!empty($data->industry_category_details)){
                    $data->subscriber_industry_category = $data->industry_category_details->category_name;
                }

                if(!empty($data->subscriber_business)){
                    $data->subscriber_business_name = $data->subscriber_business->business_name;

                    $data->subscriber_business_email = $data->subscriber_business->business_email;
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
        $data=Subscription::with('subscriber_business')->where('subscription_id',$subscription_id)->first();

        
        if(!empty($data->subscriber_business))
        $data->selected_business = SubscriberBusiness::select('business_name as label', 'business_id as value')->whereRaw('business_id IN('.$data->subscriber_business->business_id.') ')->get();
        

       
       // $data = Subscription::with('subscription_history')->where('subscription_id', $subscription_id)->first();
          return ApiHelper::JSON_RESPONSE(true,$data,'');


    }


    
    //This Function is used to update the particular subscription data
    public function update(Request $request){

        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        /*fetching data from api*/
        $subscription_id = $request->subscription_id;
        $expired_at = $request->expired_at;
        $status = $request->status;
        $db_suffix= $request->db_suffix;
        $domain_url =$request->domain_url;
        $business_id = $request->business_id;
     //   $plan_id = $request->plan_id;
        $industry_id = $request->industry_id;
        $industry_category_id= $request->industry_category_id;
        
        // /*validating data*/
        // $validator = Validator::make($request->all(),[
        //     'expired_at' => 'required',
        // ],[
        //     'expired_at.required' => 'EXPIRED_AT_REQUIRED',
        // ]);
        // /*if validation fails then it will show error message*/
        // if ($validator->fails()) {
        //     return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        // }
        /*updating subscription data after validation*/  
        $data = Subscription::where('subscription_id', $subscription_id)->update(['expired_at'=>$expired_at, 'status'=>$status,'domain_url'=>$domain_url,'db_suffix'=>$db_suffix,
        'business_id'=>$business_id,
        'industry_id'=>$industry_id,
        'industry_category_id'=>$industry_category_id,
        // 'plan_id'=>$plan_id
       ]);
        
        // you are returning somethign to client side.
        return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_SUBSCRIPTION_UPDATE');
    }
    
    //This Function is used to get the details of subscription data
     public function details(Request $request){
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $subscription_id = $request->subscription_id;
        $data = Subscription::with('subscriber_business','subscriber_business.business_info','industry_details','industry_category_details','subscription_history','subscriber_business.business_info.country','subscription_transaction')->where('subscription_id', $subscription_id)->orderBy('subscription_id', 'DESC')->first();
      
      

        $data->payment_link = ApiHelper::landingDomainUrl();

        if(!empty($data->industry_category_details)){
            $data->subscriber_industry_category = $data->industry_category_details->category_name;
        }
       // $data->subscriber_business = $data->subscriber_business;
        //$data->subscriber_business_info = $data->subscriber_business->business_info;
        if(!empty($data)){
            $data->billing_country = $data->countries_name;
        }
        $data->subscriber_industry = $data->industry_name;
        if($data->subscription_history){
            $data->subscription_history = $data->subscription_history->map(function($history){
                // if($history->approval_status == 0){
                //     $history->approval_status = 'Pending';
                // }else{
                //     $history->approval_status = 'Approved';
                // }
                $history->expired_at = ($history->expired_at == '') ? '---' : $history->expired_at;
                $history->canceled_at = ($history->canceled_at == '') ? '---' : $history->canceled_at;
        
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
        $sub_data = Subscription::where('subscription_id', $subscription_id)->first();
           
            if($sub_data->status =='0'){
                $data = Subscription::where('subscription_id', $subscription_id)->update(['status'=> '0']);
                $status = 'InActive';
            }elseif($sub_data->status =='1'){
                $data = Subscription::where('subscription_id', $subscription_id)->update(['status'=> '1']);
                $status = 'Active';
            }elseif($sub_data->status =='2'){
                $data = Subscription::where('subscription_id', $subscription_id)->update(['status'=> '2']);
                $status = 'Limited';
            }{
                $data = Subscription::where('subscription_id', $subscription_id)->update(['status'=> '3']);
                $status = 'Blocked';
            }
        return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_STATUS_UPDATE');
    }

    public function approveStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $subs_txn_id = $request->subs_txn_id;
        $sub_data = SubscriptionTransaction::find($subs_txn_id);
        $sub_data->approval_status = $request->approval_status;
        if($sub_data->approval_status==1)
        
        $sub_data->approved_at=Carbon::now();

        $sub_data->save();
        if(!empty( $sub_data))
        {
            $sub_data =SubscriptionHistory::find($sub_data->subs_history_id); 
            $sub_data->approval_status=$request->approval_status;
            $sub_data->expired_at= Carbon::now()->addMonths($sub_data->plan_duration);
            $sub_data->save();
        


       
            $sub1_data =Subscription::find($sub_data->subscription_id); 
            $sub1_data->subs_history_id= $sub_data->subs_history_id;
            $sub1_data->status=1;
            $sub1_data->approved_at=Carbon::now();
            $sub1_data->expired_at=  Carbon::now()->addMonths($sub_data->plan_duration);
            
           // $sub1_data->save();
            $sub_data=$sub1_data->save();
        }
    
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }


    public function SubHisapproveStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $subs_history_id = $request->subs_history_id;
        $sub_data = SubscriptionHistory::find($subs_history_id);
        $sub_data->approval_status = $request->approval_status;
        $sub_data->approved_at=Carbon::now();
      //  $sub_data->expired_at=  Carbon::now()->addMonths($sub_data->plan_duration);
        $sub_data->save();

        if(!empty($sub_data))
        {
           

            $subscription_data =Subscription::find($sub_data->subscription_id); 

            
            $subscription_data->subs_history_id= $sub_data->subs_history_id;
            $subscription_data->directory_path=ApiHelper::generate_random_token('alphabet',3).ApiHelper::generate_random_token('alpha_numeric', 5);
            $subscription_data->approved_at=Carbon::now();
            $subscription_data->status=1;
           // $sub1_data->save();
            $sub_data=$subscription_data->save();

            $business_id = $subscription_data->business_id;
            $bus_user_details = SubscriberBusiness::find($business_id);

            
            if(!empty($bus_user_details)){
                $details = [
                    'email' => $bus_user_details->business_email,
                    'message'=> 'This is a subscription mail'
                ];
            }
           
            ApproveUpdateMail::dispatch($details);


           UserBusiness::updateOrCreate(
                ['users_id' =>ApiHelper::get_user_id_from_token($api_token)],
                ['subscription_id'=>$subscription_data->subscription_id,

                 'users_email'=>$bus_user_details->business_email,
            ]
            );
            

        }
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }


  








    public function industry_plan(Request $request)
    {
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $industry_id = $request->industry_id;
        $industry_data=IndustryCategory::where('industry_id',$industry_id)->get();
     
      //  $industry_data = SubscriptionPlanToIndustry::where('industry_id',$industry_id)->get();
        //return ApiHelper::JSON_RESPONSE(true,$industry_data,'SUCCESS_PLAN_DATA_UPDATE');
        $industry_data = $industry_data->map(function($data){
            if(!empty($data->subscription_plan_details)){
                $data->subscription_plan_details = $data->subscription_plan_details;
            }
            return $data;
        }); 
        return ApiHelper::JSON_RESPONSE(true,$industry_data,'SUCCESS_PLAN_DATA_UPDATE');
    }

    public function add(Request $request)
    {
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        $business_id = $request->business_id;
        $plan_id = $request->plan_id;
        $industry_id = $request->industry_id;
        $industry_category_id= $request->industry_category_id;
        
        $validator = Validator::make($request->all(),[
            'business_id' => 'required',
            'industry_id' => 'required',
            'plan_id' => 'required',
        ],[
            'business_id.required' => 'BUSINESS_ID_REQUIRED',
            'industry_id.required' => 'INDUSTRY_ID_REQUIRED',
            'plan_id.required' => 'PLAN_ID_REQUIRED',
        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $subscription_data = Subscription::where('business_id',$business_id)->where('industry_id',$industry_id)->first();
        
        if($subscription_data == null ){
            $subscription_data = Subscription::create([
                'subscription_unique_id'=>ApiHelper::generate_random_token('alpha_numeric',10),
                'business_id'=>$business_id,
                'industry_id'=>$industry_id,
                'industry_category_id'=>$industry_category_id,
            ]);
        }

        // $plan_data = SubscriptionPlan::where('plan_id',$plan_id)->first();
        
        // $subscription_history_create = SubscriptionHistory::create([
        //     'subscription_id' => $subscription_data->subscription_id,
        //     'plan_id' => $plan_id,
        //     'plan_name' => $plan_data->plan_name,
        //     'plan_amount' => $plan_data->plan_amount,
        //     'plan_discount' => $plan_data->plan_discount,
        //     'plan_duration' => $plan_data->plan_duration,
        // ]);

        if($subscription_data){
            return ApiHelper::JSON_RESPONSE(true, $subscription_data,'SUCCESS_SUBSCRIPTION_ADD');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_SUBSCRIPTION_ADD');
        }  
    }

    public function history($id)
    {
       $subscription_history = SubscriptionHistory::where('subs_history_id',$id)->first();
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

    public function create(Request $request){
        $api_token = $request->api_token;

       
        $api_token = $request->api_token;

        $industry_list = Industry::where('status','1')->get();
        $industry_category=IndustryCategory::all();   
     
    
        
        $res = [
            'industry_category'=>$industry_category,
           
             'industry_list'=>$industry_list,
        ];

        return ApiHelper::JSON_RESPONSE(true,$res,'');

       
    }
}
