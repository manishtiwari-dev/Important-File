<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Models\Language;
use ApiHelper;
use Illuminate\Support\Facades\Storage;
use App\Models\WebPages;
use App\Models\WebPagesDescription;
use Modules\Ecommerce\Models\SeoMeta;



class PagesController extends Controller
{

    public $page = 'page_setting';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */



    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');



        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;
        $language = $request->language;

           $data_query = WebPages::with('pagedescription');


           
           if(!empty($search))
            {
                $data_query=$data_query->where("pages_slug","LIKE", "%{$search}%")
                ->orWhereHas('pagedescription',function ($data_query)use($search)
                {
                $data_query->where("pages_title","LIKE", "%{$search}%");
                });
            }

          

        // if (!empty($search))
        //     $data_query = $data_query->where("pages_slug","LIKE", "%{$search}%")->orWhere("pages_title","LIKE","%{$search}%");
            

            /* order by sorting */
            if (!empty($sortBY) && !empty($ASCTYPE)) {
                $data_query = $data_query->orderBy($sortBY, $ASCTYPE);
            } else {
                $data_query = $data_query->orderBy('pages_id', 'ASC');
            }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $user_count = $data_query->count();

        $data_list = $data_query->skip($skip)->take($perPage)->get();

        $data_list = $data_list->map(function ($data) use ($language) {

            $pagese = $data->pagedescription()->where('language_id', ApiHelper::getLangid($language))->first();

            $data->pages_title = ($pagese == null) ? '' : $pagese->pages_title;
            $data->pages_content = ($pagese == null) ? '' : $pagese->pages_content;
            $data->status = ($data->status == 1) ? "active" : "deactive";

            // getting sub pagesegory
            /*
            $sub_pagesegory = pagesegory::where('parent_id',$data->pagesegories_id)->get();
            if(sizeof($sub_pagesegory) >  0){
                $sub_pagesegory = $sub_pagesegory->map(function($sub) use ($language) {

                    $subpages = $sub->pagesegorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
                    $sub->pagesegory_name = ($subpages == null) ? '' : $subpages->pagesegories_name;
                    $sub->status = ($sub->status == 1) ? "active":"deactive"; 
                    $sub->pagesegories_image = ApiHelper::getFullImageUrl($sub->pagesegories_image);


                        // getting sub sub pagesegory
                        $sub_sub_pagesegory = pagesegory::where('parent_id',$sub->pagesegories_id)->get();
                        if(sizeof($sub_sub_pagesegory) >  0){
                            $sub_sub_pagesegory = $sub_sub_pagesegory->map(function($sub) use ($language) {
                                $subpages = $sub->pagesegorydescription()->where('languages_id', ApiHelper::getLangid($language))->first();
                                $sub->pagesegory_name = ($subpages == null) ? '' : $subpages->pagesegories_name;
                                $sub->status = ($sub->status == 1) ? "active":"deactive"; 
                                $sub->pagesegories_image = ApiHelper::getFullImageUrl($sub->pagesegories_image);
                                return $sub;
                            });
                        }
                        $sub->sub_sub_pagesegory = $sub_sub_pagesegory;


                    return $sub;
                });
            }
            $data->sub_pagesegory = $sub_pagesegory;
            */

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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $language = $request->language;
        $api_token = $request->api_token;
        $res = [];

             


        $res['language'] = ApiHelper::allSupportLang();
       
        
        return ApiHelper::JSON_RESPONSE(true,$res,'');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;

        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');

        //validation check 

        $rules = [
            'sort_order' => 'required',
            //  'pages_content'=>'required',
            //  'pages_title'=>'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiHelper::JSON_RESPONSE(false, [], $validator->messages());
        }




        // store pagesegory 

        // $saveData['parent_id'] = ($request->pagesegory_type == 'parent') ? $request->main_pagesegory : $request->sub_pagesegory;

        $saveData = $request->only(['sort_order','status']);
        $saveData['pages_slug'] = "";



        $pages = WebPages::create($saveData);

        // store pages details
        foreach ( ApiHelper::allSupportLang() as $key => $value) {

            $pages_title = "pages_title_" . $value->languages_id;
            $pages_content = "pages_content_" . $value->languages_id;
            $seometa_title = "seometa_title_" . $value->languages_id;
            $seometa_desc = "seometa_desc_" . $value->languages_id;



            if ($value->languages_code == 'en') {

                $page_data = WebPages::find($pages->pages_id);
                $page_data->pages_slug = Str::slug($request->$pages_title);
                $page_data->save();
            }

             $Postdesc= WebPagesDescription::create([
                'pages_id' => $pages->pages_id,
                'pages_title' => $request->$pages_title,
                'pages_content' => $request->$pages_content,
                'language_id' => $value->languages_id,
            ]);

             if(!empty($request->$pages_title) || !empty($request->$pages_content)){
                SeoMeta::create([
                    'page_type'=>3,
                    'reference_id'=>$Postdesc->page_description_id,
                    'language_id'=>$value->languages_id,
                    'seometa_title'=>$request->$seometa_title,
                    'seometa_desc'=>$request->$seometa_desc, 
                ]);
            }
        }
        
        

        if ($pages) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_PAGE_ADD');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_PAGE_ADD');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $response = WebPages::with('pagedescription','pagedescription.seo')->find($request->pages_id);
        if(!empty($response))
        {
            if(!empty($response->pagedescription)){
                $response->pagedescription->map(function($description){

                    $seoInfo = SeoMeta::select('seometa_title', 'seometa_desc')->where([
                        'page_type'=>3,
                        'reference_id'=>$description->page_description_id

                    ])->first();

                    $description->seo = $seoInfo; 
                 
                    return $description;   

                });
            }

        }  

        return ApiHelper::JSON_RESPONSE(true, $response, '');
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
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }

        $pages_id = $request->pages_id;

        // store pages
        $saveData = $request->only(['sort_order','status']);


        $pages = WebPages::where('pages_id', $pages_id)->update($saveData);

        // store page description

        // detach info
        WebPagesDescription::where('pages_id', $pages_id)->delete();

        foreach (ApiHelper::allSupportLang() as $key => $value) {

            $pages_title = "pages_title_" . $value->languages_id;
            $pages_content = "pages_content_" . $value->languages_id;
            $seometa_title = "seometa_title_" . $value->languages_id;
            $seometa_desc = "seometa_desc_" . $value->languages_id;

        //    $pages_content = "seometa_title_" . $value->languages_id;
          
            if ($value->languages_code == 'en') {
                $page_data = WebPages::find($pages_id);
                $page_data->pages_slug = Str::slug($request->$pages_title);
                $page_data->save();
            }

            $Pagedesc=WebPagesDescription::create([
                'pages_id' => $pages_id,
                'pages_title' => $request->$pages_title,
                'pages_content' => $request->$pages_content,
                'language_id' => $value->languages_id,
            ]);


            if(!empty($request->$pages_title) || !empty($request->$pages_content)){
                // create new
                SeoMeta::updateOrCreate(['page_type'=>3,
                        'reference_id'=>$Pagedesc->page_description_id,
                        'language_id'=>$value->languages_id ,
                        ],[
                            'seometa_title'=>$request->$seometa_title,
                            'seometa_desc'=>$request->$seometa_desc, 
                    ]);
            }

        }
        if ($pages) {
            return ApiHelper::JSON_RESPONSE(true, $saveData, 'SUCCESS_PAGES_UPDATE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_PAGES_UPDATE');
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
        $id = $request->pages_id;

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pagestatus)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $status = WebPages::destroy($id);
        $status2 = WebPagesDescription::destroy($id);
        if ($status) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_PAGE_DELETE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_PAGE_DELETE');
        }
    }


    public function changeStatus(Request $request)
    {
        $api_token = $request->api_token;
        $infoData = WebPages::find($request->pages_id);
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_STATUS_UPDATE');
    }


    public function view(Request $request)
    {
        $response = WebPages::with('pagedescription','pagedescription.seo')->find($request->pages_id);
       
        return ApiHelper::JSON_RESPONSE(true, $response, '');
    }
}
