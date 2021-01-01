@extends('dashboard.base')

@section('content')
  
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card" style="height:100% !important;">
                    <div class="card-body">
                        <h2 class="card-title">
                            About
                        </h2>
                        {{-- <p class="card-subtitle mb-2 text-muted">Setting up your shop
                            setting. Your shop is about to be ready.</p> --}}
                        <div class="row" style="margin-top:30px">
                            <div class="nav-tabs-boxed" style="width:100%">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#subscription"
                                            role="tab" aria-controls="home" aria-selected="false">Subscription</a></li>
                                    {{-- <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#user-password" role="tab" --}}
                                            {{-- aria-controls="home" aria-selected="false">User Password</a></li> --}}
                                </ul>
                                <div class="tab-content" style="height:100%">
                                    <div class="tab-pane active" id="subscription" role="tabpanel">
                                            <div class="row mt-4">
                                                <div class="col-md-6 col-sm-12">
                                                    <div class="form-group row full-width">
                                                        <label class="col-md-4 col-sm-12 col-form-label" for="text-input">Plan Expiry Date</label>
                                                        <div class="col-md-8">
                                                            <input class="form-control" type="text" readonly
                                                                value="{{ Carbon\Carbon::parse(auth()->user()->plan_expiry_date)->format("d M Y") }}">
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                            <div class="mt-4 row">
                                                <div class="col-10">

                                                </div>
                                            </div>

                                    </div>
                                    {{-- <div class="tab-pane" id="user-password" role="tabpanel">
                                        <form action="/user/update" method="post">
                                            <input type="hidden" name="tab" value="user-password">
                                            @csrf
                                            <div class="row mt-4">
                                                <div class="col-md-6 col-sm-12">
                                                    <div class="form-group row full-width">
                                                        <label class="col-md-4 col-form-label" for="text-input">Password</label>
                                                        <div class="col-md-8 col-sm-12">
                                                            <input class="form-control" type="password" name="password"
                                                                id="password" minlength="8" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-group row full-width">
                                                        <label class="col-md-4 col-form-label" for="text-input">Confirm
                                                            Password</label>
                                                        <div class="col-md-8 col-sm-12">
                                                            <input class="form-control" type="password" name="confirm_password"
                                                                id="confirm_password" required>
                                                            <span id='message'></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-4 row">
                                                <div class="col-10">

                                                </div>
                                                <div class="col-2">
                                                    <button class="btn btn-primary float-right" type="submit">Save</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div> --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('javascript')
    <script src="{{ asset('js/main.js') }}"></script>
    <script>
        var tab = '{{Session::has('tab') ? Session::get('tab'):''}}';
        $(function(){
            if(tab){
                $('.nav-link[href="#'+tab+'"]').trigger('click');
            };
        })
        $('#password, #confirm_password').on('keyup', function() {
            if ($('#password').val() == $('#confirm_password').val()) {
                $('#message').html('Matching').css('color', 'green');
            } else
                $('#message').html('Not Matching').css('color', 'red');
        });

    </script>
@endsection
