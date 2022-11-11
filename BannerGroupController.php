<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Banner;
use App\Models\BannerGroup;

use Illuminate\Http\Request;
use ApiHelper;


class BannerGroupController extends Controller
{

    public $page = 'banner_setting';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';

    public function index(Request $request){

       // Validate user page access
       $api_token = $request->api_token;
       if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)){
           return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
       }

        $data_list = BannerGroup::all();

        $res = [
            'data_list'=> $data_list
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }

    

   

    public function store(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        $validator = Validator::make($request->all(),[
            'group_name' => 'required',
            'group_key' => 'required',
            'banner_dimensions' => 'required',
           
        ],[
            'group_name.required'=>'GROUP_NAME_REQUIRED',
            'group_key.required'=>'GROUP_KEY_REQUIRED',
            'banner_dimensions.required'=>'BANNERS_DIMENSIONS_REQUIRED',
            
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $banner_data=$request->except(['api_token']);

        
        $data = BannerGroup::create($banner_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_BANNER_GROUP_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_BANNER_GROUP_ADD');

    }

    public function edit(Request $request)
    {
        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');
        $api_token = $request->api_token;
        
        $data_list = BannerGroup::where('banners_group_id',$request->banners_group_id)->first();
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
             'group_name' => 'required',
            'group_key' => 'required',
            'banner_dimensions' => 'required',
           
        ],[
            'group_name.required'=>'GROUP_NAME_REQUIRED',
            'group_key.required'=>'GROUP_KEY_REQUIRED',
            'banner_dimensions.required'=>'BANNERS_DIMENSIONS_REQUIRED',

        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $banner_update_data=$request->except(['api_token','banners_group_id']);
        $data = BannerGroup::where('banners_group_id', $request->banners_group_id)->update($banner_update_data);


           // return ApiHelper::JSON_RESPONSE(true,$banner_update_data['banners_image'],'');
        
        
       

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_BANNER_GROUP_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_BANNER_GROUP_UPDATE');
    }


   

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $banners_group_id = $request->banners_group_id;
        $sub_data = BannerGroup::find($banners_group_id);
        $sub_data->status = ($sub_data->status == 0 ) ? 1 : 0;         
        $sub_data->save();
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }


}
