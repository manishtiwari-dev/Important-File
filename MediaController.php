<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\Images;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use ApiHelper;



class MediaController extends Controller
{


    public function index(Request $request)
    {
           // Validate user page access
        $api_token = $request->api_token;
        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
     //   $language = $request->language;

        $data_query = Images::query();

        if (!empty($search))
            $data_query = $data_query->where("images_ori_name","LIKE", "%{$search}%");

            // /* order by sorting */
            // if (!empty($sortBY) && !empty($ASCTYPE)) {
            //     $data_query = $data_query->orderBy($sortBY, $ASCTYPE);
            // } else {
            //     $data_query = $data_query->orderBy('testimonial_id', 'ASC');
            // }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $user_count = $data_query->count();

        $data_list = $data_query->orderBy('images_id', 'desc')->skip($skip)->take($perPage)->get();


        $data_list = $data_list->map(function($data)   {

            $data->media_image = ApiHelper::getFullImageUrl($data->images_id);
          
            return $data;
        });



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
        $api_token = $request->api_token;



         $media_data=$request->only(['gallery_ids']);


        //     // upload image to live. current in temp
            
            
        //     if($media_data['images_id']!= '')  
        // ApiHelper::image_upload_with_crop($api_token, $media_data['images_id'], 1, 'upload', '', false);

        if($request->has('gallery_ids')){

            $insData = [];

            if (sizeof($request->gallery_ids)) {
                foreach ($request->gallery_ids as $key => $gallery) {
                    ApiHelper::image_upload_with_crop($api_token, $gallery, 1, $gallery,'gallery', true);

                    array_push($insData,[
                      
                        'images_id'=>$gallery
                    ]);
                }
                
            }
        }
        

        
       // $data = Images::create($media_data);

        if($insData)
            return ApiHelper::JSON_RESPONSE(true,$insData,'SUCCESS_MEDIA_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_MEDIA_ADD');

    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $id = $request->images_id;

        // if (!ApiHelper::is_page_access($api_token, $this->page, $this->pagestatus)) {
        //     return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        // }

        $status = Images::destroy($id);
        if ($status) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_MEDIA_DELETE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_MEDIA_DELETE');
        }
    }

    

 
}
