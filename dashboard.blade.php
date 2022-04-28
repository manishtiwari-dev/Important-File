<x-app-layout>
	<!-- [ breadcrumb ] start -->
   <div class="row">
    <div class="col-md-12">
     <h5>Dashboard</h5>
 </div>
</div>
<div class="row" style="margin-top: 10px;">
    <div class="col-md-6">
        <form action="{{url('customer/')}}" id="validation-form123">
            <input type="" class="form-control" name="search" value="" placeholder="Search..">
        </form>
    </div>
    
</div>

<!-- [ breadcrumb ] end -->


<!-- [ Main Content ] start -->
<br/>
<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table" id="listing">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Customer Name</th>
                        
                        <th>Chasis No</th>
                        <th>Model</th>
                        <th>OTR Price</th>
                       
                        <th>O/S Amount</th>
                       
                    </tr>
                </thead>
                <tbody>

                 @if(!empty($orderlist)>0)
                 @foreach($orderlist as $key=>$order)
            
                 <tr>
                    <td>{{ $key+1 }} </td>
                    <td>{{$order->customer->customer_name}}</td>
                    <td>{{$order->product->ProChassisNo}}</td>
                    
                    <td>{{$order->product->ProModel}}</td>
                    
                    <td>{{$order->product->ProPrice}}</td>
                    
                    <td>{{$order->customer->outstanding_amount}}</td>
                 
                    
                </tr>
                @endforeach
              
            
            @endif
         </tbody>
     </table>
 </div>
</div>
</div>
             <div class="row">
                  <div class="col-md-12 text-right">
                      <a  href="{{url('customer/')}}">View More</a>
              </div>  
           </div>

           @push('scripts')
<!-- jquery-validation Js -->
<script src="{{asset('vroom/assets/js/plugins/jquery.validate.min.js')}}"></script>
<!-- form-picker-custom Js -->
<script src="{{asset('vroom/assets/js/pages/form-validation.js')}}"></script>


<script>
    $(document).ready(function(){

        $('#validation-form123').validate({
          rules:{
            search : {
             required : true,
             maxlength : 50
         },

    },
    messages : {
        search : {
            required : 'Please Enter the any field',
            
        }
        


    }
});

});

    
</script>

@endpush


</x-app-layout>