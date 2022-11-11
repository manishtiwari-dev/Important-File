<?php


namespace App\Http\Controllers\Api;

use App\Models\SubscriberBusiness;
use App\Models\SubscriptionTransaction;
use App\Models\BusinessInfo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\LandUser;

use ApiHelper;




class SubscriberBusinessController extends Controller
{
    public $page = 'user';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update'; 

   


    public function index_all(Request $request){
        $api_token = $request->api_token;
        // $subscriber_business_list = SubscriberBusiness::where('status',1)->orWhere('status',2)->orWhere('status',0)->get();
      //  $language = $request->language;

       

        $productItem = array();   
        
    
        $businessList=SubscriberBusiness::all();
        foreach ($businessList as $key => $buss) {

            if(!empty($buss))
            array_push($productItem, 
                [
                    "value"=>$buss->business_id, 
                    "label"=>$buss->business_name.'/'.$buss->business_unique_id.'/'.$buss->business_email.'/', 
                ]);   

        }    
        

        // $businessList=SubscriberBusiness::select('business_name as label','business_id as value')->where('status',1)->orWhere('status',2)->orWhere('status',0)->get();
          $approvalStatus= SubscriptionTransaction::all();


        $res = [
            
            'businessList'=>$productItem,
            'approvalStatus'=>$approvalStatus,

        ];

        return ApiHelper::JSON_RESPONSE(true,$res,'');
        // return ApiHelper::JSON_RESPONSE(true,$subscriber_business_list,'');
    } 

    //This Function is used to show the list of subscribers
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
        $orderBy = $request->orderBy;
        
        /*Fetching subscriber data*/ 
        $subscriber_query = SubscriberBusiness::query();
        /*Checking if search data is not empty*/
        if(!empty($search))
            $subscriber_query = $subscriber_query
                ->where("business_unique_id","LIKE", "%{$search}%")
                ->orWhere("business_name","LIKE", "%{$search}%")
                ->orWhere("business_email", "LIKE", "%{$search}%");

        /* order by sorting */
        if(!empty($sortBy) && !empty($orderBy))
            $subscriber_query = $subscriber_query->orderBy($sortBy,$orderBy);
        else
            $subscriber_query = $subscriber_query->orderBy('business_id','DESC');

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;

        $subscriber_count = $subscriber_query->count();

        $subscriber_list = $subscriber_query->skip($skip)->take($perPage)->get();

        $subscriber_list = $subscriber_list->map(function($data){
            // if($data->status=='0'){
            //     $data->status = 'Deactive'; 
            // }else{
            //     $data->status = 'Active';
            // }
            if(!empty($data->business_info->country)){
               $data->business_country = $data->business_info->country->countries_name;
            }
            if(!empty($data->subscription)){
               $data->total_subscription = $data->subscription()->count();
            }
            return $data;
        }); 
        
         /*Binding data into a variable*/
        $res = [
            'data'=>$subscriber_list,
            'current_page'=>$current_page,
            'total_records'=>$subscriber_count,
            'total_page'=>ceil((int)$subscriber_count/(int)$perPage),
            'per_page'=>$perPage,
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
    
    //This Function is used to get the details of subscriber data
    public function changeStatus(Request $request){

        $api_token = $request->api_token; 
        $business_id = $request->business_id;
        $sub_data = SubscriberBusiness::find($business_id);
        $sub_data->status = $request->status;         
        $sub_data->save();

        // // Validate user page access
        // $api_token = $request->api_token;
        // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        // }
        // $business_id = $request->business_id;
        // $status = $request->status;
        // $sub_data = SubscriberBusiness::where('business_id', $business_id)->first();
           
        //     if($sub_data->status =='0'){
        //         $data = SubscriberBusiness::where('business_id', $business_id)->update(['status'=> '0']);
        //         $status = 'Pending Approval';
        //     }elseif($sub_data->status =='1'){
        //         $data = SubscriberBusiness::where('business_id', $business_id)->update(['status'=> '1']);
        //         $status = 'Active';
        //     }elseif($sub_data->status =='2'){
        //         $data = SubscriberBusiness::where('business_id', $business_id)->update(['status'=> '2']);
        //         $status = 'Limited';
        //     }{
        //         $data = SubscriberBusiness::where('business_id', $business_id)->update(['status'=> '3']);
        //         $status = 'Blocked';
        //     }
            
            return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }

    //This Function is used to show the particular subscriber data
    public function edit(Request $request){

        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $business_id = $request->business_id;
        $data = SubscriberBusiness::with('business_info','business_info.country')->where('business_id', $business_id)->first();
        if(!empty( $data->business_info))
        $data->business_info = $data->business_info;
        
            if(!empty($data->business_info->billing_country))
            $data->selectedCountry = Country::select('countries_name as label', 'countries_id as value')->whereRaw('countries_id IN('.$data->business_info->billing_country.') ')->get();
            
        return ApiHelper::JSON_RESPONSE(true,$data,'');      
    }
    
    //This Function is used to update the particular subscriber data
    public function update(Request $request){

        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $business_id = $request->business_id;


        $billing_create = $request->only(['billing_city', 'billing_contact_name', 'billing_country', 'billing_phone', 'billing_state', 'billing_street_address', 'billing_zipcode','billing_company_name']);
        
        $business_name = $request->business_name;
        $business_email = $request->business_email;
        $validator = Validator::make($request->all(),[
            'business_name' => 'required',
            'business_email' => 'required',
        ],[
            'business_name.required'=>'BUSINESS_NAME_REQUIRED',
            'business_email.required'=>'BUSINESS_EMAIL_REQUIRED',
        ]);
        
        if ($validator->fails()){
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        }

      

        $data = SubscriberBusiness::where('business_id',$business_id)->update([
            'business_name'=>$business_name,
            'business_email'=>$business_email,
        ]);

        $business_data=SubscriberBusiness::find($business_id);
         

        $landuser= LandUser::where('id', $business_data->user_id )->update([
            'first_name'=>$business_name,
            'email'=>$business_email,
            
        ]);
       
        $billing_create['billing_default'] = '1';
        $billing_create['status'] = 1;
        $billing_create['billing_email'] = $business_email;

        if(!empty($billing_create))
        $billing_data = BusinessInfo::where('business_id',$business_id)->update($billing_create);
         
     
        if($business_data){
            return ApiHelper::JSON_RESPONSE(true,$business_data,'SUCCESS_SUBSCRIBER_BUSINESS_UPDATE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_SUBSCRIBER_BUSINESS_UPDATE');
        }
    }
    
    //This Function is used to add the subscriber data
    public function add(Request $request){

        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $passwd = config('auth.default_password');

       
        
        
        $billing_create = $request->only(['billing_city', 'billing_contact_name', 'billing_country', 'billing_phone', 'billing_state', 'billing_street_address', 'billing_zipcode','billing_company_name']);
        $business_unique_id = $request->business_unique_id; 
        $business_name = $request->business_name;
        $business_email = $request->business_email;
        $validator = Validator::make($request->all(),[
            'business_name' => 'required',
            'business_email' => 'required',
        ],[
            'business_name.required'=>'BUSINESS_NAME_REQUIRED',
            'business_email.required'=>'BUSINESS_EMAIL_REQUIRED',
        ]);
        
        if ($validator->fails()){
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
        }

        $landuser= LandUser::create([
            'first_name'=>$business_name,
            'email'=>$business_email,
            'password' => Hash::make( $passwd ),
        ]);

        $data = SubscriberBusiness::insertGetId([
            'business_unique_id'=>ApiHelper::generate_random_token('alpha_numeric',15),
            'business_name'=>$business_name,
            'business_email'=>$business_email,
            'user_id'=>$landuser->id
        ]);

        
           
         
    
        $billing_create['business_id'] = $data;
        $billing_create['billing_default'] ='1';
        $billing_create['status'] = 1;
        $billing_create['billing_email'] = $business_email;


        $billing_data = BusinessInfo::create($billing_create);
        if($data){
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_SUBSCRIBER_BUSINESS_ADD');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_SUBSCRIBER_BUSINESS_ADD');
        }
    }

}
