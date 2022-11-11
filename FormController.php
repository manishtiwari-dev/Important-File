<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Form;
use Illuminate\Support\Str;
use App\Models\FormField;
use App\Models\FormData;
use ApiHelper;


class FormController extends Controller
{
    public $page = 'custom_form';
    public $pageview = 'view';
    public $pageadd = 'add';
    public $pagestatus = 'remove';
    public $pageupdate = 'update';


    //This Function is used to show the list of form
    public function index(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        $system_default=[];

        if (!ApiHelper::is_page_access($api_token, $this->page, $this->pageview)) {
            return ApiHelper::JSON_RESPONSE(false, [], 'PAGE_ACCESS_DENIED');
        }

        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage)?(int)$request->perPage: ApiHelper::perPageItem();
        $search = $request->search;
        $sortBy = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        /*Fetching plan data*/
        $form_query = Form::query();
        // dd($form_query);
        /*Checking if search data is not empty*/
        if (!empty($search))
            $form_query = $form_query
                ->where("form_name", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBy) && !empty($ASCTYPE)) {

            $form_query = $form_query->orderBy($sortBy, $ASCTYPE);
        } else {
            $form_query = $form_query->orderBy('form_id', 'ASC');
        }


        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;

        $form_count = $form_query->count();

        $form_list = $form_query->skip($skip)->take($perPage)->get();

      
          
         $form_list->system_default=$system_default;

        // if (!empty($form_list)) {
        //     $form_list->map(function ($data) {
        //         $data->status = ($data->status == "1") ? 'active' : 'deactive';
        //         return $data;
        //     });
        // }
        $res = [
            'data' => $form_list,
            'current_page' => $current_page,
            'total_records' => $form_count,
            'total_page' => ceil((int)$form_count / (int)$perPage),
            'per_page' => $perPage,
        ];

        return ApiHelper::JSON_RESPONSE(true, $res, '');
    }

    // public function validate_form_shortcode($shortcode)
    // {
    //     $shortCode_Data = Form::where('form_shortcode', $shortcode)->first();
    //     if (!empty($shortCode_Data)) {
    //         $shortcode = $shortcode . '_' . ApiHelper::generate_random_token('alpha_numeric', 3);
    //         return $this->validate_form_shortcode($shortcode);
    //     } else {
    //         return $shortcode;
    //     }
    // }

    public function validate_form_shortcode($shortcode, $i=0){
       
        
         if($i)
         $update_shortcode=$shortcode.$i;
         else
         $update_shortcode=$shortcode;
        
         $shortCode_Data=Form::where('form_shortcode',$update_shortcode)->first();
         
        if(!empty($shortCode_Data))
        {
         $i+=1;
         return $this->validate_form_shortcode($shortcode, $i);
        }
        else
        {
         return $update_shortcode;
        }
     }

    public function store(Request $request)
    {
        // Validate user page access
        $api_token = $request->api_token;
        $alldata = $request->all();
         if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
         return ApiHelper::JSON_RESPONSE(false,$request->only('attributes'),'PAGE_ACCESS_DENIED');

        // store form 
        $saveData =  $request->only(['form_name', 'form_shortcode', 'form_type', 'status']);

        if (!empty($request->input('form_shortcode'))) {
            $form_shortcode = $this->validate_form_shortcode($request->input('form_shortcode'));
            $saveData['form_shortcode'] = $form_shortcode;
            $form = Form::create($saveData);
        }

        // attach form field to form
        if ($request->has('form_field') && sizeof($request->form_field) > 0) {
            foreach ($request->form_field as $key => $formField) {

                FormField::create([
                    'form_id' => $form->form_id,
                    'field_label' => $formField['field_label'],
                    'field_name' => \Str::slug($formField['field_label'], '-'),
                    'field_class' => $formField['field_class'],
                    'field_type' => $formField['field_type'],
                    'field_values' => isset($formField['field_values']) ? $formField['field_values'] : '',
                    'sort_order'=>$formField['sort_order'],
                    'required' => $formField['required'],
                    //   'short_order' => $formField['short_order'],
                ]);
            }
        }
        return ApiHelper::JSON_RESPONSE(true, $form, 'SUCCESS_FORM_ADD');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function edit(Request $request)
    {
        $response = Form::with('form_field')->find($request->form_id);
        if(!empty($response))
        {
        $response->setRelation('form_field',$response->form_field()->orderBy('sort_order','ASC')->get());
        }
    
        // if ($response !== null) {
        //     $response->status = ($response->status == 1) ? "active" : "deactive";
        //     $response->form_name = $response->form_name;

        //     $form_field = $response->form_field;
        //     $selected_field = [];
        //     if (!empty($form_field)) {
        //         foreach ($form_field as $key => $formFieldVal) {

        //             $field_label = $formFieldVal->field_label;
        //             $field_name =  $formFieldVal->field_name;
        //             $field_type =  $formFieldVal->field_type;
        //             $field_required = ($formFieldVal->required == 1) ? "Yes" : "NO";

        //             array_push($selected_field, [
        //                 "field_label" => $field_label,
        //                 "field_name" => $field_name,
        //                 "field_type" => $field_type,
        //                 "field_required" => $field_required,
        //             ]);
        //         }
        //     }
        //     $response->selected_field = $selected_field;
        // }
        return ApiHelper::JSON_RESPONSE(true, $response, '');
    }

    //This Function is used to update the particular plan data
    public function update(Request $request)
    {

        // return ApiHelper::JSON_RESPONSE(true,$request->all(),'');

    
        // Validate user page access
        $api_token = $request->api_token;
        if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageupdate)){
            return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        }
        
        $form_id = $request->form_id;

        // store form 
        $saveData =  $request->only(['form_name', 'form_shortcode', 'form_type', 'status']);

        Form::where('form_id', $form_id)->update($saveData);

        $form = Form::find($form_id);

        // attach form field to form
        FormField::where('form_id', $form_id)->delete();

        if ($request->has('form_field') && sizeof($request->form_field) > 0) {
            foreach ($request->form_field as $key => $formField) {

                FormField::create([
                    'form_id' => $form->form_id,
                    'field_label' => $formField['field_label'],
                    'field_name' => \Str::slug($formField['field_label'], '-'),
                    'field_type' => $formField['field_type'],
                    'field_class' => $formField['field_class'],
                    'field_values' => isset($formField['field_values']) ? $formField['field_values'] : '',
                    'sort_order'=>$formField['sort_order'],
                    'required' => $formField['required'],

                ]);
            }
        }

        return ApiHelper::JSON_RESPONSE(true, $saveData, 'SUCCESS_FORM_UPDATE');
    }


   
    public function destroy(Request $request)

    {
        $api_token = $request->api_token;

           
        

        $DETAIL = Form::with('form_field')->where('form_id', $request->form_id)->first();




        $form_data=FormData::where('form_id', $DETAIL->form_id)->first();



     //  return ApiHelper::JSON_RESPONSE(true,$form_data->form_id, 'SUCCESS_FORM_DELETE');
         
          if(empty($form_data->form_id))
        {
            if (!empty($DETAIL)) $DETAIL->form_field()->delete();   // relation data delete

            $DETAIL = Form::where('form_id', $request->form_id)->delete();

        }


        else{

           

            return ApiHelper::JSON_RESPONSE(false,[], 'UNABLE_TO_FORM_DELETE');

        }
      

        if ($DETAIL) {
            return ApiHelper::JSON_RESPONSE(true, [], 'SUCCESS_FORM_DELETE');
        } else {
            return ApiHelper::JSON_RESPONSE(false, [], 'ERROR_FORM_DELETE');
        }
    }

    //This Function is used to get the change the form status
    public function changeStatus(Request $request)
    {

        $api_token = $request->api_token;
        $infoData = Form::find($request->form_id);
        $infoData->status = ($infoData->status == 0) ? 1 : 0;
        $infoData->save();
        return ApiHelper::JSON_RESPONSE(true, $infoData, 'SUCCESS_STATUS_UPDATE');

    }
}
