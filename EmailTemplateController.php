<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\EmailTemplates;
use App\Models\WebPages;
use App\Models\SuperEmailTemplates;
use Modules\Ecommerce\Models\Category;
use App\Models\UserBusiness;
use App\Models\SuperEmailGroup;


class EmailTemplateController extends Controller
{
    public $page = 'email_setting';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    public function list(Request $request){

            // Validate user page access
            $api_token = $request->api_token;

            if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
                return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        $role_name = ApiHelper::get_role_from_token($api_token);

        $p_email = ApiHelper::get_parentemail_from_token($api_token);
        $userBus = UserBusiness::where('users_email', $p_email)->first();
        if(!empty($userBus))
        $userType = "subscriber";
        else
        $userType = '';
         
        if ($userType != 'subscriber') {
       
            $data_list = SuperEmailTemplates::where('group_id',$request->group_id)->get();
            if(!empty($data_list)){
                $data_list = $data_list->map(function($data){
                    $data->editable = 1;

                    return $data;
                });
            }
        }
        else{
            $super_list = SuperEmailTemplates::where('group_id',$request->group_id)->get();
            if(!empty($super_list)){
                $super_list = $super_list->map(function($data){
                    $data->editable = 0;

                    return $data;
                });
            }

            $admin_list = EmailTemplates::where('group_id',$request->group_id)->get();

            if(!empty($admin_list)){
                $admin_list = $admin_list->map(function($data){
                    $data->editable = 1 ;
                    return $data;
                });
            }
            
            $merged = $admin_list->merge($super_list);

            
            $data_list = $merged->all();



        }

        if($request->has('group_id')){        
            //getting category Name
             $grpName=SuperEmailGroup::where('group_id',$request->group_id )->first();
             $cName = !empty($grpName) ? $grpName->group_name : '';
        }


      //  $data_list = EmailTemplates::where('group_id',$request->group_id)->get();

        $res = [
            'data_list'=> $data_list,
            'group_name'=>$cName,
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
  

    public function create()
    {
        $page_data=WebPages::all();
        $category_data=Category::all();
        $data=[
            'page_data'=>$page_data,
            'category_data'=>$category_data,
        ];

        if($page_data)
            return ApiHelper::JSON_RESPONSE(true,$data,'');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'');

        
    }

    public function store(Request $request)
    {
       // Validate user page access
       $api_token = $request->api_token;

       if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
           return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');


        $validator = Validator::make($request->all(),[
            'group_id' => 'required',
            'template_subject' => 'required',
            'template_content' => 'required',

        ],[
            'group_id.required'=>'GROUP_ID_REQUIRED',
            'template_subject.required'=>'SUBJECT_REQUIRED',
            'template_content.required'=>'CONTENT_REQUIRED',

        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $menu_data=$request->only(['group_id','template_content','template_subject']);

        $data = EmailTemplates::create($menu_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_TEMPLATE_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_TEMPLATE_ADD');

    }

    public function edit(Request $request)
    {
        $api_token = $request->api_token;
        $data_list = EmailTemplates::where('template_id',$request->template_id)->first();
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
            'group_id' => 'required',
            'template_subject' => 'required',
            'template_content' => 'required',

        ],[
            'group_id.required'=>'GROUP_ID_REQUIRED',
            'template_subject.required'=>'SUBJECT_REQUIRED',
            'template_content.required'=>'CONTENT_REQUIRED',

        ]);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $menu_update_data=$request->only(['group_id','template_content','template_subject']);
        $data = EmailTemplates::where('template_id', $request->template_id)->update($menu_update_data);


           // return ApiHelper::JSON_RESPONSE(true,$banner_update_data['banners_image'],'');

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_TEMPLATE_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_TEMPLATE_UPDATE');
    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;

        $status = EmailTemplates::where('template_id',$request->template_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_TEMPLATE_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_TEMPLATE_DELETE');
        }
    }

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $template_id = $request->template_id;
        $sub_data = EmailTemplates::find($template_id);
        $sub_data->status = ($sub_data->status == 0 ) ? 1 : 0;         
        $sub_data->save();
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }

 public function view(Request $request)


 {

    // Validate user page access
    $api_token = $request->api_token;


    if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
    return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        
    if($request->type == "default")
    $response =  SuperEmailTemplates::find($request->update_id);
     else
    $response =EmailTemplates::find($request->update_id);








          

    

//        $role_name = ApiHelper::get_role_from_token($api_token);

//        $p_email = ApiHelper::get_parentemail_from_token($api_token);
//        $userBus = UserBusiness::where('users_email', $p_email)->first();
//        if(!empty($userBus))
//        $userType = "subscriber";
//        else
//        $userType = '';

//        if ($userType != 'subscriber') {
       
//         $response = SuperEmailTemplates::find($request->template_id);
    
//     }
// else{
//     $super_list = SuperEmailTemplates::where('template_id',$request->template_id)->get();
//     if(!empty($super_list->template_id))
//     {
//         $response = SuperEmailTemplates::find($request->template_id);   
//     }

//     else{
//     $response  = EmailTemplates::find($request->template_id);
    
//     }
// }




       
       
        return ApiHelper::JSON_RESPONSE(true, $response, '');
    }
}
