@extends('dashboard.base')

@section('content')

</style>

<div class="container-fluid">
  <div class="fade-in">
    <!-- /.row-->
    <div class="row">
      <div class="col-md-10 offset-md-1">
        <div class="position-relative">
          <div class="card">
            <div class="card-header">Sync items with Lazada
            </div>
            <div class="card-body">
              <div class="row">
                  <div class="col-12 text-center">
                      <div style="display: flex;flex-direction:row;align-items: center;justify-content:space-between;">
                        <div>
                            <h5>Choose a Lazada Shop:</h5>
                        </div>
                        <div>
                            <form action="/sync-items-with-lazada" method="get">
                                <select name="shop_id" class="form-control">
                                    @foreach($lazadaShops as $shop_id => $lazadaShop)
                                    <option value="{{$shop_id}}">{{$lazadaShop['shop_name']}} - {{$lazadaShop['shop_country']}}</option>
                                    @endforeach
                                </select>
                            </form>
                            @if($shop)
                            <form action="/sync-items-with-lazada/map-by-sku" method="post" class="mt-3">
                                @csrf
                                <input type="hidden" name="mapped_shop_id" value="{{$shop->id}}">
                                <button class="float-right btn btn-primary" type="submit">Map by sku</button>
                            </form>
                            @endif
                      </div>
                    </div>
                    @if($shop)
                    <form action="/sync-items-with-lazada" method="POST">
                        <input type="hidden" name="mapped_shop_id" value="{{$shop->id}}">
                        @csrf
                    <div class="row mt-3">
                      <table class="table table-bordered table-sm item-table w-100">
                        <thead>
                          <tr>
                            <th class="text-left">Item</th>
                            <th class="text-center">Mapped Lazada Item</th>
                            {{-- <th class="text-center">Sync Stock</th> --}}
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($shopeeItems as $item)
                          @if($item['variations'])
                            @foreach($item['variations'] as $variation)
                              {{-- {{dd($variation)}} --}}
                              <tr>
                                <td class="text-left">{{$item['name']}}<p class="mb-0">{{$variation['name']}}</p></td>
                                <td class="text-left" style="width: 40%">
                                    <input type="hidden" name="item_id[]" value="{{$item['item_id']}}">
                                    <input type="hidden" name="variation_id[]" value="{{$variation['variation_id']}}">
                                    <input type="hidden" name="lazada_sku_id[]" value="{{@$variation['_append']['stock_syncs']->where('mapped_shop_id',$shop->id)->first()->sku_id}}">
                                    <input type="hidden" name="lazada_seller_sku[]" value="{{@$variation['_append']['stock_syncs']->where('mapped_shop_id',$shop->id)->first()->seller_sku}}">
                                    {{-- <input type="hidden" name="variation_id[]" value="{{$variation['variation_id']}}"> --}}
                                    <select name="lazada_product_id[]" id="" class="w-100">
                                        <option value=""></option>
                                        @foreach($products as $product)
                                        @php
                                     
                                            $stock_sync = $variation['_append']['stock_syncs'];

                                            $selected = '';
                                            if($stock_sync){
                                                if($stock_sync->where('mapped_shop_id',$shop->id)->where('item_id',$product['item_id'])->first()){
                                                    $selected = 'selected';
                                                }
                                            }

                                        @endphp
                                        <option value="{{$product['item_id']}}" data-seller-sku="{{$product['skus'][0]['SellerSku']}}" data-sku-id="{{$product['skus'][0]['SkuId']}}" {{$selected}}>{{$product['attributes']['name']}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                {{-- <td class="text-center"></p></td> --}}
                              </tr>
                              @endforeach
                              @else
                              <tr>
                                <td class="text-left">{{$item['name']}}</td>
                                <td class="text-left" style="width: 40%">
                                    <input type="hidden" name="item_id[]" value="{{$item['item_id']}}">
                                    <input type="hidden" name="variation_id[]" value="0">
                                    <input type="hidden" name="lazada_sku_id[]" value="">
                                    <input type="hidden" name="lazada_seller_sku[]" value="">

                                    <select name="lazada_product_id[]" id="" class="w-100">
                                        <option value=""></option>
                                        @foreach($products as $product)
                                        @php
                                            $stock_sync = $item['_append']['stock_syncs'];

                                            $selected = '';
                                            if($stock_sync){
                                                if($stock_sync->where('mapped_shop_id',$shop->id)->where('item_id',$product['item_id'])->first()){
                                                    $selected = 'selected';
                                                }
                                            }

                                        @endphp
                                        <option value="{{$product['item_id']}}" data-seller-sku="{{$product['skus'][0]['SellerSku']}}" data-sku-id="{{$product['skus'][0]['SkuId']}}" {{$selected}}>{{$product['attributes']['name']}}</option>
                                        @endforeach
                                    </select>
                                </td>
                                {{-- <td class="text-center">{{$item['_append']['stock_syncs'] && $item['_append']['stock_syncs']->where('platform','LAZADA')->count() ? 'Yes' : ''}}</p></td> --}}
                            </tr>
                          @endif
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                    @endif
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
$('select[name="lazada_product_id[]"]').select2();

$('select[name="lazada_product_id[]"]').on("change",function(evt){
    obj = evt.target;
    var td = $(obj).closest('td');
    console.log(td.find('input[name="lazada_sku_id[]"]'));
    td.find('input[name="lazada_sku_id[]"]').val($(obj).find(':selected').data('sku-id'));
    td.find('input[name="lazada_seller_sku[]"]').val($(obj).find(':selected').data('seller-sku'));
});
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

</script>
@endsection