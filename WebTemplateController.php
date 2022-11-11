<?php

namespace App\Http\Controllers\Api;

use ApiHelper;
use App\Http\Controllers\Controller;
use App\Models\ComponentSetting;
use App\Models\Language;
use App\Models\Super\TemplateIndustry;
use App\Models\TemplateComponent;
use App\Models\Templates;
use App\Models\TemplatesSectionOptions;
use App\Models\TemplatesSections;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserBusiness;

class WebTemplateController extends Controller
{

    public function index_all(Request $request)
    {
        $api_token = $request->api_token;

        $language = $request->language;
        $role_name = ApiHelper::get_role_from_token($api_token);

        $p_email = ApiHelper::get_parentemail_from_token($api_token);
        $userBus = UserBusiness::where('users_email', $p_email)->first();
        if(!empty($userBus))
        $userType = "subscriber";
        else
        $userType = '';



        if ($userType != 'subscriber') {

            $template_list = Templates::with('sections', 'templatesIndu', 'templatesIndu.industry_details')->get();

            $template_list = $template_list->map(function ($data) use ($language) {

                $industryname = '';

                if (!empty($data->templatesIndu)) {

                    //Indu
                    //return ApiHelper::JSON_RESPONSE(true,$data->templatesIndu[0]->industry_details->industry_name,'');

                    //if(!empty($data->templatesIndup[0])){

                    //$indName = $data->templatesIndu->industry_details()->where('languages_id', ApiHelper::getLangid($language))->first();
                    //   $industryname = ($indName == null) ? '' : $indName->industry_name;

                    $industryname = $data->templatesIndu[0]->industry_details->industry_name;
                    // }

                }

                
                $data->industry_name = $industryname;
                return $data;
            });

        } else {
            $industry_id = ApiHelper::get_industry_id_by_api_token($api_token);

            $template_list = Templates::with('sections')->whereRelation('templatesIndu', 'industry_id', $industry_id)->where('status', 1)->get();

        }
        //return ApiHelper::JSON_RESPONSE(true,$role_name,'');

        if (!empty($template_list)) {
            $template_list = $template_list->map(function ($data) {

                $data->template_sections =
                TemplatesSections::all()->map(function ($tempsection) use ($data) {

                    // filter record template_id, section_id
                    $tempsection->template_section_options = $data->sections()->where(
                        ['template_id' => $data->template_id, 'section_id' => $tempsection->section_id]
                    )->get();

                    // section image show
                    $tempsection->section_list->map(function ($section_list) {
                        $section_list->image = ApiHelper::getSuperFullImageUrl($section_list->images_id, 'template-list');
                        return $section_list;
                    });

                    return $tempsection;

                });

                $data->image = ApiHelper::getSuperFullImageUrl($data->images_id, 'template-list');
                return $data;
            });

        }

        $res = [
            'template_list' => $template_list,
        ];

        return ApiHelper::JSON_RESPONSE(true, $template_list, '');

    }

    public function template_detail(Request $request)
    {

        $api_token = $request->api_token;


        $language = $request->language;
        $role_name = ApiHelper::get_role_from_token($api_token);

        $p_email = ApiHelper::get_parentemail_from_token($api_token);
        $userBus = UserBusiness::where('users_email', $p_email)->first();
        if(!empty($userBus))
        $userType = "subscriber";
        else
        $userType = '';

        if ($userType != 'subscriber') {


            $data = Templates::with('sections')->where('template_id', $request->template_id)->first();

            if (!empty($data)) {
    
                $data->template_sections =
                TemplatesSections::all()->map(function ($tempsection) use ($data) {
    
                    // filter record template_id, section_id
                    $tempsection->template_section_options = $data->sections()->where(
                        ['template_id' => $data->template_id, 'section_id' => $tempsection->section_id]
                    )->get();
    
                    // section image show
                    $tempsection->template_section_options->map(function ($section_list) {
                     //   $section_list->image = ApiHelper::getFullImageUrl($section_list->images_id, 'template-list');
                     $section_list->image = ApiHelper::getSuperFullImageUrl($section_list->images_id, 'template-list');
    
                        return $section_list;
                    });
    
                    return $tempsection;
    
                });
    
            }



        }
    else{
       
        $data = Templates::with('sections')->where('template_id', $request->template_id)->first();

        if (!empty($data)) {

            $data->template_sections =
            TemplatesSections::where('status',1)->get()->map(function ($tempsection) use ($data) {

                // filter record template_id, section_id
                $tempsection->template_section_options = $data->sections()->where(
                    ['template_id' => $data->template_id, 'section_id' => $tempsection->section_id]
                )->where('status', 1)->get();

                // section image show
                $tempsection->template_section_options->map(function ($section_list) {
                 //   $section_list->image = ApiHelper::getFullImageUrl($section_list->images_id, 'template-list');
                 $section_list->image = ApiHelper::getSuperFullImageUrl($section_list->images_id, 'template-list');

                    return $section_list;
                });

                return $tempsection;

            });

        }
    }
        return ApiHelper::JSON_RESPONSE(true, $data, '');
    }


    public function component_setting_list(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        $componentlist = TemplateComponent::select('component_name as label', 'component_id as value')->where('status', 1)->get();

        $setting_details = ComponentSetting::with('component_details')->where('template_id', $request->template_id)->get();

        $res = [
            'component_list' => $componentlist,
            'setting_details' => $setting_details,

        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    public function component_setting_store(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        $component_id = explode(",", $request->component_id);
        //validation check
        $rules = [

            'template_id' => 'required',
            'component_id' => 'required',

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        // store category
        foreach ($component_id as $key => $value) {
            $cat = ComponentSetting::updateOrCreate([
                'component_id' => $value,
                'template_id' => $request->template_id,
            ], []);

        }

        if ($cat) {
            return ApiHelper::JSON_RESPONSE(true, $cat, 'SUCCESS_COMPONENT_SETTING_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_COMPONENT_SETTING_ADD');
        }

    }

    public function create(Request $request)
    {

        $api_token = $request->api_token;
        $template_list = Templates::all();
        $section_list = TemplatesSections::all();

        $res = [
            'template_list' => $template_list,
            'section_list' => $section_list,
        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    public function template_store(Request $request)
    {
        $api_token = $request->api_token;
        $template_name = $request->template_name;
        $template_key = $request->template_key;
        $images_id = $request->images_id;

        $industry_ary = explode(',', $request->industry_id);

        $validator = Validator::make($request->all(), [
            'template_key' => 'required',
            'template_name' => 'required',

        ], [

            'template_key.required' => 'TEMPLATE_KEY_REQUIRED',
            'template_name.required' => 'TEMPLATE_NAME_REQUIRED',

        ]);

        if ($validator->fails()) {

            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());

        }

        if($images_id)
        
        ApiHelper::image_upload_with_crop($api_token, $images_id, 1, 'template','', false);

        $data = Templates::create([
            'template_name' => $template_name,
            'template_key' => $template_key,
            'images_id' => $images_id,

        ]);
        $template_data = Templates::where('template_id', $data['template_id'])->first();
        $template_industry = [];
        foreach ($industry_ary as $key => $value) {
            $template_industry = TemplateIndustry::create([
                'template_id' => $template_data->template_id,
                'industry_id' => $value,

            ]);
        }

        // $template_data=$request->except(['api_token']);

        //  // upload image to live. current in temp
        // ApiHelper::image_upload_with_crop($api_token,$template_data['images_id'], 4, 'template');

        // $data = Templates::create($template_data);

        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_WEB_TEMPLATE_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_WEB_TEMPLATE_ADD');
        }

    }

    public function template_section_store(Request $request)
    {
        $api_token = $request->api_token;

        $validator = Validator::make($request->all(), [
            'section_id' => 'required',
            'section_option_name' => 'required',
            'section_option_key' => 'required',

        ], [

            'section_id.required' => 'SECTION_ID_REQUIRED',
            'section_option_key.required' => 'SECTION_OPTION_KEY_REQUIRED',
            'section_option_name.required' => 'SECTION_OPTION_NAME_REQUIRED',

        ]);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        $template_section_data = $request->except(['api_token']);
        // upload image to live. current in temp
        ApiHelper::image_upload_with_crop($api_token, $template_section_data['images_id'], 1, 'template/section','', false);

      //  ApiHelper::image_upload_with_crop($api_token, $template_section_data['images_id'], 4, 'templateSection');

        $data = TemplatesSectionOptions::create($template_section_data);

        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_TEMPLATE_SECTION_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_TEMPLATE_SECTION_ADD');
        }

    }

    public function template_section_group_store(Request $request)
    {
        $api_token = $request->api_token;

        $validator = Validator::make($request->all(), [
            //'template_id' => 'required',
            'section_name' => 'required',
            'section_key' => 'required',

        ], [

            // 'template_id.required'=>'TEMPLATE_ID_REQUIRED',
            'section_key.required' => 'SECTION_KEY_REQUIRED',
            'section_name.required' => 'SECTION_NAME_REQUIRED',

        ]);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        $template_section_group_data = $request->except(['api_token']);

        $data = TemplatesSections::create($template_section_group_data);

        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_TEMPLATE_GROUP_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_TEMPLATE_GROUP_ADD');
        }

    }

    public function template_edit(Request $request)
    {
        $api_token = $request->api_token;

        $data_list = Templates::with('templatesIndu.industry_details')->where('template_id', $request->template_id)->first();
        //return ApiHelper::JSON_RESPONSE(true,$data_list,'');
        //  select industry
        $templatesIndu = $data_list->templatesIndu;
        $selected_template = [];
        if (!empty($templatesIndu)) {
            foreach ($templatesIndu as $key => $cat) {

                $label_res = $cat->industry_details()->where('industry_id', $cat->industry_id)->first();
                $label = ($label_res !== null) ? $label_res->industry_name : '';

                array_push($selected_template, [
                    "label" => $label,
                    "value" => $cat->industry_id,
                ]);
            }
        }
        $data_list->selected_template = $selected_template;

        $data_list->image = ApiHelper::getFullImageUrl($data_list->images_id, 'index-list');

        return ApiHelper::JSON_RESPONSE(true, $data_list, '');
    }

//  public function  template_edit(Request $request)
    //  {
    //      $api_token = $request->api_token;

//       $data_list = Templates::where('template_id',$request->template_id)->first();

//       $data_list->IndustryTypeList=Industry::select('industry_name as label','industry_id as value')->get();

//      return ApiHelper::JSON_RESPONSE(true,$data_list,'');
    //  }

    public function section_edit(Request $request)
    {
        $api_token = $request->api_token;

        $data_list = TemplatesSectionOptions::where('section_option_id', $request->section_option_id)->first();
      
        $data_list->image = ApiHelper::getFullImageUrl($data_list->images_id, 'index-list');

        return ApiHelper::JSON_RESPONSE(true, $data_list, '');
    }

    public function template_update(Request $request)
    {

        $api_token = $request->api_token;
        $template_id = $request->template_id;
        $template_name = $request->template_name;
        $template_key = $request->template_key;
        $images_id = $request->images_id;

        //$industry_ary = explode(',', $request->industry_id);
        $industry_ary = $request->industry_id;
        $validator = Validator::make($request->all(), [
            'template_key' => 'required',
            'template_name' => 'required',

        ], [
            'template_key.required' => 'TEMPLATE_KEY_REQUIRED',
            'template_name.required' => 'TEMPLATE_NAME_REQUIRED',
        ]);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());

        }

        if($images_id)
        ApiHelper::image_upload_with_crop($api_token, $images_id, 1, 'template','', false);

        $data = Templates::where('template_id', $template_id)->update([
            'template_name' => $template_name,
            'template_key' => $template_key,
            'images_id' => $images_id,

        ]);

        $industry_delete = TemplateIndustry::where('template_id', $template_id)->delete();
        $template_industry = [];
        if (!empty($industry_ary)) {
            foreach ($industry_ary as $key => $value) {
                $template_industry = TemplateIndustry::create([
                    'template_id' => $template_id,
                    'industry_id' => $value,

                ]);
            }

        }
        return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_TEMPLATE_UPDATE');
    }

    public function template_section_update(Request $request)
    {

        $api_token = $request->api_token;

        $validator = Validator::make($request->all(), [
            'template_id' => 'required',
            'section_option_name' => 'required',
            'section_option_key' => 'required',

        ], [
            'template_id.required' => 'TEMPLATE_ID_REQUIRED',
            'section_option_key.required' => 'SECTION_OPTION_KEY_REQUIRED',
            'section_option_name.required' => 'SECTION_OPTION_NAME_REQUIRED',
        ]);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        $template_update_data = $request->except(['api_token', 'section_option_id']);
        $data = TemplatesSectionOptions::where('section_option_id', $request->section_option_id)->update($template_update_data);
        ApiHelper::image_upload_with_crop($api_token, $template_update_data['images_id'], 1, 'template/section','', false);

      //  ApiHelper::image_upload_with_crop($api_token, $template_update_data['images_id'], 4, 'templateSection');

        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_TEMPLATE_SECTION_UPDATE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_TEMPLATE_SECTION_UPDATE');
        }

    }

    public function template_section_group_update(Request $request)
    {

        $api_token = $request->api_token;
        $validator = Validator::make($request->all(), [

            'section_name' => 'required',
            'section_key' => 'required',

        ], [
            'section_key.required' => 'SECTION_KEY_REQUIRED',
            'section_name.required' => 'SECTION_NAME_REQUIRED',
        ]);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        $template_update_data = $request->except(['api_token', 'section_id']);
        $data = TemplatesSections::where('section_id', $request->section_id)->update($template_update_data);

        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_SECTION_GROUP_UPDATED');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_SECTION_GROUP_UPDATED');
        }

    }

    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $setting_id = $request->setting_id;
        /*
        if(!ApiHelper::is_page_access($api_token,$this->BrandDelete)){
        return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
         */
        $status = ComponentSetting::where('setting_id', $setting_id)->delete();
        if ($status) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_COMPONENT_SETTING_DELETE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_COMPONENT_SETTING_DELETE');
        }
    }

    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token;

        if ($request->type == "template") {
            $infoData = Templates::find($request->update_id);
        } else if ($request->type == "section") {
            $infoData = TemplatesSectionOptions::find($request->update_id);
        } else {
            $infoData = TemplatesSections::find($request->update_id);
        }
       
         if(!empty( $infoData))
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();

        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_STATUS_UPDATE');

    }

}
