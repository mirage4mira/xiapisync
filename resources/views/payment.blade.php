@php
 if(auth()->user()->planExpired())$layout = 'dashboard.authBase';
 else $layout =  'dashboard.base';  
@endphp
@extends($layout)

@section('content')

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card" style="height:100% !important;">
                    <div class="card-body">
                        <h2 class="card-title">
                            Payment
                        </h2>
                        {{-- <p class="card-subtitle mb-2 text-muted">Setting up your shop
                            setting. Your shop is about to be ready.</p> --}}
                        <div class="card-text">
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="">Amount</label>
                                        <input type="text" readonly value="29.90 (USD)" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="">Item</label>
                                        <input type="text" readonly value="xiapisync.com (1 Year Plan)" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-12 text-center">
                                    <div>
                                        <form action="/payment/stripe" method="POST">
                                            @csrf
                                            <script src="https://checkout.stripe.com/checkout.js" class="stripe-button"
                                                data-key="{{env('STRIPE_PUBLISHABLE_KEY')}}"
                                                data-amount="2990" data-name="xiapisync.com" data-description="1 Year Plan"
                                                data-locale="auto"
                                                data-image="https://stripe.com/img/documentation/checkout/marketplace.png"
                                                data-currency="usd">
        
                                            </script>
                                        </form>
                                    </div>
                                    <div class="mt-3">
                                        <form action="/payment/paypal" method="POST">
                                            @csrf
                                            <input type="submit" value="Pay with Paypal" class="btn btn-warning">
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
  
    </script>
@endsection
