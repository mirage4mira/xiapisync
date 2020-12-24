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
                  <form action="/add-items-to-lazada/create" method="get">
                    <div style="display: flex;flex-direction:row;align-items: center;justify-content:space-between;">
                      <div>
                        <h5>Choose a Lazada Shop:</h5>
                      </div>
                      <div>
                        <select name="shop_id" class="form-control">
                          @foreach($lazadaShops as $shop_id => $lazadaShop)
                          <option value="{{$shop_id}}">{{$lazadaShop['shop_name']}} - {{$lazadaShop['shop_country']}}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="row mt-3">
                      <table class="table table-bordered table-sm item-table">
                        <thead>
                          <tr>
                            <th><input type="checkbox" onchange="tickCheckBoxOfCurrentPage(this)"></th>
                            <th class="text-left">Item</th>
                            <th class="text-center">Created</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($shopeeItems as $item)
                          @if($item['variations'])
                            @foreach($item['variations'] as $variation)
                              <tr>
                                <td><input type="checkbox" name="items_code[]" value="{{$item['item_id'].'|'.$variation['variation_id']}}"></td>
                                <td class="text-left">{{$item['name']}}<p class="mb-0">{{$variation['name']}}</p></td>
                                <td class="text-center">{{$variation['_append']['stock_syncs'] && $variation['_append']['stock_syncs']->where('platform','LAZADA')->count() ? 'Yes' : ''}}</p></td>
                              </tr>
                              @endforeach
                              @else
                              <tr>
                                <td><input type="checkbox" name="items_code[]" value="{{$item['item_id'].'|0'}}"></td>
                                <td class="text-left">{{$item['name']}}</td>
                                <td class="text-center">{{$item['_append']['stock_syncs'] && $item['_append']['stock_syncs']->where('platform','LAZADA')->count() ? 'Yes' : ''}}</p></td>
                            </tr>
                          @endif
                          @endforeach
                        </tbody>
                      </table>
                    </div>
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
$(function(){
  $('.item-table').dataTable({
    columnDefs: [
     { orderable: false, targets: 0 },
     
    ],
    scrollY: 400,
    fixedColumns: true,
        fixedHeader: true,
        autoWidth: true,
    
  });
})

function tickCheckBoxOfCurrentPage(obj){
  if($(obj).prop("checked") == true){
    $('input[name="items_code[]"]').prop('checked', true);
  }else{
    $('input[name="items_code[]"]').prop('checked', false);
  }
}
</script>
@endsection