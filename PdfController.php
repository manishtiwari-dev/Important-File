<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockOrder;
use App\Models\Stockvso;
use App\Models\Customer;
use App\Models\CustomerDocument;
use PDF;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use File;



class PdfController extends Controller
{



    public function  orderDetailsDocumentPDF($vso_type,$order_id){


        $stock_vso = Stockvso::where('vso_type',$vso_type)->where('order_id', $order_id)->first(); 
        $pdf = \PDF::loadView('sample-pdf',['stockvso'=>$stock_vso]);
        return $pdf->download('orderDetailsDocumentPDF.pdf');


    }


    public function  saveorderDetailsDocumentPDF($vso_type,$order_id){

        $stock_vso = Stockvso::where('vso_type',"bank")->where('order_id', $order_id)->first(); 
        $pdf = \PDF::loadView('sample-pdf',['stockvso'=>$stock_vso]);
        
        $filename = 'temp/'.$order_id.'_'.$vso_type.'.pdf';
        
        if(!file_exists($filename))
            $pdf->save($filename);

        return $filename;

    }
    public function allFileDownload($order_id){

        $order = StockOrder::find($order_id);
        
        $stockvso = Stockvso::Where('order_id', $order->customer_id)->get();  
        $customer=Customer::find($order->customer_id); 
        $doclist = CustomerDocument::where('customer_id',$order->customer_id)->get(); 
     
        $zip = new ZipArchive;

        $fileName = 'temp/'.$order_id.'_'.time().'_document.zip';
        if ($zip->open(public_path($fileName), ZipArchive::CREATE) === TRUE) {
       

            $bank_file_name = $this->saveorderDetailsDocumentPDF('bank', $order_id);
            $fullpath = public_path($bank_file_name);
            $initial_name =  basename($fullpath);
            $zip->addFile($fullpath,$initial_name);

          


            $cstomer_file_name = $this->saveorderDetailsDocumentPDF('customer', $order_id);
            $fullpath = public_path($cstomer_file_name);
            $initial_name =  basename($fullpath);
            $zip->addFile($fullpath,$initial_name);

            if(!empty($doclist)){
                foreach ($doclist as $key => $doclist) {
                  $fullpath = storage_path('storage/app/public/'.$doclist->document_url);
                  $initial_name =  basename($fullpath);
               
                $zip->addFile($fullpath,$initial_name);

            
                         
            }
        }

          $zip->close();
      
    }
      return response()->download(public_path($fileName));
   
    }
}
