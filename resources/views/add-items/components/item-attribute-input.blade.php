<style>

</style>
<input type="hidden" name="primary_category_id[{{$inputArrayNumber}}]" value="{{$primary_category_id}}">
<input type="hidden" name="item_id[{{$inputArrayNumber}}]" value="{{$shopeeItem['item_id']}}">
<input type="hidden" name="variation_id[{{$inputArrayNumber}}]" value="{{isset($shopeeItem['_variation']) && $shopeeItem['_variation'] ? $shopeeItem['_variation']['variation_id'] : 0}}">
@foreach($categoryAttr as $category)
<div class="col-4">
<div class="form-group">
    <label for=""
    @if($category['is_mandatory'])
    class="required"
    @endif
    >{{$category['label']}}</label>
    @if(in_array($category['input_type'],["text","numeric","date"]))
        <input
            @if($category['input_type'] == "text") 
            type="text" 
            @elseif($category['input_type'] == "numeric")
            type="number" 

            @if($category['name'] == "package_weight")
            min="0.02"
            step="0.01"
            @endif

            @elseif($category['input_type'] == "date")
            type="date"
            @endif
        name="{{$category['name']}}[{{$inputArrayNumber}}]" class="form-control"
        
        @if($category['is_mandatory'])
        required
        @endif

        @if($category['name'] == "name")
            value="{{$shopeeItem['name']}}"
        @elseif($category['name'] == "package_weight")
            value="{{$shopeeItem['weight']}}"
        @elseif($category['name'] == "package_length")
            value="{{$shopeeItem['package_length']}}"
        @elseif($category['name'] == "package_width")
            value="{{$shopeeItem['package_width']}}"
        @elseif($category['name'] == "package_height")
            value="{{$shopeeItem['package_height']}}"
        @elseif($category['name'] == "quantity")
            value="{{(isset($shopeeItem['_variation']) && $shopeeItem['_variation']?$shopeeItem['_variation']['stock']:$shopeeItem['stock'])}}"
        @elseif($category['name'] == "price")
            value="{{(isset($shopeeItem['_variation']) && $shopeeItem['_variation']?$shopeeItem['_variation']['original_price']:$shopeeItem['original_price'])}}"
        @elseif($category['name'] == "SellerSku")
            value="{{trim($shopeeItem['item_sku'].' '.(isset($shopeeItem['_variation']) && $shopeeItem['_variation']? $shopeeItem['_variation']['variation_sku'] : '' ))}}"
        @elseif($category['name'] == "brand")
            @foreach($shopeeItem['attributes'] as $attr)
                @if($attr['attribute_name'] == "Brand")
                    value="{{$attr['attribute_value']}}"
                @endif
            @endforeach
        @endif
        />
        @elseif($category['input_type'] == "richText")
            <textarea name="{{$category['name']}}[{{$inputArrayNumber}}]" class="form-control"
            @if($category['is_mandatory'])
            required
            @endif

            
            />@if($category['name'] == "short_description"){{$shopeeItem['description']}}@endif</textarea>
        @elseif(in_array($category['input_type'],["multiEnumInput","singleSelect","multiSelect","enumInput"]))
            <select 
                @if(in_array($category['input_type'],["multiEnumInput","multiSelect"]))
                name="{{$category['name']}}[{{$inputArrayNumber}}][]" 
                @else
                name="{{$category['name']}}[{{$inputArrayNumber}}]" 
                @endif
                class="form-control
                @if($category['name'] == "brand")
                    is-brand
                @endif
                "
                @if(in_array($category['input_type'],["multiEnumInput","multiSelect"]))
                    multiple
                @endif

                @if($category['is_mandatory'])
                required
                @endif
            />
                <?php
                    if($category['name'] == "brand"){
                        $options = $brands;
                    }else{
                        $options = $category['options'];
                    }
                ?>
                @foreach($options as $option)

                    <option
                    @if($category['name'] == "Hazmat" && $option['name'] == "None")
                        selected
                    @elseif($category['name'] == "warranty_type" && $option['name'] == "No Warranty")
                    selected
                    @elseif($category['name'] == "pack_type" && $option['name'] == "Single")
                    selected
                    @elseif($category['name'] == "brand" && $option == "No Brand")
                    selected 
                    @endif
                    
                    >
                    @if($category['name'] == "brand")
                        {{$option}}
                    @else
                        {{$option['name']}}
                    @endif
                </option>
                @endforeach
            </select>
        @elseif($category['input_type'] == "img")
            @if($category['name'] == "__images__")
                @for($i = 0;$i < 7; $i++)
                @if(isset($shopeeItem['images'][$i]))
                <img src="{{$shopeeItem['images'][$i]}}_tn" style="object-fit:contain;width:50px;height:50px;">
                <input type="hidden" name="{{$category['name']}}[{{$inputArrayNumber}}][]" value="{{$shopeeItem['images'][$i]}}">
                @endif
                @endfor
            @endif
        @else
        <?php
            throw new Exception("No input available for ".$category['input_type']);
        ?>
        @endif
    </div>
</div>

@endforeach