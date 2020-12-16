@extends('dashboard.authBase')

@section('content')
    <style>
      .logout-btn:hover{
        color:blue;
      }
    </style>
    <div style="position:absolute;top:0;right:0;">
      <form action="/logout" method="POST"> @csrf <button type="submit" class="btn btn-block logout-btn"><i class="fa fa-sign-out" aria-hidden="true"></i>&nbsp;Logout</button></form>
    </div>
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card">
            <div class="card-body">
                <h2 class="card-title">
                    Sign in Shop
                </h2>
                <p class="card-subtitle mb-2 text-muted">Sign in to a shop to proceed. You can add more shops later.</p>
                <div class="row" style="margin-top:30px">
                    <div class="col-md-6 border-right text-center">
                        <img src="/images/shopee-logo.png" height="70px">
                        <div style="margin:20px 0px">
                            <a href="{{$shopeeAuthLink}}"><button class="btn btn-primary">Sign in Shopee</button></a>
                        </div>
                    </div>
                    <div class="col-md-6 text-center">
                        <img src="/images/lazada-logo.png" height="70px">
                        <div style="margin:20px 0px">
                        <a href="{{$lazadaAuthLink}}"><button class="btn btn-primary">Sign in Lazada</button></a>
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

@endsection