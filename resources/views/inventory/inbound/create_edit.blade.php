@extends('dashboard.base')

@section('content')
<div class="container-fluid">
    <div class="fade-in">
        <div class="row">
            <div class="col-md-12">
                <div class="position-relative">
                    <div class="card">
                        <div class="card-header">
                            @if($inbound_order)
                            Inbound Order #{{$inbound_order->id}}
                            <div class="float-right">
                                <div class="received d-inline">                                    
                                </div>
                                <form action="/inventory/inbound/{{$inbound_order->id}}" method="post" class="d-inline">
                                @method('delete')
                                @csrf
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this Inbound Order?')">Delete</button>
                                </form>
                            </div>
                            @else
                            New Inbound Order
                            @endif
                        </div>
                        <div class="card-body">
                            @if($inbound_order)
                            <form method="post" action="/inventory/inbound/{{$inbound_order->id}}">
                            {{ method_field('PUT') }}
                            @else
                            <form method="post" action="/inventory/inbound">
                            @endif
                                @csrf
                                <div class="form-row">
                                    <div class="form-group col-md-8">
                                        <label>Supplier</label>
                                        <input list="supplier" class="form-control" placeholder="Supplier Name" name="supplier_name" required value="{{$inbound_order? $inbound_order->supplier_name : ''}}">
                                        <datalist id="supplier">
                                            @foreach($suppliers as $supplier)
                                            <option value="{{$supplier}}">
                                                @endforeach
                                        </datalist>
                                    </div>
                                    <div class="form-group col-md-4">
                                        <div class="form-row">
                                            <div class="form-group col-md-12">
                                                <label>Payment Date</label>

                                                <input class="form-control" name="payment_date" required value="{{$inbound_order? toClientDateformat($inbound_order->payment_date) : toClientDateformat(now())}}">
                                            </div>
                                            <div class="form-group col-md-12">
                                                <label>Reference</label>
                                                <input class="form-control" name="reference" required value="{{$inbound_order? $inbound_order->reference : ''}}">
                                            </div>
                                            <div class="form-group col-md-12">
                                                <label>Estimated Days To Supply</label>
                                                <input type="number" min="0" step="1" class="form-control" name="days_to_supply" required value="{{$inbound_order? $inbound_order->days_to_supply : 0}}"> 
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-success" onclick="addItemRow()">Add Item</button>
                                <div class="row mt-3">
                                    <div class="col-7">
                                        Item
                                    </div>
                                    <div class="col-2">
                                        Quantity
                                    </div>
                                    <div class="col-2">
                                        Cost
                                    </div>
                                </div>
                                <hr class="mt-1">
                                <div id="items-div">

                                </div>

                                <button type="submit" class="btn btn-primary float-right">Save</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-none item-row">

    <div class="form-row">
        <div class="form-group col-md-7">
            <!-- <label>Item</label> -->
            <input type="hidden" class="form-control" name="items_id[]">
            <input type="hidden" class="form-control" name="variations_id[]">
            {{--<input list="item" class="form-control" onchange="itemSelectedAction(this)" name="items_name[]" required>
            <datalist id="item">
                @foreach($products as $product)
                @if(empty($product['variations']))
                <option value="{{$product['name']}}" data-item-id="{{$product['item_id']}}" data-variation-id="0" data-cost="{{$product['_append']['cost']}}">[{{$product['item_sku']}}]</option>
                @else
                @foreach($product['variations'] as $variation)
                <option value="{{$product['name']}}" data-item-id="{{$product['item_id']}}" data-variation-id="{{$variation['variation_id']}}" data-cost="{{$variation['_append']['cost']}}">{{$variation['name']}} [{{$product['item_sku']." ".$variation['variation_sku']}}]</option>
                @endforeach
                @endif
                @endforeach
            </datalist>--}}
            <!-- <input list="item" class="form-control" onchange="itemSelectedAction(this)" name="items_name[]" required> -->
            <select class="form-control item-select" onchange="itemSelectedAction(this)" required>
                <option value=""></option>
                @foreach($products as $product)
                @if(empty($product['variations']))
                <option data-item-id="{{$product['item_id']}}" data-variation-id="0" data-cost="{{$product['_append']['cost']}}" value="{{$product['item_id'].'|0'}}"> {{$product['name']}} <br> [{{$product['item_sku']}}]</option>
                @else
                @foreach($product['variations'] as $variation)
                <option data-item-id="{{$product['item_id']}}" data-variation-id="{{$variation['variation_id']}}" data-cost="{{$variation['_append']['cost']}}" value="{{$product['item_id'].'|'.$variation['variation_id']}}">{{$product['name']}} <br> {{ $variation['name']}} [{{$product['item_sku']." ".$variation['variation_sku']}}]</option>
                @endforeach
                @endif
                @endforeach
            </select>

        </div>
        <div class="form-group col-md-2">
            <!-- <label>Quantity</label> -->
            <input type="number" class="form-control" min="0" step="1" name="quantities[]" required>
        </div>
        <div class="form-group col-md-2">
            <!-- <label>Cost</label> -->
            <input type="number" class="form-control" min="0" step="0.01" name="costs[]" required>
        </div>
        <div class="form-group col-md-1 d-flex justify-content-center">
            <!-- <label>Cost</label> -->
            <button type="button" class="btn btn-sm btn-danger" onclick="$(this).closest('.item-row').remove();"><i class="cil-trash"></i></button>
        </div>
    </div>
</div>
@endsection

@section('javascript')
<script src="{{ asset('js/main.js') }}"></script>
<script>
    $(function() {
        $('[name="payment_date"]').datepicker();
        // $('.item-select').select2();
        @if($inbound_order)


        displayStockReceived({{$inbound_order->stock_received}});
        @foreach($inbound_order->stocks as $stock)
        addItemRow({item_id:'{{$stock->platform_item_id}}',variation_id:'{{$stock->platform_variation_id}}',quantity:'{{$stock->pivot->quantity}}',cost:'{{$stock->pivot->cost}}'});
        @endforeach
        @else
        addItemRow();
        @endif
    })
    @if($inbound_order)
    function displayStockReceived(received){
        var obj = $('div.received');

        obj.empty();
        if(received){
            obj.append(`<button type="button" class="btn btn-warning" onclick='inboundOrderReceived(this,{{$inbound_order->id}},0)'>Revert Stock Received</button>`);
        }else{
            obj.append(`<button type="button" class="btn btn-success" onclick='inboundOrderReceived(this,{{$inbound_order->id}},1)'>Stock Received</button>`);
        }
    }
    @endif

    function addItemRow(data) {
       var itemRow = $('.item-row.d-none').clone().removeClass('d-none');
       $('#items-div').append(itemRow);
       if(data){
           console.log(data);
           itemRow.find('.item-select').val(data.item_id+'|'+data.variation_id).change();
           itemRow.find('[name="items_id[]"]').val(data.item_id);
           itemRow.find('[name="variations_id[]"]').val(data.variation_id);
           itemRow.find('[name="quantities[]"]').val(data.quantity);
           itemRow.find('[name="costs[]"]').val(data.cost);
           
       }
       itemRow.find('.item-select').select2();

        
    }

    function itemSelectedAction(obj) {
        obj = $(obj);
        var value = obj.val();
        console.log(value);
        var item = obj.find(':selected');
        var item_id = item.data('item-id');
        var variation_id = item.data('variation-id');
        var cost = item.data('cost');


        obj.closest('.item-row').find("[name='items_id[]']").val(item_id);
        obj.closest('.item-row').find("[name='variations_id[]']").val(variation_id);
        obj.closest('.item-row').find("[name='costs[]']").val() ? '': obj.closest('.item-row').find("[name='costs[]']").val(cost);
    }

    function inboundOrderReceived(obj,id,received){
        obj = $(obj);
        
        obj.attr("disabled", true);
        return $.ajax({
            async: true,
            type: 'POST',
            url: '/inventory/inbound/' + id + '/received',
            data: {_token: CSRF_TOKEN,received},
            success: function(data) {
                displayStockReceived(received);
                $.notify("Inbound Order Successfully Updated","success");
            },
            error: ajaxErrorResponse,
            complete:function(){
                // consol
                obj.attr("disabled", false);
            }
        });
    }
</script>
@endsection