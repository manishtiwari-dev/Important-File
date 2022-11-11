<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\EmailGroup;
use App\Models\SuperEmailGroup;
use App\Models\Menu;
use App\Models\UserBusiness;


class EmailGroupController extends Controller
{
    public $page = 'email_setting';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    public function index(Request $request){

          // Validate user page access
          $api_token = $request->api_token;

          if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');



        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        $role_name = ApiHelper::get_role_from_token($api_token);

        $p_email = ApiHelper::get_parentemail_from_token($api_token);
        $userBus = UserBusiness::where('users_email', $p_email)->first();
        if(!empty($userBus))
        $userType = "subscriber";
        else
        $userType = '';

           
        if ($userType == 'subscriber') {
       
            $data_query = SuperEmailGroup::where('group_type',1);
        }
       
        else{
            $data_query = SuperEmailGroup::query();

        }


      
        
        /* order by sorting */
        if(!empty($sortBY) && !empty($ASCTYPE)){
          $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
      }else{
          $data_query = $data_query->orderBy('sort_order','ASC');
      } 

      $data_list = $data_query->get();
      
     


        if (!empty($data_list)) { 
            $data_list->map(function($data){
              
                $data->group_type = ($data->group_type == "1")?'Admin':'SuperAdmin';
                $data->templateCount = $data->template()->count();

                return $data;
            });
        }




        $res = [
            'data_list'=> $data_list
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }



    public function store(Request $request)
    {
         // Validate user page access
         $api_token = $request->api_token;

         if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
             return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

 
        $validator = Validator::make($request->all(),[
            'group_name' => 'required',
        ],[
            'group_name.required'=>'GROUP_NAME_REQUIRED',        
        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $banner_data=$request->only(['group_name','group_key','status','group_type']);

        
        $data = EmailGroup::create($banner_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_EMAIL_GROUP_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_EMAIL_GROUP_ADD');

    }

    public function edit(Request $request)
    {
        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');
        $api_token = $request->api_token;
        $data_list = EmailGroup::find($request->group_id);
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
        ],[
            'group_name.required'=>'GROUP_NAME_REQUIRED',                   
        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $EmailGroup_update_data=$request->only(['group_name','group_key','status','group_type']);

        $data = EmailGroup::where('group_id', $request->group_id)->update($EmailGroup_update_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_EMAIL_GROUP_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_EMAIL_GROUP_UPDATE');
    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;

        $status = EmailGroup::where('group_id',$request->group_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_EMAIL_GROUP_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_EMAIL_GROUP_DELETE');
        }
    }

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $group_id = $request->group_id;
        $sub_data = EmailGroup::find($group_id);
        $sub_data->status = ($sub_data->status == 0 ) ? 1 : 0;         
        $sub_data->save();
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }

    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $group_id = $request->group_id;
        $sort_order=$request->sort_order;
        $infoData =  EmailGroup::find($group_id);
        if(empty($infoData)){
            $infoData = new Menu();
            $infoData->group_id=$group_id;
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
