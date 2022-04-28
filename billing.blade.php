<x-app-layout>
    <section class="section-base">

        <div class="container">

            <div class="row">

                @include('account.sidebar')
                <div class="col-lg-9 cnt-box cnt-call">
                    <h2>Billing Address<span class="dot">.</span></h2>
                    <hr class="space-sm">
                    @if (session()->has('message'))
                        <div class="alert alert-success">
                            {{ session()->get('message') }}
                        </div>
                    @endif
                    <form action="{{ url('/Update') }}" method="post" class="form-box ">
                        @csrf
                        <div class="row">
                            <div class="col-lg-6">
                                <p>Name</p>
                                <input id="name" name="name" placeholder="Name" value=""
                                    type="text" class="input-text" required>
                            </div>
                            <div class="col-lg-6">
                                <p>Email</p>
                                <input id="email" name="email" placeholder="Email" value=""
                                    type="email" class="input-text" required>
                            </div>

                            <div class="col-lg-12">
                                <p>Address</p>
                                <input name="address" type="text" autocomplete="off"
                                    class="input-text @error('address') is-invalid @enderror" placeholder="Address">
                                @error('address')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <!-- <div class="col-lg-6">
                                    <p>Phone Number</p>
                                    <input name="phoneno" type="text"autocomplete="off" class="input-text"  value="" placeholder="Phone Number" required>
                                </div> -->
                            <div class="col-lg-4">
                                <p>Country</p>
                                <select name="country" id="country-dropdown"
                                    class="input-select @error('country') is-invalid @enderror">
                                    <option value="0">Select country</option>
                                    @foreach ($country as $cn)
                                        <option value="{{ $cn->countries_id }}">{{ $cn->countries_name }}</option>
                                    @endforeach

                                </select>
                                @error('country')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-lg-4">
                                <p>State</p>
                                <select name="billing_state" id="state-dropdown" class="input-select" required>
                                    <option value="0">Select state</option>

                                    <option value=""></option>

                                </select>
                            </div>
                            <div class="col-lg-4">
                                <p>City</p>
                                <input type="text" name="city" id="city"
                                    class="input-select @error('city') is-invalid @enderror" placeholder="City">
                                @error('city')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <p>Pin Code</p>
                                <input id="pincode" name="pincode" placeholder="Pincode" type="text"
                                    class="input-text @error('pincode') is-invalid @enderror">
                                @error('pincode')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <p>Phone Number</p>
                                <input id="phone" name="phone" placeholder="Phone" type="text"
                                    class="input-text @error('phone') is-invalid @enderror">
                                @error('phone')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!--   <p>Phone</p>
                            <input id="phone" name="phone" placeholder="Phone" type="text" class="input-text"> -->

                        <p>Gst</p>
                        <input id="" name="gst" placeholder="Gst" type="text"
                            class="input-text @error('gst') is-invalid @enderror"> @error('gst')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror </br></br>

                        <button class="btn btn-sm" type="submit">Submit</button>

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

    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script type="text/javascript">
        $(document).ready(function() {
            $('#country-dropdown').on('change', function() {
                var countries_id = $(this).val();
                //alert(country_id);
                $("#state-dropdown").html('');
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });

                $.ajax({
                    url: '{{ url('/getState') }}',
                    type: "POST",
                    data: {
                        countries_id: countries_id,
                    },
                    dataType: "json",
                    success: function(result) {
                        $('#state-dropdown').html('<option value="">Select State</option>');
                        $.each(result, function(key, value) {
                            $("#state-dropdown").append('<option value="' + value
                                .state_id + '">' + value.state_name + '</option>');
                        });

                    }
                });
            });


        });
    </script>

</x-app-layout>
