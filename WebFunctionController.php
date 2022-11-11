<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\WebFunctions;
use App\Models\WebFunctionsSetting;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use ApiHelper;



class WebFunctionController extends Controller
{
    public $page = 'function_settings';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    public function index(Request $request)
    {
     
            // Validate user page access
            $api_token = $request->api_token;

            if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
                return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        
        /*Fetching function data*/ 
        $res=WebFunctions::with('settings_details')->get();
        return ApiHelper::JSON_RESPONSE(true,$res,'');
  
    }

    
   
   
    

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $function_id = $request->function_id;
       $webfunction= WebFunctionsSetting::where('function_id',$function_id)->first();
       if(!empty($webfunction))
       {
           $webfunction->status = ($webfunction->status == 'enabled') ? 'disabled' : 'enabled';         
           $webfunction->save();
        
       }
       else{
           $webfunction = new WebFunctionsSetting();
            $webfunction->function_id=$function_id;
            $webfunction->status = 'enabled';         
            $webfunction->save();
       }
        
        return ApiHelper::JSON_RESPONSE(true,$webfunction,'SUCCESS_STATUS_UPDATE');
    }


}
