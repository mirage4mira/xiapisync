<?xml version="1.0" encoding="UTF-8"?>

<Request>     
 <Product>         
  <PrimaryCategory>{{$item['primary_category_id']}}</PrimaryCategory>         
  <SPUId/>         
  <AssociatedSku/>         
  <Attributes>
    @foreach($item as $key => $itemValue) 
    @foreach($categoryAttr as $attr)
    @if($key == $attr['name'] && $attr['attribute_type'] == "normal")
    <{{$key}}>
    @if(is_array($itemValue))
        {{implode(",",$itemValue)}}
    @else
    {{$itemValue}}
    @endif
    </{{$key}}>
   @break   
   @endif
    @endforeach          
   @endforeach    
  </Attributes>         
  <Skus>             
   <Sku>
    @foreach($item as $key => $itemValue) 
    @foreach($categoryAttr as $attr)
    {{-- {{dd(trim('_',$attr['name']))}} --}}
    @if(($key == $attr['name'] || ($key == "Images" && $attr['name'] == "__images__")) && $attr['attribute_type'] == "sku")
    <{{$key}}>
    @if($key == "Images")
        @foreach($itemValue as $valueChild)
        <Image>{{$valueChild}}</Image>
        @endforeach
    @elseif(is_array($itemValue))
        {{implode(",",$itemValue)}}
    @else
    {{$itemValue}}
    @endif
    </{{$key}}>     
    @break   
    @endif
     @endforeach          
    @endforeach          
   </Sku>         
  </Skus>     
 </Product> 
</Request>
