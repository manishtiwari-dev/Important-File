<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use ApiHelper;
use App\Models\Super\WebsiteSettingsGroup;
use App\Models\Super\WebsiteSettingGroupKey;
use App\Models\WebsiteSettingKeyValue;
use App\Models\Super\DateFormat;

use App\Models\Currency;
use App\Models\Language;
use App\Models\Country;
use App\Models\TimeZone;

class WebsiteSettingController extends Controller
{

    public $page = 'web_setting';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    public function index(Request $request){

        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
        return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
    

        $current_page = !empty($request->page)?$request->page:1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        $data_list = WebsiteSettingsGroup::with(['settings' => function ($query) {
            $query->orderBy('sort_order', 'ASC')->where('status',1);
        }])->where('status',1)->orderBy('sort_order', 'ASC')->get();

        //$data_list = WebsiteSetting::all();
        
        // attaching image url of logo etc..
        if(!empty($data_list)){
            foreach ($data_list as $key => $data) {
                $data->settings->map(function($keys){

                    $res = WebsiteSettingKeyValue::where('setting_key', $keys->setting_key)->first();
                    if($keys->option_type == 'image'){
                        $key_image = $keys->setting_key.'_image'; 
                        $keys->$key_image = !empty($res) ? ApiHelper::getFullImageUrl($res->setting_value, '') : '';
                    }
                    
                    $keys->setting_value = !empty($res) ? $res->setting_value : '';
                    return $keys;
                });
            }
        }

        $currency = Currency::selectRaw('CONCAT(currencies_code," - ", currencies_name)  as label, currencies_code as value')->where('status', 1)->get();

        $language = Language::selectRaw('CONCAT(languages_code, " - ", languages_name )  as label, languages_code as value')->where('status', 1)->get();
        $dateformat = DateFormat::selectRaw(' CONCAT(date_format, " (" ,date_example, ")")  as label , date_format as value')->get();

        $country = Country::select('countries_name as label','countries_iso_code_2 as value')->get();

        $timezone = TimeZone::select('timezone_location as label','timezone_location as value')->get();

        $optiontype= WebsiteSettingGroupKey::all();
        
        $weekary = explode(',', 'Mon,Tues,Wed,Thur,Fri,Sat,Sun');
        foreach($weekary as $key=>$val){
            $weekdays[]= array('label'=>$val,'value'=>$val);
        }
        
          
        

        $res = [
            'data_list'=>$data_list,
            'helperData' => [
                'currency'=>$currency,
                'language'=>$language,
                'country'=>$country,
                'optiontype'=>$optiontype,
                'dateformat'=>$dateformat,
                'timezone'=>$timezone,
                'weekdays'=>$weekdays,
            ],
        ];

        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }

    public function update(Request $request)
    {

         // Validate user page access
        $api_token = $request->api_token;

      
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate))
        return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        
        // if(!ApiHelper::is_page_access($api_token,'setting.notification')){
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        // }

        $all_image_key = ["website_logo","website_favicon","preloader_image","newsletter_image"];

        $saveData = $request->settingdata;

        if($saveData){
            foreach ($saveData as $key => $value) {
                if(!empty($value)){
                    if(in_array($value['setting_key'], $all_image_key)){
                        if($value['setting_value'])
                        ApiHelper::image_upload_with_crop($api_token, $value['setting_value'], 1,  $value['setting_key'], '', false);

                    //    ApiHelper::image_upload_with_crop($api_token,$value['setting_value'], 6, $value['setting_key']);
                    }
                          
                    if(isset($value['setting_value']) || (! in_array($value['setting_key'], $all_image_key))){
                        $setting_key =  $value['setting_key'];
                        $setting_val = (isset($value['setting_value']))? $value['setting_value'] : '';

                        WebsiteSettingKeyValue::updateOrCreate(
                            [
                                'setting_key'=> $setting_key
                            ],
                            ['setting_value'=> $setting_val ]
                        );
                    }
                }
            }
        }

        
        return ApiHelper::JSON_RESPONSE(true,$value,'SUCCESS_SETTING_DETAILS_UPDATE');

    }

  

}
