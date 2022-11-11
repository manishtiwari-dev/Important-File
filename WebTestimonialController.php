<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\WebTestimonial;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use ApiHelper;



class WebTestimonialController extends Controller
{

    public $page = 'web_testimonial';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';

    public function index(Request $request)
    {
           // Validate user page access
        $api_token = $request->api_token;
        
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $language = $request->language;

        $data_query = WebTestimonial::query();

        if (!empty($search))
            $data_query = $data_query->where("testimonial_title","LIKE", "%{$search}%");

            /* order by sorting */
            if (!empty($sortBY) && !empty($ASCTYPE)) {
                $data_query = $data_query->orderBy($sortBY, $ASCTYPE);
            } else {
                $data_query = $data_query->orderBy('testimonial_id', 'ASC');
            }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $user_count = $data_query->count();

        $data_list = $data_query->skip($skip)->take($perPage)->get();


        $res = [
            'data' => $data_list,
            'current_page' => $current_page,
            'total_records' => $user_count,
            'total_page' => ceil((int)$user_count / (int)$perPage),
            'per_page' => $perPage
        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');

    
  
    }

     public function store(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

            
        $validator = Validator::make($request->all(),[
            'customer_name' => 'required',
            'company_name' => 'required',
            'testimonial_title'=>'required',
            'testimonial_text'=>'required',
        
        ],[
            'customer_name.required'=>'CUSTOMER_NAME_REQUIRED',
            'company_name.required'=>'COMPANY_NAME_REQUIRED',  
            'testimonial_title.required'=>'TESTIMONIAL_TITLE_REQUIRED',
            'testimonial_text.required'=>'TESTIMONIAL_TEXT_REQUIRED',            
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $banner_data=$request->only('customer_name','company_name','testimonial_title','testimonial_text','status');

        
        $data = WebTestimonial::create($banner_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_TESTIMONIAL_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_TESTIMONIAL_ADD');

    }

    public function edit(Request $request)
    {
        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');
        $api_token = $request->api_token;
        
        $data_list = WebTestimonial::where('testimonial_id',$request->testimonial_id)->first();
        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

    }

    public function update(Request $request)
    { 

         // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $validator = Validator::make($request->all(),[
            'customer_name' => 'required',
            'company_name' => 'required',
            'testimonial_title'=>'required',
            'testimonial_text'=>'required',
        
        ],[
            'customer_name.required'=>'CUSTOMER_NAME_REQUIRED',
            'company_name.required'=>'COMPANY_NAME_REQUIRED',  
            'testimonial_title.required'=>'TESTIMONIAL_TITLE_REQUIRED',
            'testimonial_text.required'=>'TESTIMONIAL_TEXT_REQUIRED',            
        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $menugroup_update_data=$request->only('customer_name','company_name','testimonial_title','testimonial_text','status');

        $data = WebTestimonial::where('testimonial_id', $request->testimonial_id)->update($menugroup_update_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_TESTIMONIAL_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_TESTIMONIAL_UPDATE');
    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;

        $status = WebTestimonial::where('testimonial_id',$request->testimonial_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_TESTIMONIAL_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_TESTIMONIAL_DELETE');
        }
    }
   
   
    

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $testimonial_id = $request->testimonial_id;
        $infodata=WebTestimonial::find($testimonial_id);
        $infodata->status = ($infodata->status == 0 ) ? 1 : 0;         
        $infodata->save();
      
        return ApiHelper::JSON_RESPONSE(true,$infodata,'SUCCESS_STATUS_UPDATE');
    }


}
