<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Auth;
use App\Models\Business_info;
use App\Models\Countries;
use App\Models\States;

class AccountController extends Controller
{
    public function index()
    {
        return view('account.index'); 
    }

    public function profile()
    {     $country=Countries::all();
          $state=States::all();
          //dd($country);
        return view('account.profile',compact('country','state'));
    }
     
       public function getState(Request $request)
    {
          $data = States::select('state_name','state_id')->where("countries_id",$request->countries_id)->get();
        // //dd($data);
        // return response($data);
       // $data['States'] = States::find('countries_id',$request->countries_id)-;

       // return response()->json($data);
         return response()->json($data);
    }



     public function show()
    {
        return view('account.plan');
    }
    
    public function profileUpdate(Request $request)
    {
       $request->validate([

        'address'=>'required',
         'city'=>'required',
         'country'=>'required',
         'pincode'=>'required',
         'phone'=>'required',
         'gst'=>'required',
           


       ]);




       $user = Business_info::updateOrCreate(['business_id'   => Auth::user()->business_id],
        ['billing_contact_name' => request()->name,
        'billing_email' => request()->email,
        'billing_street_address' => request()->address,
        'billing_city' => request()->city,
        'billing_state' => request()->billing_state,
        'billing_country' => request()->country,
        'billing_zipcode' => request()->pincode,
        'billing_phone' => request()->phone,
        'billing_gst' => request()->gst,
        'status' => 1]
       );

       return redirect()->back()->with('message', 'Successfully!');
    }

      public function resetpassword(Request $request)
      {

          $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise we will parse the error and return the response.
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                            ->withErrors(['email' => __($status)]);



          // $request->validate([
          //     'email' => 'required|email|exists:users',
          // ]);
  
          // $token = Str::random(64);
  
          // DB::table('User')->insert([
          //     'email' => $request->email, 
          //     'token' => $token, 
          //     'created_at' => Carbon::now()
          //   ]);
  
          // return back()->with('message', 'Password reset');
           // DB::table('User')->update([
           //     // 'email' => $request->email,
           //     'password' => $request->password, 
           //     'token' => $token, 
           //     'created_at' => Carbon::now()
           //  ]);
        
           //      return redirect()->back()->with('success',"Password Reset");
      }

      public function notification(Request $request)
      {
        return view('account.notification'); 
      }
  

}
