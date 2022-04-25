Command using website Pdf :laravel snappy Read and use command
Multiple search data filter: Using CustomerList Controller seen
Multiple document download Zip file : Using CustomerList Controller seen
auth command : composer require laravel/ui
php artisan ui:auth
 date format change function :$editData->hld_end_date ? date('d-m-Y',strtotime($editData->hld_end_date)) : null // bydefault null

date('d-m-Y',strtotime(str_replace('/', '-', $editData->hld_end_date) //bydefault value is null then 01-01-1970
                       
date_format(date_create($editData->hld_end_date),'d-m-Y') //bydefault value is null then Today date
                       
$editData->hld_end_date->format('d-m-Y') // null error
                       
\Carbon\Carbon::parse($editData->hld_end_date)->format('d/m/Y') //bydefault value is null then 01-01-1970
//$uj@y









