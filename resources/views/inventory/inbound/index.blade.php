@extends('dashboard.base')

@section('content')
<div class="container-fluid">
    <div class="fade-in">
        <div class="row">
            <div class="col-md-12">
                <div class="position-relative">
                    <div class="card">
                        <div class="card-header">Inbound Orders <a href="/inventory/inbound/create" class="float-right"><button class="btn btn-success">Create Inbound</button></a></div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Payment Date</th>
                                        <th>Reference</th>
                                        <th>Supplier</th>
                                        <th>Inbound Items</th>
                                        <th>Estimated Remaining Days to Arrive</th>
                                        <th>Received</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($inboundOrders as $inboundOrder)
                                        <tr data-inbound-order-id="{{$inboundOrder->id}}">
                                            <td>{{$inboundOrder->payment_date}}</td>
                                            <td><a href="/inventory/inbound/{{$inboundOrder->id}}">{{$inboundOrder->reference}}</a></td>
                                            <td>{{$inboundOrder->supplier_name}}</td>
                                            <td>
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th>No.</th>
                                                            <th>Item</th>
                                                            <th>Quantity</th>
                                                        </tr>
                                                    </thead>
                                                    @foreach($inboundOrder->stocks as $key => $stock)
                                                        <tr>
                                                            <td>{{$key + 1}}</td>
                                                            <td>
                                                                @foreach($products as $product)
                                                                @if($stock->platform_variation_id)
                                                                @foreach($product['variations'] as $variation)
                                                                @if($stock->platform_item_id == $product['item_id'] && $stock->platform_variation_id == $variation['variation_id'])
                                                                <p>{{$product['name']}}</p>
                                                                 <p>{{$variation['name']}} [{{$product['item_sku']}}{{$variation['variation_sku']}}]</p>
                                                                @break
                                                                @endif
                                                                @endforeach
                                                                @else
                                                                @if($stock->platform_item_id == $product['item_id'])
                                                                    <p>{{$product['name']}}</p>
                                                                    <p>[{{$product['item_sku']}}]</p>
                                                                    @break
                                                                @endif
                                                                @endif
                                                                @endforeach
                                                            </td>
                                                            <td>{{$stock->pivot->quantity}}</td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </td>
                                            <td>{{$inboundOrder->days_to_supply}}</td>
                                            <td class="text-center"><div class="received" data-received="{{$inboundOrder->stock_received}}"></div></td>
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
@endsection

@section('javascript')
<script src="{{ asset('js/main.js') }}"></script>
<script>
    $(function(){
        $('.received').each(function(idx,ele){
            ele = $(ele);
            var received = ele.data('received');
            if(received){
                changeReceivedBtnToTick(ele);
            }else{
                ele.append("<button type='button' class='btn btn-success' onclick='inboundOrderReceived(this,1)'>Received</button>");
            }
        })
    });
    
    function inboundOrderReceived(obj,received){
        obj = $(obj);
        var inboundOrderId = obj.closest("tr").data('inbound-order-id');
        
        obj.attr("disabled", true);
        return $.ajax({
            async: true,
            type: 'POST',
            url: '/inventory/inbound/' + inboundOrderId + '/received',
            data: {_token: CSRF_TOKEN,received},
            success: function(data) {
                changeReceivedBtnToTick(obj.closest('div.received'));
            },
            error: ajaxErrorResponse,
            complete:function(){
                // consol
                obj.attr("disabled", false);
            }
        });
    }
    
    function changeReceivedBtnToTick(ele){
        ele.empty();
        ele.append('<i class="fa fa-check" aria-hidden="true" style="color:green;"></i>');

    }
</script>
@endsection