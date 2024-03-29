<Request>
    <Product>
      <Skus>
          @foreach($items as $item)
            <Sku>
                <ItemId>{{$item['item_id']}}</ItemId>
                <SkuId>{{$item['sku_id']}}</SkuId>
                <SellerSku>{{$item['seller_sku']}}</SellerSku>
                @if(isset($item['price']))
                    <Price>{{$item['price']}}</Price>
                @endif
                @if(isset($item['sale']))
                    <SalePrice>{{$item['sale']['price']}}</SalePrice>
                    <SaleStartDate>{{$item['sale']['start_date']}}</SaleStartDate>
                    <SaleEndDate>{{$item['sale']['end_date']}}</SaleEndDate>
                @endif
                @if(isset($item['latest_quantity']))
                    <Quantity>{{$item['latest_quantity']}}</Quantity>
                @endif
            </Sku>
            @endforeach
      </Skus>
    </Product>
  </Request>