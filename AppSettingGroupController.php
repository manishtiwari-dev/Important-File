<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\AppSettingsGroup;

class AppSettingGroupController extends Controller
{



    public function index(Request $request){

        // Validate user page access
        $api_token = $request->api_token;

        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        $data_query = AppSettingsGroup::select('group_id', 'group_name', 'status','sort_order');

        // search
        if(!empty($search))
            $data_query = $data_query->where("group_name","LIKE", "%{$search}%");

        /* order by sorting */
        if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('group_id','ASC');
        } 

        $skip = ($current_page == 1)?0:(int)($current_page-1)*$perPage;     // apply page logic

        $data_count = $data_query->count(); // get total count

        $data_list = $data_query->skip($skip)->take($perPage)->get(); 
        
        $data_list = $data_query->get();


        $res = [
            'data'=>$data_list,
            'current_page'=>$current_page,
            'total_records'=>$data_count,
            'total_page'=>ceil((int)$data_count/(int)$perPage),
            'per_page'=>$perPage,
        ];

        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
    

    public function store(Request $request)
    {
        $api_token = $request->api_token;
   

        $validator = Validator::make($request->all(),[
            'group_name' => 'required',
        ],[
            'group_name.required'=>'GROUP_NAME_REQUIRED',           
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());
    
        $setting_group_data=$request->except(['api_token']);
        $data = AppSettingsGroup::create($setting_group_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_SETTING_GROUP_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_SETTING_GROUP_ADD');

    }

    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        
        $data_list = AppSettingsGroup::where('group_id',$request->group_id)->first();
        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

    }

    public function update(Request $request)
    {
        $api_token = $request->api_token;
        

        
        $validator = Validator::make($request->all(),[
            'group_name' => 'required',
            'status'=>'required',
        ],[
            'group_name.required'=>'GROUP_NAME_REQUIRED',           
            'status.required'=>'STATUS_REQUIRED',           
        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $setting_update_data=$request->except(['api_token','group_id']);
        $data = AppSettingsGroup::where('group_id', $request->group_id)->update($setting_update_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_SETTING_GROUP_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_SETTING_GROUP_UPDATE');
    }


    
    public function sort_order_update(Request $request)
    {
        $api_token = $request->api_token;
        

        
        // $validator = Validator::make($request->all(),[
        //     'group_name' => 'required',
        //     'status'=>'required',
        // ],[
        //     'group_name.required'=>'GROUP_NAME_REQUIRED',           
        //     'status.required'=>'STATUS_REQUIRED',           
        // ]);

        // if ($validator->fails())
        //     return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $setting_update_data=$request->only(['sort_order']);
        $data = AppSettingsGroup::where('group_id', $request->group_id)->update($setting_update_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_SORT_ORDER_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_SORT_ORDER_UPDATE');
    }
    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
    
        $status = AppSettingsGroup::where('group_id',$request->group_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_SETTING_GROUP_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_SETTING_GROUP_DELETE');
        }
    }

      public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $group_id = $request->group_id;
        $sub_data = AppSettingsGroup::find($group_id);
        $sub_data->status = $request->status;
        $sub_data->save();
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }
    
}
