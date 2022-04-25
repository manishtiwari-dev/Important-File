<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use ApiHelper;
use Modules\CRM\Models\CRMIndustry;
use Modules\CRM\Models\CRMLeadSource;
use Modules\CRM\Models\CRMLeadStatus;
use App\Models\Country;
use Modules\CRM\Models\CRMAgent;
use Modules\CRM\Models\CRMLead;
use Modules\CRM\Models\CRMLeadContact;
use Modules\CRM\Models\CRMLeadFollowUp;
use Modules\CRM\Models\CRMLeadFollowUpHistory;
use Modules\CRM\Models\CRMLeadSocialLink;
use Storage;

class CRMLeadController extends Controller
{

    public $page = 'lead';
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

        // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageview))
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');



        $current_page = !empty($request->page) ? $request->page : 1;
        $perPage = !empty($request->perPage) ? $request->perPage : 10;
        $search = $request->search;
        $sortBY = $request->sortBy;
        $ASCTYPE = $request->orderBY;

        $data_query = CRMLead::query();

        if (!empty($search))
            $data_query = $data_query->where("company_name", "LIKE", "%{$search}%");

        /* order by sorting */
        if (!empty($sortBY) && !empty($ASCTYPE)) {
            $data_query = $data_query->orderBy($sortBY, $ASCTYPE);
        } else {
            $data_query = $data_query->orderBy('lead_id', 'ASC');
        }

        $skip = ($current_page == 1) ? 0 : (int)($current_page - 1) * $perPage;
        $user_count = $data_query->count();

        $data_list = $data_query->skip($skip)->take($perPage)->get();

        $data_list = $data_list->map(function ($data) {
            
            $data->folloup_date = !empty($data->crm_lead_followup) 
                ? date("Y-m-d", strtotime($data->crm_lead_followup->next_followup )) 
                : "-";
            
            $status_id = !empty($data->crm_lead_followup) ? $data->crm_lead_followup->followup_status : 0;
            
            $status_data = CRMLeadStatus::find($status_id);
            $data->status = !empty($status_data) ? $status_data->status_name : "";

            $status_data = CRMIndustry::find($data->industry_id);
            $data->industry_id = !empty($status_data) ? $status_data->industry_name : "";

            $data->priority = ($data->priority == 1) ? "Low" : (($data->priority == 2) ? "Medium" : "High");

            $data->folllow_up = ($data->folllow_up == 1) ? "Yes" : "No";


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

    public function create()
    {
        $industrydata = CRMIndustry::all();
        $leadsource = CRMLeadSource::all();
        $leadstatus = CRMLeadStatus::all();
        $countrydata = Country::all();
        $crmagentdata = CRMAgent::all();
        $res = [
            'industrydata' => $industrydata,
            'leadsource' => $leadsource,
            'leadstatus' => $leadstatus,
            'countrydata' => $countrydata,
            'crmagentdata' => $crmagentdata,
        ];
        return ApiHelper::JSON_RESPONSE(true, $res, '');
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

        // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');



        //validation check 
        $rules = [
            'source_id' => 'required',
            'industry_id' => 'required',
            //'status_id' => 'required',
            'priority' => 'required',
            'company_name' => 'required|string',
            'website' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zipcode' => 'required',
            'countries_id' => 'required',
            'phone' => 'required',
            'folllow_up' => 'required',
            //'followup_schedule' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, '', $validator->messages());

        $data1 = $request->except(['api_token', 'contact', 'socialLink']);
        //$data2=$request->only('contact_name','contact_email');

        $prdopval1 = CRMLead::create($data1);
        $leadid = $prdopval1->lead_id;

      $followupdata= CRMLeadFollowUp::create([
            'followup_status'=>$request->followup_status,
            'next_followup'=>$request->next_followup,
            'lead_id'=>$leadid,
        ]);


        $contactdata = $request->contact;
        foreach ($contactdata as $key => $value) {
            $prdopval2 = CRMLeadContact::create([
                'lead_id' => $leadid,
                'contact_name' => $value['contact_name'],
                'contact_email' => $value['contact_email'],
            ]);
        }
        $socialLinkdata = $request->socialLink;
        foreach ($socialLinkdata as $key => $value) {
            $prdopval3 = CRMLeadSocialLink::create([
                'lead_id' => $leadid,
                'social_type' => $value['social_type'],
                'social_link' => $value['social_link'],
            ]);
        }

        $data = [
            'leaddata' => $prdopval1,
            'leadcontactdata' => $prdopval2,
            'sociallinkdata' => $prdopval3,
            'followupdata'=>$followupdata,
        ];

        if ($data) {
            return ApiHelper::JSON_RESPONSE(true, $data, 'LEAD_CREATED');
        } else {
            return ApiHelper::JSON_RESPONSE(false, '', 'SOME_ISSUE');
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $response = CRMLead::find($request->lead_id);
        if (!empty($response)) {
            $response->contact = $response->crm_lead_contact()->select('contact_name', 'contact_email')->get();
            $response->socialLink = $response->crm_lead_soclink()->select('social_type', 'social_link')->get();
            $response->crm_lead_followup=$response->crm_lead_followup;
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


        // if(!ApiHelper::is_page_access($api_token, $this->page, $this->pageadd))
        //     return ApiHelper::JSON_RESPONSE(false,[],'PAGE_ACCESS_DENIED');
        //validation check 
        $rules = [
            'source_id' => 'required',
            'industry_id' => 'required',
           // 'status_id' => 'required',
            'priority' => 'required',
            'company_name' => 'required|string',
            'website' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zipcode' => 'required',
            'countries_id' => 'required',
            'phone' => 'required',
            'folllow_up' => 'required',
            //'followup_schedule' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails())
            return ApiHelper::JSON_RESPONSE(false, '', $validator->messages());

        $data = $request->except(['api_token', 'lead_id', 'contact', 'socialLink','followup_id','followup_status','next_followup']);
        $prdopval = CRMLead::where('lead_id', $request->lead_id)->update($data);

        $leadid = $request->lead_id;

        $contactdata = $request->contact;

        if (!empty($contactdata)) {
            CRMLeadContact::where('lead_id', $leadid)->delete();
            foreach ($contactdata as $key => $value) {
                $arra = [
                    'lead_id' => $leadid,
                    'contact_name' => $value['contact_name'], 'contact_email' => $value['contact_email']
                ];
                $prdopval2 = CRMLeadContact::Create($arra);
            }
        }

        $socialLinkdata = $request->socialLink;
        if (!empty($socialLinkdata)) {
            CRMLeadSocialLink::where('lead_id', $leadid)->delete();
            foreach ($socialLinkdata as $key => $value) {
                $arra = [
                    'lead_id' => $leadid,
                    'social_type' => $value['social_type'],
                    'social_link' => $value['social_link'],
                ];
                $prdopval3 = CRMLeadSocialLink::Create($arra, $arra);
            }
        }

        $crmfollowupupdate=CRMLeadFollowUp::where('followup_id',$request->followup_id)
        ->update([
            'followup_status'=>$request->followup_status,
            'next_followup'=>$request->next_followup,
        ]);

        if ($prdopval) {
            return ApiHelper::JSON_RESPONSE(true, $prdopval, 'LEAD_UPDATED');
        } else {
            return ApiHelper::JSON_RESPONSE(false, '', 'SOME_ISSUE');
        }
    }

    //view

    public function view(Request $request)
    {
        $response = CRMLead::find($request->lead_id);
        $followupresponse = CRMLeadFollowUp::find($request->lead_id);

        if (!empty($response)) {
            $response->crm_lead_contact = $response->crm_lead_contact;
            $response->crm_lead_soclink = $response->crm_lead_soclink;
            $response->crm_lead_source = $response->crm_lead_source;
            $response->crm_lead_industry = $response->crm_lead_industry;
            $response->crm_lead_agent = $response->crm_lead_agent;
            $followup = $response->crm_lead_followup->crm_lead_followup_history;
            $response->crm_lead_followup = $followup;
        }

        return ApiHelper::JSON_RESPONSE(true, $response, '');
    }


    //lead_followup_store

    public function lead_followup_store(Request $request)
    {
        if ($request->followup_status || $request->next_followup) {
            $data = CRMLeadFollowUp::where('followup_id', $request->followup_id)
                ->update([
                    'followup_status' => $request->followup_status,
                    'next_followup' => $request->next_followup,
                ]);
        }
        $crmdata = CRMLeadFollowUp::find($request->followup_id);
        $histdata = CRMLeadFollowUpHistory::create([
            'followup_note' => $request->followup_note,
            'followup_id' => $request->followup_id,
            'lead_id' => ($crmdata->lead_id != '') ? $crmdata->lead_id : '',
        ]);

        $response = [
            'followupdata' => $data,
            'followuphistorydata' => $histdata,
        ];

        return ApiHelper::JSON_RESPONSE(true, $response, '');
    }


    public function status_tab()
    {
        $list = CRMLeadStatus::all();
        if (!empty($list)) {
            $list = $list->map(function ($data) {
                $data->total = CRMLeadFollowUp::where('followup_status', $data->status_id)->count();
                return $data;
            });
        }

        return ApiHelper::JSON_RESPONSE(true, $list, '');
    }

    public function import_file(Request $request)
    {

        $dataList = ApiHelper::read_csv_data($request->fileInfo, "csv/lead");

        foreach ($dataList as $key => $value) {

            $indsID = '';
            $leadsID = '';
            $custcontID = '';
            $industryid = $value[0];
            $leadarrid = $value[3];

            $custnameid = $value[1];
            $custemailid = $value[2];

            $insdata =  CRMIndustry::where('industry_name', $industryid)->first();



            if (!empty($insdata))
                $indsID = $insdata->industry_id;
            else {
                $data = ['industry_name' => $industryid];
                $prdopval = CRMIndustry::create($data);
                $indsID = $prdopval->industry_id;
            }

            $leaddata =  CRMLead::where('company_name', $leadarrid)->first();

            if (!empty($leaddata))
                $leadsID = $leaddata->lead_id;
            else {
                $data = [
                    'company_name' => $value[3],
                    'phone' => $value[4],
                    'website' => $value[5],
                    'address' => $value[6],
                    'city' => $value[7],
                    'state' => $value[8],
                    'zipcode' => $value[9],
                    'country' => $value[10],
                    'industry_id' => $indsID,
                    'source_id' => !empty($request->source_id) ? $request->source_id : 1,
                    'status_id' => 7,
                ];
                $prdopval = CRMLead::create($data);
                $leadsID = $prdopval->lead_id;
            }

            $ContactData = ['contact_name' => $value[1], 'contact_email' => $value[2], 'lead_id' => $leadsID];
            $insdata =  CRMLeadContact::where($ContactData)->first();
            // return ApiHelper::JSON_RESPONSE(true, $ContactData, 'DATA_INSERTED');
            if (empty($insdata))
                CRMLeadContact::create($ContactData);
        }

        return ApiHelper::JSON_RESPONSE(true, $dataList, 'DATA_INSERTED');
    }
}
