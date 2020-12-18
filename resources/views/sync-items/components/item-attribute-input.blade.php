<style>

</style>
@foreach($categoryAttr as $category)
<div class="col-4">
<div class="form-group">
    <label for=""
    @if($category['is_mandatory'])
    class="required"
    @endif
    >{{$category['label']}}</label>
    @if(in_array($category['input_type'],["text","numeric","date"]) || $category['name'] == "brand")
        <input
            @if($category['input_type'] == "text" || $category['name'] == "brand") 
            type="text" 
            @elseif($category['input_type'] == "numeric")
            type="number" 
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
            value="{{($shopeeItem['_variation']?$shopeeItem['_variation']['stock']:$shopeeItem['stock'])}}"
        @elseif($category['name'] == "price")
            value="{{($shopeeItem['_variation']?$shopeeItem['_variation']['original_price']:$shopeeItem['original_price'])}}"
        @elseif($category['name'] == "SellerSku")
            value="{{$shopeeItem['item_sku']}} {{($shopeeItem['_variation']? $shopeeItem['_variation']['variation_sku'] : '' )}}"
        @elseif($category['name'] == "brand")
            @foreach($shopeeItem['attributes'] as $attr)
                @if($attr['attribute_name'] == "Brand")
                    value="{{$attr['attribute_value']}}"
                @endif
            @endforeach
        @endif
        >
        @elseif($category['input_type'] == "richText")
            <textarea name="{{$category['name']}}[{{$inputArrayNumber}}]" class="form-control"
            @if($category['is_mandatory'])
            required
            @endif

            
            >
            
            @if($category['name'] == "short_description")
                {{$shopeeItem['description']}}
            @endif
        </textarea>
        @elseif(in_array($category['input_type'],["multiEnumInput","singleSelect","multiSelect","enumInput"]))
            <select 
                @if(in_array($category['input_type'],["multiEnumInput","multiSelect"]))
                name="{{$category['name']}}[{{$inputArrayNumber}}][]" 
                @else
                name="{{$category['name']}}[{{$inputArrayNumber}}]" 
                @endif
                class="form-control"
                @if(in_array($category['input_type'],["multiEnumInput","multiSelect"]))
                    multiple
                @endif

                @if($category['is_mandatory'])
                required
                @endif
            >
                @foreach($category['options'] as $option)

                    <option
                    @if($category['name'] == "Hazmat" && $option['name'] == "None")
                        selected
                    @elseif($category['name'] == "warranty_type" && $option['name'] == "No Warranty")
                        selected
                    @endif
                    
                    >{{$option['name']}}</option>
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