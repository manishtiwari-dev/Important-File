<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\Menu;
use App\Models\MenuGroup;
use App\Models\WebPages;
use Modules\Ecommerce\Models\Category;
use Modules\Listing\Models\BusinessCategory;

class MenuController extends Controller
{
    public $page = 'menu_setting';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';

    public function list(Request $request){


      // Validate user page access
      $api_token = $request->api_token;

      if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
          return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;



    

        $data_query = Menu::with('bannerGroup')->where('group_id',$request->group_id);
        
          /* order by sorting */
          if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('sort_order','ASC');
        } 

        // $data_list = Menu::with('bannerGroup')->where('group_id',$request->group_id)->get();
        $data_list = $data_query->get();
        
        $data_list = $data_list->map(function($data)  {

            $cate = $data->bannerGroup()->first();

            $data->parentName=Menu::where('parent_id',$data->menu_id)->get();


            $data->group_name = ($cate == null) ? '' : $cate->group_name;
     
            
          
            return $data;
        });

        if($request->has('group_id')){        
            //getting category Name
             $grpName=MenuGroup::where('group_id',$request->group_id )->first();
             $cName = !empty($grpName) ? $grpName->group_name : '';
        }


        
        if($request->has('group_id')){        
            //getting category Name
             $typeName=Menu::where('group_id',$request->group_id )->first();
             $type = !empty($typeName) ? $typeName->menu_type : '';
        }


        $res = [
            'data_list'=> $data_list,
            'group_name'=>$cName,
            'menu_type'=>$type,
        ];
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
  


    public function create(Request $request)
    {
        $services_data = $category_data = [];
        $api_token = $request->api_token;
        $industry_id = ApiHelper::get_industry_id_by_api_token($api_token);
        //return ApiHelper::JSON_RESPONSE(true,$industry_id,'');
        $module_status_ecom =  ApiHelper::check_module_status_by_industryid($industry_id, '6'); // 6= ECom
        if($module_status_ecom)
            $category_data=Category::where('status',1)->get();

        $module_status_service =  ApiHelper::check_module_status_by_industryid($industry_id, '17'); // 17= serice
        if($module_status_service)
            $services_data=BusinessCategory::all();

    
        $page_data=WebPages::where('status',1)->get();
        
        $parent_type=Menu::where('group_id',$request->group_id)->get();

        if($request->has('group_id')){        
            //getting category Name
             $menuType=Menu::where('group_id',$request->group_id )->first();
             $cName = !empty($menuType) ? $menuType->menu_type : '';
        }

       
      //  $quick_section = ModuleSection::where('module_id',$module->module_id)->where('status','completion_status',1)->where('quick_access','1')->orderBy('sort_order','ASC')->limit(4)->get();

        $data=[
            'page_data'=>$page_data,
            'category_data'=>$category_data,
            'services_data'=>$services_data,
            'parent_type'=>$parent_type,
            'menu_type'=>$cName,
        ];

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'');

        
    }

    public function store(Request $request)
    {
        $api_token = $request->api_token;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');


        $validator = Validator::make($request->all(),[
            'group_id' => 'required',
            'menu_name' => 'required',
            'menu_type' => 'required',
          

        ],[
            'group_id.required'=>'MENU_GROUP_ID_REQUIRED',
            'menu_name.required'=>'MENU_NAME_REQUIRED',
            'menu_type.required'=>'MENU_TYPE_REQUIRED',
           

        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

        $menu_data=$request->only(['group_id','menu_name','menu_type','menu_link','status','sort_order','parent_id','menu_ref_id']);

        $data = Menu::create($menu_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_MENU_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_MENU_ADD');

    }

    public function edit(Request $request)
    {
        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');
        $api_token = $request->api_token;
        
        $data_list = Menu::where('menu_id',$request->menu_id)->first();
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
            'menu_name' => 'required',
            'menu_type' => 'required',
         
        ],[
            'group_id.required'=>'MENU_GROUP_ID_REQUIRED',
            'menu_name.required'=>'MENU_NAME_REQUIRED',
            'menu_type.required'=>'MENU_TYPE_REQUIRED',
         

        ]);
        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

     $menu_update_data=$request->only(['group_id','menu_name','menu_type','menu_link','status','sort_order','parent_id','menu_ref_id']);

   
        $data = Menu::where('menu_id', $request->menu_id)->update($menu_update_data);


           // return ApiHelper::JSON_RESPONSE(true,$banner_update_data['banners_image'],'');

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_MENU_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_MENU_UPDATE');
    }


    public function destroy(Request $request)
    {
        $api_token = $request->api_token;

        $status = Menu::where('menu_id',$request->menu_id)->delete();
        if($status) {
            return ApiHelper::JSON_RESPONSE(true,[],'SUCCESS_MENU_DELETE');
        }else{
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_MENU_DELETE');
        }
    }

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token; 
        $update_id = $request->update_id;
        $sub_data = Menu::find($update_id);
        $sub_data->status = ($sub_data->status == 0 ) ? 1 : 0;         
        $sub_data->save();
        
        return ApiHelper::JSON_RESPONSE(true,$sub_data,'SUCCESS_STATUS_UPDATE');
    }

    public function sortOrder(Request $request)
    {
        $api_token = $request->api_token;
        $menu_id = $request->menu_id;
        $sort_order=$request->sort_order;
        $infoData =  Menu::find($menu_id);
        if(empty($infoData)){
            $infoData = new Menu();
            $infoData->menu_id=$menu_id;
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
