<x-app-layout>
    <section class="section-base">

        <div class="container">

            <div class="row">

                @include('account.sidebar')
                <div class="col-lg-9 cnt-box cnt-call">
                    <h2>Profile<span class="dot">.</span></h2>
                    <hr class="space-sm">
                    @if (session()->has('message'))
                        <div class="alert alert-success">
                            {{ session()->get('message') }}
                        </div>
                    @endif
                    <form action="{{url('accountupdate')}}" method="post" class="form-box "enctype="multipart/form-data">
                        @csrf
                       <div class="row">
                            <div class="col-lg-6">
                                <input id="name" name="name" placeholder="{{ __('Name') }}" type="text" value="{{$user->name}}" class="input-text @error('name') is-invalid @enderror" >
                                 @error('name')
                               <div class="alert alert-danger">{{ $message }}</div>
                               @enderror
                            </div>
                            <div class="col-lg-6">
                                <input id="email" name="email" placeholder="{{ __('Email Address') }}"  value="{{$user->email}}"type="email" class="input-text @error('email') is-invalid @enderror"    
                                    >
                                      @error('email')
                               <div class="alert alert-danger">{{ $message }}</div>
                               @enderror
                            </div>
                        </div>
                         <div class="row">
                            <div class="col-lg-6">
                                <input id="password" name="notify_email" placeholder="Notify Email" type="text" class="input-text @error('notify_email') is-invalid @enderror" >
                                     @error('notify_email')
                               <div class="alert alert-danger">{{ $message }}</div>
                               @enderror
                            </div>
                            <div class="col-lg-6">
                                <input id="password_confirmation" name="whatsapp_no" placeholder="Whatsapp Number" type="text"
                                       class="input-text @error('whatsapp_no') is-invalid @enderror">
                                        @error('whatsapp_no')
                               <div class="alert alert-danger">{{ $message }}</div>
                               @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <input id="name" name="secondary_phone" placeholder="Contact" type="text" class="input-text @error('secondary_phone') is-invalid @enderror" >
                                @error('secondary_phone')
                               <div class="alert alert-danger">{{ $message }}</div>
                               @enderror
                            </div>
                            <div class="col-lg-6">
                                <input id="email" name="business_name" placeholder="Company"  value="{{$user->business_name}}" type="text" class="input-text @error('business_name') is-invalid @enderror">
                                 @error('business_name')
                               <div class="alert alert-danger">{{ $message }}</div>
                               @enderror
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6">
                                <input  name="business_icon" placeholder="Upload icon" type="file" class="input-text @error('business_icon') is-invalid @enderror">
                                   @error('business_icon')
                               <div class="alert alert-danger">{{ $message }}</div>
                               @enderror
                            </div>
                           
                        </div>

                       
                         

                        <!---  <p>Phone</p>
                            <input id="phone" name="phone" placeholder="Phone" type="text" class="input-text"> -->

                       

                        <button class="btn btn-sm" type="submit">Update</button>

                    </form>
                </div>
            </div>
            <hr class="space-lg" />
            <div class="cnt-box cnt-call">

                <div class="caption">
                    <h2>Docs and features</h2>
                    <p>
                        There is much more than what you see here! Visit the documentation to see all the components
                        features and for the implementation instructions.
                    </p>
                    <a href="https://themekit.dev/docs/components/contact-form/"
                        class="btn btn-sm btn-circle">Documentation</a>
                </div>
            </div>
        </div>
    </section>

    
</x-app-layout>
