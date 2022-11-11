<?php

namespace App\Http\Controllers\Api;

use ApiHelper;
use App\Events\LoginEvent;
use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\Module;
use App\Models\ModuleSection;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserBusiness;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserController extends Controller
{
    public $page = 'user';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';

    /* login through api */
    public function login(Request $request)
    {

        $quickInc = $logged_user_relation_id = 0;
        $account_status = 1;
        $selectionItem = [];

        if ($request->has('email') && $request->has('password')) {

            if (base64_decode($request->loginType) == 'administrator') {
                $userType = "administrator";
                $db_id = 0;
                $industry_id = 0;

                ApiHelper::essential_config_regenerate($db_id);

            } else {

                $userBus = UserBusiness::where('users_email', $request->email)->first();
                $userType = "subscriber";

                if ($userBus == null) {
                    return ApiHelper::JSON_RESPONSE(false, [], 'NOT_VALID_URL');
                }


                $db_id = $userBus->subscription->db_suffix;

                ApiHelper::essential_config_regenerate($db_id);

                $subs = Subscription::where('subscription_id', $userBus->subscription_id)->first();
                if (!empty($subs)) {
                    if ($subs->account_type == 2 && $subs->status != 1) {
                        if ($subs->status == 0) {
                            return ApiHelper::JSON_RESPONSE(false, [], 'SUBSCRIPTION_STATUS_INACTIVE');
                        } else if ($subs->status == 2) {
                            $today_date = date_create(date("Y-m-d"));

                   
                        // $today_date = Carbon::now();

                         //  $expiry_date = $subs->expired_at;

                             $expiry_date = date_create("2022-09-30");
                            $day_diff = date_diff($today_date, $expiry_date);
                            if ($day_diff->format("%a") > 3) {
                                return ApiHelper::JSON_RESPONSE(false, [], 'SUBSCRIPTION_STATUS_EXPIRED');
                            }

                        }
                        $account_status = 0;
                    }

                    $industry_id = $subs->industry_id;
                } else {
                    $industry_id = 0;
                }

                $logged_user_relation_id = $userBus->subs_user_id;
            }

            $res = User::where('email', $request->email)->first();

            if ($res != null && $account_status == 1) {
                /* check account active or not */
                if ($res->status == 0) {
                    return ApiHelper::JSON_RESPONSE(false, [], 'CONTACT_ADMIN');
                }

                /* passowrd match */
                if (Hash::check($request->password, $res->password)) {

                    $loginHistorys = [
                        "user_id" => ($logged_user_relation_id == 0) ? $res->id : ($logged_user_relation_id),
                        "user_ip" => $request->ip(),
                        "location" => "",
                        "browser" => $request->header("Browser"),
                        "os" => "",
                        "longitude" => $request->header("Longitude"),
                        "latitude" => $request->header("Latitude"),
                        "city" => "",
                        "country_id" => "",
                    ];
                    $loginHistory = LoginEvent::dispatch($loginHistorys);
                    $res->dump = $db_id;

                    $response = [
                        'userType' => $userType,
                        'db_control' => $db_id,
                        'db_control' => $db_id,
                        'role' => ApiHelper::get_role_from_token($res->api_token),
                        'permission' => ApiHelper::get_permission_list($res->api_token),
                        'user' => $res,
                        'business_id' => UserBusiness::where('users_email', $request->email)->first(),
                        // 'Longitude'=>ApiHelper::user_login_history($request->header("Longitude")),
                        'ModuleSectionList' => ApiHelper::ModuleSectionList(),
                        'history' => $loginHistory,
                        'loginHistorys' => $loginHistorys,
                        'languageInfo' => ApiHelper::getLanguageAndTranslation($res->api_token),
                        'settingInfo' => ApiHelper::getSettingInfo($res->api_token),
                        'industry' => ApiHelper::get_industry_id_by_api_token($res->api_token),

                    ];

                    /*  dynamically module list behalf on role
                    if user = superadmin, show all module
                    else permission wise
                     */
                    $role = ApiHelper::get_role_from_token($res->api_token);
                    $fuctionName = ($role === 'super_admin') ? 'getModuleListForSideMenu' : 'getPermissionModuleListForSideMenu';
                    $sideMenu = $this->$fuctionName($userType, $industry_id, $res->api_token);
                    $response['module_list'] = $sideMenu['module_list'];

                    // if ($userType != "administrator") {
                        if (!empty($res->quick_access)) {
                            $myArray = explode(',', $res->quick_access);

                            if ($myArray) {
                                foreach ($myArray as $key => $value) {
                                    if ($value != '') {
                                        $quick_section = ModuleSection::where('section_id', $value)->where('status', 1)->get();
                                    }

                                    if (sizeof($quick_section) > 0) {
                                        foreach ($quick_section as $key => $value) {
                                            $selectionItem[$quickInc] = $value;
                                            $quickInc++;
                                        }
                                    }
                                }
                            }

                            $response['quick_list'] = $selectionItem;

                            //    $response['quick_list'] = $sideMenu['quick_list'];

                        } else {
                            $response['quick_list'] = [];
                        }
                    // } else {
                    //     $response['quick_list'] = $sideMenu['quick_list'];
                    // }

                    // login history event
                    return ApiHelper::JSON_RESPONSE(true, $response, 'LOGIN_SUCCESS');

                } else {
                    return ApiHelper::JSON_RESPONSE(false, [], 'WRONG_PASSWORD');
                }
            } else {
                return ApiHelper::JSON_RESPONSE(false, [], 'WRONG_EMAIL');
            }

        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'EMAIL_PASSWORD_MISSING');
        }
    }

    // all module list
    public function getModuleListForSideMenu($user_type, $industry_id, $token = '')
    {

        $returnItem = [];
        $quickInc = 0;
        $selectionItem = [];

        if ($user_type == 'administrator') {
            // $module_list = Module::where('status',1)->where('access_priviledge',0)->orderBy('sort_order','ASC')->get();
            $module_list = Module::where('status', 1)->where('access_priviledge', 0)->orWhere('access_priviledge', 1)->orderBy('sort_order', 'ASC')->get();
            foreach ($module_list as $mkey => $module) {
                $module_section = ModuleSection::where('module_id', $module->module_id)->where('status', 1)->where('parent_section_id', '0')->orderBy('sort_order', 'ASC')->get();
                foreach ($module_section as $skey => $section) {
                    $module_section[$skey]['submenu'] = ModuleSection::where('parent_section_id', $section['section_id'])->where('status', 1)->orderBy('sort_order', 'ASC')->get();
                }
                $module_list[$mkey]['menu'] = $module_section;
                // module wise quicklist view
                // $quick_section = ModuleSection::where('module_id', $module->module_id)->where('status', 1)->get();
                // if (sizeof($quick_section) > 0) {
                //     foreach ($quick_section as $key => $value) {
                //         $selectionItem[$quickInc] = $value;
                //         $quickInc++;
                //     }
                // }
            }
            $returnItem['module_list'] = $module_list;
         //   $returnItem['quick_list'] = $selectionItem;
        } else {

            $insutry = Industry::find($industry_id);
            $module_list_item = [];
            if (!empty($insutry->modules)) {

                $module_list = $insutry->modules()->orderBy('sort_order', 'ASC')->where('app_module.status', 1)->get();

                foreach ($module_list as $mkey => $module) {

                    ApiHelper::init( $token);
                    $subsDetails = ApiHelper::$subscription;

                    $modulequery  = ModuleSection::query();
                    
                    if($subsDetails['account_type'] == '2')
                    $modulequery->where('completion_status', '1')->where('status', '1');
                    else if($subsDetails->account_type == '1')
                    $modulequery->where('completion_status', '1');

                    $modulequery->where('module_id', $module->module_id)->where('parent_section_id', '0')->orderBy('sort_order', 'ASC')->get();

                    $module_section = $modulequery->get();

                    foreach ($module_section as $skey => $section) {
                        
                        $subModulequery  = ModuleSection::query();
                        if($subsDetails['account_type'] == '2')
                        $subModulequery->where('completion_status', '1')->where('status', '1');
                        else if($subsDetails->account_type == '1')
                        $subModulequery->where('completion_status', '1');

                        $module_section[$skey]['submenu'] = $subModulequery->where('parent_section_id', $section['section_id'])->orderBy('sort_order', 'ASC')->get();
                    }

                    $module_list[$mkey]['menu'] = $module_section;

                    // module wise quicklist view
                    $quick_section = ModuleSection::where('status', 1)->where('module_id', $module->module_id)->get();
                    if (sizeof($quick_section) > 0) {
                        foreach ($quick_section as $key => $value) {
                            $selectionItem[$quickInc] = $value;
                            $quickInc++;
                        }
                    }
                }
                $module_list_item = $module_list;
            }

            $returnItem['module_list'] = $module_list_item;
            $returnItem['quick_list'] = $selectionItem;

        }
        return $returnItem;
    }

    // permission wise module list
    public function getPermissionModuleListForSideMenu($user_type, $industry_id, $token = '')
    {

        $permissionList = ApiHelper::get_permission_list($token) ? ApiHelper::get_permission_list($token) : [];

        $moduleList = $this->getModuleListForSideMenu($user_type, $industry_id, $token); // get all mudole and section than filter permissionwise

        $newModule = [];

        if (!empty($moduleList['module_list'])) {

            foreach ($moduleList['module_list'] as $modkey => $module) {
                $foundMod = false;

                if (!empty($module['menu'])) {
                    foreach ($module['menu'] as $menkey => $menu) {
                        $foundMenu = false;

                        if (!empty($menu['submenu'])) {
                            foreach ($menu['submenu'] as $subkey => $submenu) {
                                // checking section exist in sub_section
                                if (isset($permissionList[$submenu['section_id']])) {
                                    $foundMenu = true;
                                    break;
                                }
                            }
                        }

                        // if found in subsection loop will stop...
                        if ($foundMenu) {
                            $foundMod = true;
                            break;
                        } else {

                            if (isset($permissionList[$menu['section_id']])) {
                                $foundMod = true;
                                break;
                            }

                        }
                    }
                }

                if ($foundMod) {
                    array_push($newModule, $module);
                }
                // push only section module who has setion

            }

        }

        $moduleList['module_list'] = $newModule; // reattach module_list
        return $moduleList;

    }

    /* get all userlist */
    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? $request->perPage : 10;
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        $user_query = User::query();

        // attaching query filter by permission(all, added,owned,both)
        $user_query = ApiHelper::attach_query_permission_filter($user_query, $api_token, $this->page, $this->pageview);

        if (!empty($search)) {
            $user_query = $user_query->where("first_name", "LIKE", "%{$search}%")->where("last_name", "LIKE", "%{$search}%")->orWhere("email", "LIKE", "%{$search}%");
        }

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $user_query = $user_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $user_query = $user_query->orderBy('id', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int) ($current_page - 1) * $perPage;

        $user_count = $user_query->count();

        $user_list = $user_query->skip($skip)->take($perPage)->get();

        $user_list = $user_list->map(function ($user) {
            $user->full_image_path = Storage::path($user->profile_photo);
            $user->role_name = ApiHelper::get_role_name_from_token($user->api_token);
            $user->name = $user->first_name . ' ' . $user->last_name;
            if ($user->status == '1') {
                $user->status = 'Active';
            } else {
                $user->status = 'Deactive';
            }
            return $user;
        });

        $res = [
            'data' => $user_list,
            'current_page' => $current_page,
            'total_records' => $user_count,
            'total_page' => ceil((int) $user_count / (int) $perPage),
            'per_page' => $perPage,
        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    /* create user and assign role  */
    public function store(Request $request)
    {

        // Validate user page access
        $api_token = $request->api_token;
        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        // validation check
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:usr_users',
            'password' => 'required|string|min:8',
            'role_name' => 'required',
        ], [
            'name.required' => 'NAME_REQUIRED',
            'name.max' => 'NAME_MAX',
            'email.required' => 'EMAIL_REQUIRED',
            'email.email' => 'EMAIL_EMAIL',
            'password.required' => 'PASSWORD_REQUIRED',
            'password.min' => 'PASSWORD_MIN',
            'role.required' => 'ROLE_NAME_REQUIRED',
        ]);

        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        try {

            DB::beginTransaction(); // begin transaction

            // store user and assign role
            $user = User::create([
                'first_name' => $request->name,
                'last_name' => '',
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'created_by' => ApiHelper::get_adminid_from_token($request->api_token),
                'api_token' => Hash::make($request->name),
            ]);
            // attach role
            $user->roles()->attach($request->role_name);

            //if user is subscriber replica of user store in userbusiness
            if ($request->has('userType') && $request->userType == 'subscriber') {
                $parent_id = ApiHelper::get_adminid_from_token($request->api_token);

                $userBusiness = UserBusiness::where('users_id', $parent_id)->first();
                $newBusinnens = $userBusiness->replicate();
                $newBusinnens->users_id = $user->id;
                $newBusinnens->users_email = $user->email;
                $newBusinnens->created_at = date('Y-m-d h:s:i');
                $newBusinnens->save();

            }

            DB::commit(); // db commit

            return ApiHelper::JSON_RESPONSE(true, $user, 'SUCCESS_USER_ADD');

        } catch (\Throwable $th) {
            \Log::error($th->getMessage());
            DB::rollback(); // db rollback
            return ApiHelper::JSON_RESPONSE(false, [], "HAVING_SOME_TECHNICAL_ISSUE");
        }

    }

    public function edit(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $userdetail = User::find($request->user_id);
        if ($userdetail != null) {

            /* $userdetail->full_image_path = asset('storage/'.$userdetail->image_path);*/
            $userdetail->full_image_path = (!empty($userdetail->profile_photo)) ? Storage::url($userdetail->profile_photo) : '';
            if (isset($userdetail->roles[0])) {
                $userdetail->role_name = $userdetail->roles[0]->name;
            } else {
                $userdetail->role_name = '';
            }

            
            return ApiHelper::JSON_RESPONSE(true, $userdetail, '');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'SOMETHING_WRONG');
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        // return ApiHelper::JSON_RESPONSE(true,$request->file('profileimg'),'Profile updated successfully !');
        // Validate user page access
        $api_token = $request->api_token;
        $user_id = $request->user_id;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        // validation check
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'contact' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'date_of_birth' => 'required|date',
            'gender' => 'required',
        ], [
            'first_name.required' => 'NAME_REQUIRED',
            'first_name.max' => 'NAME_MAX',
            'contact.required' => 'CONTACT_REQUIRED',
            'date_of_birth.required' => 'DOB_REQUIRED',
            'gender.required' => 'GENDER_REQUIRED',
        ]);

        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        $updateData = $request->only('first_name', 'last_name', 'contact', 'date_of_birth', 'gender');
        if ($request->has('userpassword') && !empty($request->userpassword)) {
            $updateData['password'] = Hash::make($request->userpassword);
        }

        if ($request->has("profileimg")) {
            if ($request->file('profileimg')) {


    // ApiHelper::image_upload_with_crop($api_token,  $updateData['profile_photo'], 1, 'profile_photo', '', false);


               $updateData['profile_photo'] = $request->file('profileimg')->store('media/profile_photo');
            }
        }
        $userInfo = User::find($user_id);

        // $autoGenPass = rand();
        // if($request->has('autoGenerate')){
        //     if($request->autoGenerate == 'on'){

        //         $updateData['password'] = Hash::make($autoGenPass);
        //         // sent auto generate password to mail
        //         // Mail::to($userInfo->email)->queue(new AutoGeneratePassword($autoGenPass));
        //         $arralist = [
        //             'email'=>$userInfo->email,
        //             'autoGenPass'=>$autoGenPass
        //         ];
        //         dispatch(new SendAutoGeneratePasswordMail($arralist));

        //     }
        // }

      






        $status = $userInfo->update($updateData);

        if ($request->has('role_name') && !empty($request->role_name)) {
            $user = User::find($user_id);
            $user->roles()->detach();
            $user->roles()->attach($request->role_name);
        }

        $userInfo = User::find($user_id);

        if ($status) {
            return ApiHelper::JSON_RESPONSE(true, $userInfo, 'SUCCESS_PROFILE_UPDATE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_PROFILE_UPDATE');
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        $api_token = $request->api_token;
        $id = $request->deleteId;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pagestatus)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $status = User::destroy($id);
        if ($status) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_USER_DELETE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_USER_DELETE');
        }
    }

    /*
    forget password
     */
    public function forgetPassword(Request $request)
    {
        if ($request->has('email')) {
            $res = User::where('email', $request->email)->first();
            if ($res != null) {
                /* check account active or not */
                if ($res->status == 'deactive') {
                    return ApiHelper::JSON_RESPONSE(false, [], 'CONTACT_ADMIN');
                }
                /* passowrd match */
                $res->password = Hash::make("password");
                $res->save();
                return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS');

            } else {
                return ApiHelper::JSON_RESPONSE(false, [], 'INVALID_EMAIL');
            }

        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'EMAIL_MISSING');
        }
    }

    /* change status */

    public function changeStatus(Request $request)
    {
        try {
            $sub_data = User::find($request->user_id);
            $sub_data->status = ($sub_data->status == 0) ? 1 : 0;
            $sub_data->save();
            return ApiHelper::JSON_RESPONSE(true, $sub_data, 'SUCCESS_STATUS_UPDATE');
        } catch (\Throwable $th) {
            return ApiHelper::JSON_RESPONSE(true, $sub_data, 'SOME_TECHNICAL_ISSUE');
        }

    }

    /* update user info */

    public function updateUser(Request $request)
    {
        //return ApiHelper::JSON_RESPONSE(true,$request->all(),'SUCCESS_USER_UPDATE');
        // Validate user page access
        $api_token = $request->api_token;
        $user_id = $request->user_id;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        // validation check
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role_name' => 'required',
        ], [
            'name.required' => 'NAME_REQUIRED',
            'name.max' => 'NAME_MAX',
            'role.required' => 'ROLE_NAME_REQUIRED',
        ]);

        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        try {

            DB::beginTransaction(); // begin transaction

            $storeData = ['first_name' => $request->name];
            $storeData = ['email' => $request->email];

            if (!empty($request->password)) {
                $storeData['password'] = Hash::make($request->password);
            }

            // store user and assign role

            $users=User::where('email',$request->email)->first();

            if(empty($users)){
                User::where('id', $user_id)->update($storeData);
            }
            else{
    
                return ApiHelper::JSON_RESPONSE(false, [], 'EMAIL_UNIQUE_REQUIRED');
    
             }
    





            //  User::where('id', $user_id)->update($storeData);

            $user = User::where('id', $user_id)->first();

            if (!empty($request->role_name)) {
                $user->roles()->detach(); // remove old role
                $user->roles()->attach($request->role_name); // attach new role
            }

            DB::commit(); // db commit

            return ApiHelper::JSON_RESPONSE(true, $user, 'SUCCESS_USER_UPDATE');

        } catch (\Throwable $th) {
            \Log::error($th->getMessage());
            DB::rollback(); // db rollback
            return ApiHelper::JSON_RESPONSE(false, [], json_encode($th->getMessage()));
        }

    }

    public function section_select(Request $request)
    {

        $api_token = $request->api_token;
        $language = $request->language;

        $usType = ($request->userType == 'administrator') ? 0 : 2;

        $utype = '1,' . $usType;

        $module_listItem = [];

        $industry_id = ApiHelper::get_industry_id_by_api_token($api_token);






        $moduleList = Module::with(['section_list' => function ($query) {$query->orderBy('sort_order', 'ASC')->where('status', 1);}])->whereRelation('module_list', 'industry_id', $industry_id)->whereRaw('access_priviledge IN(' . $utype . ')')->where('status', '1')->orderBy('sort_order', 'ASC')->get();

        
       $section_ary[] = $moduleList->map(function($data) use ($language)  {


        $cate = $data->section_list()->where('status',1)->orWhere('sort_order','ASC')->get();

        return $cate;
      });
     
    
     //$data->section_name = ($cate == null) ? '' : $cate->section_name;
     $list = array();
     foreach($section_ary[0] as $key=>$cat){
         foreach($cat as $key1=>$c_details)
             $list[] =  ['label'=>$c_details->section_name, 'value'=>$c_details->section_id];
        //$list[] = $cat;
     }
  





        // $module  = array();
        // $module[] = $moduleList->map(function ($data) use ($language,  $module ) {
           
        //     $cate = $data->section_list()->where('status', 1)->orWhere('sort_order', 'ASC')->get();
        //     foreach ($cate as $key1 => $c_details) {
        //         $list[] = ['label' => $c_details->section_name, 'value' => $c_details->section_id];
        //     }

        //     $module = ['module_name' => $data->module_name,  'sections' =>$list ];
           
        //     return $module;
        // });

       

        $module_listItem['sectionlist'] = $list;

        // $sectionlist=ModuleSection::select('section_name as label','section_id as value')->where('status',1)->get();

        // $res = [

        //     'sectionlist'=>$sectionlist,

        // ];

        return ApiHelper::JSON_RESPONSE(true, $module_listItem, '');


    }

    public function function_select(Request $request)
    {
        $api_token = $request->api_token;

        $res = User::with('section')->where('id', ApiHelper::get_adminid_from_token($api_token))->first();
        
        // if (!empty($res->quick_access)) {
        //     $myArray = explode(',', $res->quick_access);

        //     if ($myArray) {
        //         foreach ($myArray as $key => $value) {
        //             if ($value != '') {
        //                 $quick_section = ModuleSection::where('section_id', $value)->where('status', 1)->get();
        //             }

        //             if (sizeof($quick_section) > 0) {
        //                 foreach ($quick_section as $key => $value) {
        //                     $selectionItem[$quickInc] = $value;
        //                     $quickInc++;
        //                 }
        //             }
        //         }
        //     }

        //     $response['quick_list'] = $selectionItem;

        //     //    $response['quick_list'] = $sideMenu['quick_list'];

        // } else {
        //     $response['quick_list'] = [];
        // }






        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    public function account_store(Request $request)
    {

        $api_token = $request->api_token;

        $quick_access = $request->quick_access;

        $validator = Validator::make($request->all(), [
            'quick_access' => 'required',

        ], [
            'quick_access.required' => 'QUICK_ACCESS_REQUIRED',

        ]);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }

        $user = User::find(ApiHelper::get_adminid_from_token($api_token));

        $data = User::where('id', ApiHelper::get_adminid_from_token($api_token))->update([
            'id' => $user->id,
            'quick_access' => $quick_access,
            'theme_color' => $request->theme_color,

        ]);

        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, $data, 'SUCCESS_ACCOUNT_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_ACCOUNT_ADD');
        }

    }

}
