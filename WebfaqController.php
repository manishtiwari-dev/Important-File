<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\WebFaq;
use App\Models\WebFaqGroup;



class WebfaqController extends Controller
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



    

        $data_query = WebFaqGroup::with('faq_list');
        
          /* order by sorting */
          if(!empty($sortBY) && !empty($ASCTYPE)){
            $data_query = $data_query->orderBy($sortBY,$ASCTYPE);
        }else{
            $data_query = $data_query->orderBy('sort_order','ASC');
        } 

        // $data_list = Menu::with('bannerGroup')->where('group_id',$request->group_id)->get();
        $data_list = $data_query->get();
        
        // $data_list = $data_list->map(function($data)  {

        //     $cate = $data->bannerGroup()->first();

        //     $data->parentName=Menu::where('parent_id',$data->menu_id)->get();


        //     $data->group_name = ($cate == null) ? '' : $cate->group_name;
     
            
          
        //     return $data;
        // });

        // if($request->has('group_id')){        
        //     //getting category Name
        //      $grpName=MenuGroup::where('group_id',$request->group_id )->first();
        //      $cName = !empty($grpName) ? $grpName->group_name : '';
        // }


        $res = [
            'data_list'=> $data_list,
           
        ];

        
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }
  

    public function create(Request $request)
    {
        
        $api_token = $request->api_token;
      
        $group_data=WebFaqGroup::all();
 
        $data=[
            'group_data'=>$group_data,
           
           
        ];

        if($page_data)
            return ApiHelper::JSON_RESPONSE(true,$data,'');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'');

        
    }

    public function store(Request $request)
    {
        $api_token = $request->api_token;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');




        $faq_data=$request->only(['group_id','question','answer',]);

        
        $details = WebFaq::count();

        $sort_order=$details+1;
        
        //   return ApiHelper::JSON_RESPONSE(true,   $sort_order,'SUCCESS_STATUS_UPDATE');

           $faq_data['sort_order'] =$sort_order;

        $data = WebFaq::create($faq_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_FAQ_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FAQ_ADD');

    }

    public function group_store(Request $request)
    {
        $api_token = $request->api_token;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');




        $faq_group_data=$request->only(['group_name','group_key']);

        $details = WebFaqGroup::count();

        $sort_order=$details+1;
        
        //   return ApiHelper::JSON_RESPONSE(true,   $sort_order,'SUCCESS_STATUS_UPDATE');

        $faq_group_data['sort_order'] =$sort_order;

        $data = WebFaqGroup::create($faq_group_data);

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_FAQ_GROUP_ADD');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FAQ_GROUP_ADD');

    }


    public function edit(Request $request)
    {
        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');
        $api_token = $request->api_token;
        
        $data_list = WebFaq::where('id',$request->id)->first();

        


        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

    }



    public function group_edit(Request $request)
    {
        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');
        $api_token = $request->api_token;
        
        $data_list = WebFaqGroup::where('group_id',$request->group_id)->first();

        


        return ApiHelper::JSON_RESPONSE(true,$data_list,'');

    }



    public function update(Request $request)
    { 

            // Validate user page access
            $api_token = $request->api_token;
            if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
                return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
            }


        // $validator = Validator::make($request->all(),[
        //     'group_id' => 'required',
        //     'menu_name' => 'required',
        //     'menu_type' => 'required',
         
        // ],[
        //     'group_id.required'=>'MENU_GROUP_ID_REQUIRED',
        //     'menu_name.required'=>'MENU_NAME_REQUIRED',
        //     'menu_type.required'=>'MENU_TYPE_REQUIRED',
         

        // ]);
        // if ($validator->fails())
        //     return ApiHelper::JSON_RESPONSE(false,[],$validator->messages());

       $faq_update_data=$request->only(['group_id','question','answer',]);
    //   ->max('sort_order')
   
        $details = WebFaq::count();

        $sort_order=$details+1;
        
        //   return ApiHelper::JSON_RESPONSE(true,   $sort_order,'SUCCESS_STATUS_UPDATE');

           $faq_update_data['sort_order'] =$sort_order;

        $data = WebFaq::where('id', $request->id)->update($faq_update_data);


           // return ApiHelper::JSON_RESPONSE(true,$banner_update_data['banners_image'],'');

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_FAQ_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FAQ_UPDATE');
    }


    public function group_update(Request $request)
    { 

            // Validate user page access
            $api_token = $request->api_token;
            if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
                return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
            }



       $faq_group_update_data=$request->only(['group_name','group_key']);

       $details = WebFaqGroup::count();

       $sort_order=$details+1;
       
       //   return ApiHelper::JSON_RESPONSE(true,   $sort_order,'SUCCESS_STATUS_UPDATE');

       $faq_group_update_data['sort_order'] =$sort_order;
   
        $data = WebFaqGroup::where('group_id', $request->group_id)->update($faq_group_update_data);


           // return ApiHelper::JSON_RESPONSE(true,$banner_update_data['banners_image'],'');

        if($data)
            return ApiHelper::JSON_RESPONSE(true,$data,'SUCCESS_FAQ_GROUP_UPDATE');
        else
            return ApiHelper::JSON_RESPONSE(false,[],'ERROR_FAQ_GROUP_UPDATE');
    }


 
    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token;

        if ($request->type == "faq") {
            $infoData = WebFaq::find($request->update_id);
          
        } 
         else {
            $infoData = WebFaqGroup::find($request->update_id);
        }
       
         if(!empty( $infoData))
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();


      
      
        return ApiHelper::JSON_RESPONSE(true,$infoData,'SUCCESS_STATUS_UPDATE');
    }

    public function sortOrder(Request $request)
    
    {
        $api_token = $request->api_token;

        if($request->type == "faq")
            $infoData = WebFaq::find($request->update_id);
        else
            $infoData = WebFaqGroup::find($request->update_id);
    
        $infoData->sort_order = (int)$request->sort_order;
        $res = $infoData->save();
    
   

       
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_SORT_ORDER_UPDATE');
    }    







}
