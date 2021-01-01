@extends('dashboard.base')

@section('content')
  
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card" style="height:100% !important;">
                    <div class="card-body">
                        <h2 class="card-title">
                            Settings
                        </h2>
                        {{-- <p class="card-subtitle mb-2 text-muted">Setting up your shop
                            setting. Your shop is about to be ready.</p> --}}
                        <div class="row" style="margin-top:30px">
                            <div class="nav-tabs-boxed" style="width:100%">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#user"
                                            role="tab" aria-controls="home" aria-selected="false">User</a></li>
                                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#user-password" role="tab"
                                            aria-controls="home" aria-selected="false">User Password</a></li>
                                </ul>
                                <div class="tab-content" style="height:100%">
                                    <div class="tab-pane active" id="user" role="tabpanel">
                                        <form action="/user/update" method="post">
                                            <input type="hidden" name="tab" value="user">
                                            @csrf
                                            <div class="row mt-4">
                                                <div class="col-md-6 col-sm-12">
                                                    <div class="form-group row full-width">
                                                        <label class="col-md-4 col-sm-12 col-form-label" for="text-input">Name</label>
                                                        <div class="col-md-8">
                                                            <input class="form-control" type="text" name="name" required
                                                                value="{{ auth()->user()->name }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-group row ">
                                                        <label class="col-md-4 col-sm-12 col-form-label"
                                                            for="text-input">Email</label>
                                                        <div class="col-md-8">
                                                            <input class="form-control" type="email" name="email" required
                                                                value="{{ auth()->user()->email }}">
                                                        </div>
                                                    </div>
                                                    <div class="form-group row full-width">
                                                        <label class="col-md-4 col-sm-12 col-form-label"
                                                            for="text-input">Username</label>
                                                        <div class="col-md-8">
                                                            <input class="form-control" type="text" name="username" required
                                                                value="{{ auth()->user()->username }}">
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
                                    </div>
                                    <div class="tab-pane" id="user-password" role="tabpanel">
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
                                    </div>
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
