@extends('dashboard.base')

@section('content')
<style>
    #item-table{
        font-size: 0.75rem;
        width:100%  ;
        /* background-color: white !important; */
    }
#item-table td,#item-table th{
    border:none;
    padding: 3px;
}
#item-table td p{
    margin-bottom: 0;
}

.inbound-table tr:not(:last-child){
    border-bottom:1px solid grey;
}
</style>
<div class="container-fluid">
    <div class="fade-in">
        <div class="row">
            <div class="col-md-12">
                <div class="position-relative">
                    <div class="card">
                        <div class="card-header">Inbound Orders <a href="/inventory/inbound/create" class="float-right"><button class="btn btn-success">Create Inbound</button></a></div>
                        <div class="card-body">
                            <div style="overflow-y: auto">

                                <table class="table inbound-table w-100">
                                    <thead>
                                        <tr>
                                            <th>Payment Date</th>
                                            <th>Reference</th>
                                            <th>Supplier</th>
                                            <th>Inbound Items</th>
                                            <th class="text-center">Days to Supply</th>
                                            <th class="text-center">Received</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($inboundOrders as $inboundOrder)
                                            <tr data-inbound-order-id="{{$inboundOrder->id}}">
                                                <td>{{toClientDateformat($inboundOrder->payment_date)}}</td>
                                                <td><a href="/inventory/inbound/{{$inboundOrder->id}}">{{$inboundOrder->reference}}</a></td>
                                                <td>{{$inboundOrder->supplier_name}}</td>
                                                <td>
                                                    <div style="max-height:90px; overflow-y:auto">
                                                    <table id="item-table" class="w-100">
                                                        <!-- <thead>
                                                            <tr>
                                                                <th>No.</th>
                                                                <th>Item</th>
                                                                <th>Quantity</th>
                                                            </tr>
                                                        </thead> -->
                                                        @php
                                                         if(auth()->user()->currentShop->platform == "SHOPEE")$stocks = $inboundOrder->shopee_stocks;
                                                         elseif(auth()->user()->currentShop->platform == "LAZADA")$stocks = $inboundOrder->lazada_stocks;
                                                        @endphp
                                                        @foreach($stocks as $key => $stock)
                                                        <tr>
                                                                <!-- <td>{{$key + 1}}</td> -->
                                                                <td>
                                                                    @foreach($products as $product)
                                                                        @if(auth()->user()->currentShop->platform == "SHOPEE")
                                                                            @if($stock->platform_variation_id)
                                                                                @foreach($product['variations'] as $variation)
                                                                                    @if($stock->platform_item_id == $product['item_id'] && $stock->platform_variation_id == $variation['variation_id'])
                                                                                        <p>{{$product['name']}}</p>
                                                                                        <p>{{$variation['name']}} {{$product['item_sku'] || $variation['variation_sku'] ? '['.$product['item_sku'].($product['item_sku']?' ':'').$variation['variation_sku'].']': '' }}</p>
                                                                                        @break
                                                                                    @endif
                                                                                @endforeach
                                                                            @else
                                                                                @if($stock->platform_item_id == $product['item_id'])
                                                                                    <p>{{$product['name']}}</p>
                                                                                    <p>{{$product['item_sku'] ? '['.$product['item_sku']."]" : ''}}</p>
                                                                                    @break
                                                                                @endif
                                                                            @endif
                                                                        @elseif(auth()->user()->currentShop->platform == "LAZADA")
                                                                            @if($stock->platform_item_id == $product['item_id'])
                                                                            <p>{{$product['attributes']['name']}}</p>
                                                                            <p>{{$product['skus'][0]['SellerSku']}}</p>
                                                                            @endif
                                                                        @endif
                                                                    @endforeach
                                                                </td>
                                                                <td style="min-width:50px;" class="text-right">x {{$stock->pivot->quantity}}</td>
                                                            </tr>
                                                        @endforeach
                                                    </table>
                                                    </div>
                                                </td>
                                                <td class="text-center">{{$inboundOrder->days_to_supply}}
    
                                                    <br><span class="badge badge-primary">{{$inboundOrder->days_to_arrive >= 0 ? "Arrive in" : "Late arrival" }}: {{$inboundOrder->days_to_arrive}} {{$inboundOrder->days_to_arrive > 1 ||  $inboundOrder->days_to_arrive < -1? "days": "day"}}</span>
                           
                                                </td>
                                                <td class="text-center"><div class="received"></div></td>
                                                <td class="text-center"><div class="received-btn-div" data-received="{{$inboundOrder->stock_received}}"></div></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
    $(function(){

        $('.received-btn-div').each(function(idx,ele){
            ele = $(ele);
            var received_text_ele = ele.closest('tr').find('.received');
            console.log(received_text_ele);
            var received = ele.data('received');
            console.log(received);
            if(received){
                // changeReceivedBtnToTick(ele);
                received_text_ele.html("Yes");
            }else{
                ele.append("<a href='#' onclick='inboundOrderReceived(this,1)'>Stock Received</a>");
                received_text_ele.html("No");
            }
        })
        $('.inbound-table').dataTable({
            "order":[]
        });
    });
    
    function inboundOrderReceived(obj,received){
        e.preventDefault();
        obj = $(obj);
        var tr = obj.closest("tr");
        var inboundOrderId = tr.data('inbound-order-id');
        
        obj.attr("disabled", true);
        return $.ajax({
            async: true,
            type: 'POST',
            url: '/inventory/inbound/' + inboundOrderId + '/received',
            data: {_token: CSRF_TOKEN,received},
            success: function(data) {
                // changeReceivedBtnToTick();
                // console.log(obj.closest("tr"));
                obj.closest('div.received-btn-div').remove();
                $.notify("Inbound Order Updated","success");
                tr.find('.received').html("Yes");
            },
            error: ajaxErrorResponse,
            complete:function(){
                // consol
                obj.attr("disabled", false);
            }
        });
    }
    
    // function changeReceivedBtnToTick(ele){
        
    //     ele.append('<i class="fa fa-check" aria-hidden="true" style="color:green;"></i>');

    // }
</script>
@endsection