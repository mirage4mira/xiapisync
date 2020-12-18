@extends('dashboard.base')

@section('content')

</style>

<div class="container-fluid">
  <div class="fade-in">
    <!-- /.row-->
    <div class="row">
      <div class="col-md-6 offset-md-3">
        <div class="position-relative">
          <div class="card">
            <div class="card-header">Sync items with Lazada</div>
            <div class="card-body">
              <div class="row">
                <div class="col-12 text-center">
                  <form action="/sync-items-with-lazada/create" method="get">
                    <h5>Choose a Lazada Shop to Sync</h5>
                    <select name="shop_id" class="form-control mt-3">
                      @foreach($lazadaShops as $shop_id => $lazadaShop)
                      <option value="{{$shop_id}}">{{$lazadaShop['shop_name']}} - {{$lazadaShop['shop_country']}}</option>
                      @endforeach
                    </select>
                    <input class="btn btn-primary mt-3" type="submit" value="Proceed">
                  </form>
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

<script src="{{ asset('js/Chart.min.js') }}"></script>
<script src="{{ asset('js/coreui-chartjs.bundle.js') }}"></script>
<script src="{{ asset('js/main.js') }}"></script>

<script>

</script>
@endsection