<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Storage;


use App\Models\CommentsMeta;
use App\Models\Comments;
use App\Models\HlehUser;
use Carbon\Carbon;

class ImportController extends Controller
{





    public function index ()
    {

        return view ('import.index');
    }


    public function read_csv_data($fileInfo, $path){

        $file = $fileInfo;
        $path = $file->store($path);
        $file_path = Storage::path($path);
        $file = fopen($file_path,"r");
        $dataList = fgetcsv($file);
        $all_data = [];
        while ( ($data = fgetcsv($file) ) !== FALSE ) {
            
            array_push($all_data,$data);
        }
        return $all_data;

    } 

    public function store(Request $request)
    {

         
       $dataList=$this->read_csv_data($request->upload, "csv/lead");
        // dd($dataList);
       foreach ($dataList as $key => $value) {

        $post_id = $user_id = '';
        $Users = array();

        if(isset($value)){

               // print_r($value);

            $user_id = isset($value[4]) ? $value[4] : 0;
            $post_id = isset($value[2]) ? $value[2] : 0;
            
            $comment_content= isset($value[7]) ? $value[7] : 0; 
            $comment_date= isset($value[9]) ? $value[9] : 0;
            $date=date('Y-m-d',strtotime(str_replace('/', '-', $comment_date)));
           // dd($date);

            if($user_id)
                $Users =  HlehUser::where('ID', $user_id)->first();

            if(!empty($Users)){


                $Comment_user =$Users->user_nicename;
                //dd($Comment_user);
                $comment_user_email=$Users->user_email;

                $data = [
                   'comment_author'=>$Comment_user,
                   'comment_author_email'=> $comment_user_email,
                   'comment_post_ID'=>$post_id,
                   'comment_date'=> $date,
                   'comment_date_gmt'=>$date,
                   'comment_content'=>$comment_content,
                   'user_id'=>$user_id,
                   'comment_agent'=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWeb...',
               ];
               $comments = Comments::create($data);

               $Userdata= [

                'user_status'=>1,
                'user_registered'=>Carbon::now(),

            ];
            $userCreate=HlehUser::create($Userdata);                  

        }
    }
}
}  

}
