@extends('dashboard.base')

@section('content')
  
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card" style="height:100% !important;">
                    <div class="card-body">
                        <h2 class="card-title">
                            Feedback
                        </h2>
                        {{-- <p class="card-subtitle mb-2 text-muted">Setting up your shop
                            setting. Your shop is about to be ready.</p> --}}
                        <div class="card-text">
                            <form action="/feedback" method="POST">
                                @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Name</label>
                                        <input type="text" class="form-control" readonly value="{{auth()->user()->name}}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Email</label>
                                        <input type="email" class="form-control" readonly value="{{auth()->user()->email}}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="">Message</label>
                                        <textarea class="form-control" name="message"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 text-right">
                                    <input type="submit" name="Send" class="btn btn-primary">
                                </div>
                            </div>
                            </form>
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
  
    </script>
@endsection
