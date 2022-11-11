<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Banner;
use App\Models\BannerGroup;

use Illuminate\Http\Request;
use ApiHelper;


class BannerController extends Controller
{

    
    public $page = 'banner_setting';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


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

    // Validate user page access
       $api_token = $request->api_token;
       if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)){
           return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
       }
 
        $info=BannerGroup::find($request->banners_group_id);
        $data_list = Banner::where('banners_group_id',$request->banners_group_id)->orderBy('sort_order','ASC')->get();
        
        // display each image 
        if(!empty($data_list)){
            $data_list = $data_list->map(function($data){
                $data->image = ApiHelper::getFullImageUrl($data->banners_image, 'index-list');
                return $data;
            });
        }
        $res = [
            'data_list'=> $data_list,
            'info'=>$info,
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
    

    public function store(Request $request)
    {
        $api_token = $request->api_token;
          
          // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $validator = Validator::make($request->all(),[
            //'banners_group_id' => 'required',
            'banners_title' => 'required',
            'banners_url' => 'required',
            'banners_image' => 'required',

        ],[
            //'banners_group_id.required'=>'BANNERS_GROUP_ID_REQUIRED',
            'banners_title.required'=>'BANNERS_TITLE_REQUIRED',
            'banners_url.required'=>'BANNERS_URL_REQUIRED',
            'banners_image.required'=>'BANNERS_IMAGE_REQUIRED',

        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $banner_data=$request->except(['api_token']);

        if($banner_data['banners_image']!= '')
        ApiHelper::image_upload_with_crop($api_token, $banner_data['banners_image'], 1, 'banner', '', false);

         // upload image to live. current in temp
      //  ApiHelper::image_upload_with_crop($api_token,$banner_data['banners_image'], 4, 'banner');

        $data = Banner::create($banner_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_BANNER_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_BANNER_ADD');

    }

    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        
        $data_list = Banner::where('banners_id',$request->banners_id)->first();
         // display each image 
        
        $data_list->image = ApiHelper::getFullImageUrl($data_list->banners_image, 'index-list');
                
       
           
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

   
        if($banner_update_data['banners_image']!= '')
        ApiHelper::image_upload_with_crop($api_token, $banner_update_data['banners_image'], 1, 'banner', '', false);

     //   ApiHelper::image_upload_with_crop($api_token,$banner_update_data['banners_image'], 4, 'banner');

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_BANNER_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_BANNER_UPDATE');
    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;

        $status = Banner::where('banners_id',$request->banners_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_BANNER_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_BANNER_DELETE');
        }
    }

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $banners_id = $request->banners_id;
        $sub_data = Banner::find($banners_id);
        $sub_data->status = ($sub_data->status == 0 ) ? 1 : 0;         
        $sub_data->save();
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }

    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $banners_id = $request->banners_id;
        $sort_order=$request->sort_order;
        $infoData =  Banner::find($banners_id);
        if(empty($infoData)){
            $infoData = new Banner();
            $infoData->banners_id=$banners_id;
            $infoData->sort_order =$sort_order;
            $infoData->status =1;

            $infoData->save();
        
        }else{
            $infoData->sort_order = $sort_order;
            $infoData->save();
        }
       
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_SORT_ORDER_UPDATE');
    }    



}
