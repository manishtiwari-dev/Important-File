how to use api

firstly create a folder than add request 
edit,update,--->base on primaryKey
key : value generate Body
firstly generate api key
than store data


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\WebsiteSetting;
use App\Models\Banner;
use App\Models\BannerGroup;

use Illuminate\Http\Request;
use ApiHelper;
use App\Models\WebsiteSettingsGroup;

use App\Models\Currency;
use App\Models\Language;
use App\Models\Country;


class BannerController extends Controller
{


    public function index(Request $request){

        // Validate user page access
        $api_token = $request->api_token;

        $data_list = BannerGroup::all();

        $res = [
            'data_list'=> $data_list
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }

     public function list(Request $request){

        
        $api_token = $request->api_token;
        $data_list = Banner::where('banners_group_id',$request->banners_group_id)->get();

        // display each image 
        if(!empty($data_list)){
            $data_list = $data_list->map(function($data){
                $data->image = ApiHelper::getFullImageUrl($data->banners_image, 'index-list');
                return $data;
            });
        }

        $res = [
            'data_list'=> $data_list
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
    

    public function create()
    {
        $group_data=WebsiteSettingsGroup::all();

    if($group_data)
        return ApiHelper::JSON_RESPONSE(true,$group_data,'');
    else
        return ApiHelper::JSON_RESPONSE(false,[],'');

        
    }


    public function store(Request $request)
    {
        $api_token = $request->api_token;
   

        $validator = Validator::make($request->all(),[
            'banners_group_id' => 'required',
            'banners_title' => 'required',
            'banners_url' => 'required',
            'banners_image' => 'required',
         
        ],[
            'banners_group_id.required'=>'BANNERS_GROUP_ID_REQUIRED',
            'banners_title.required'=>'BANNERS_TITLE_REQUIRED',
            'banners_url.required'=>'BANNERS_URL_REQUIRED',
            'banners_image.required'=>'BANNERS_IMAGE_REQUIRED',
                      
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
    
        $banner_data=$request->except(['api_token']);

         // upload image to live. current in temp
            ApiHelper::image_upload_with_crop($api_token,$banner_data['banners_image'], 4, 'banner');

        $data = Banner::create($banner_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'BANNER_CREATED');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'UNABLE_TO_BANNER_CREATED');

    }

    public function edit(Request $request)
    {
        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');
        $api_token = $request->api_token;
        
        $data_list = Banner::where('banners_id',$request->banners_id)->first();
        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

    }

    public function update(Request $request)
    { 

        $api_token = $request->api_token;

        $validator = Validator::make($request->all(),[
            'banners_group_id' => 'required',
            'banners_title' => 'required',
            'banners_url' => 'required',
            'banners_image' => 'required',
         
        ],[
            'banners_group_id.required'=>'BANNERS_GROUP_ID_REQUIRED',
            'banners_title.required'=>'BANNERS_TITLE_REQUIRED',
            'banners_url.required'=>'BANNERS_URL_REQUIRED',
            'banners_image.required'=>'BANNERS_IMAGE_REQUIRED',
                      
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
    
            $banner_update_data=$request->except(['api_token','banners_id']);
        $data = Banner::where('banners_id', $request->banners_id)->update($banner_update_data);


           // return ApiHelper::JSON_RESPONSE(true,$banner_update_data['banners_image'],'');
        
        
            ApiHelper::image_upload_with_crop($api_token,$banner_update_data['banners_image'], 4, 'banner');

         if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'BANNER_UPDATED');
         else
            return ApiHelper::JSON_RESPONSE(false,[],'UNABLE_BANNER_UPDATED');
    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
    
        $status = Banner::where('banners_id',$request->banners_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'BANNER_DELETED');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'NOT_BANNER_DELETED');
        }
    }
}

