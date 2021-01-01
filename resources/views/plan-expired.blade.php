@extends('dashboard.authBase')

@section('content')

    {{-- <div style="position:absolute;top:0;right:0;">
      <form action="/logout" method="POST"> @csrf <button type="submit" class="btn btn-block logout-btn"><i class="fa fa-sign-out" aria-hidden="true"></i>&nbsp;Logout</button></form>
    </div> --}}
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card">
            <div class="card-body">
                <h2 class="card-title text-center">
                    Opps! Your plan has expired!
                </h2>
                <div class="card-text text-center">
                    Renew your plan to continue.
                    <div class="mt-3">
                        <a href="/payment"><button class="btn btn-primary">Renew Now</button></a>
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