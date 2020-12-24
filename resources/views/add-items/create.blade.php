@extends('dashboard.base')

@section('content')
<style>
  a[id^="tab-switch-"]:hover {
    cursor: pointer;
  }

  .notactive {
    pointer-events: none;
    cursor: default;
  }
</style>
<?php

function HandleArray(&$value)
{

  if (isset($value['children']) && is_array($value['children'])) {
    //Do something with all array
    if($value['name'] == "Packaging & Cartons"){
      Log::alert($value);
    }
    echo "<optgroup label='" . $value['name'] . "'>";
    array_walk($value['children'], 'HandleArray');
    echo "</optgroup>";
  } else {
    echo "<option value='" . $value['category_id'] . "'>" . $value['name'] . "</option>";
    //do something with your scalar value
  }
}
?>
<select class="form-control category-select d-none" style="width: 100%;" id="cat-select-template" onchange="setCategoryInput(this)">
  <?php
  array_walk($categories, 'HandleArray');
  ?>
</select>

<div class="container-fluid">
  <div class="fade-in">
    <!-- /.row-->
    <div class="row">
      <div class="col-md-12">
        <div class="position-relative">
          <div class="card">
            <div class="card-header">Sync items with Lazada</div>
            <div class="card-body">
            <form action="/export-items-to-lazada" id="items-form" method="post">
                @csrf
                <input type="hidden" name="shop_id" value="{{$shop_id}}">
                @foreach($shopeeItemsChunk as $key => $shopeeItems)
                <div id="items-tab-{{$key}}" class="d-none">
                  @foreach($shopeeItems as $item)
                  @if(!empty($item['variations']))
                  @foreach($item['variations'] as $variation)
                  <div class="row item-row" data-item-id="{{$item['item_id']}}" data-variation-id="{{$variation['variation_id']}}">
                    <div class="col-12">
                      <?php $sku = trim($item['item_sku'] . ' ' . $variation['variation_sku']);
                      if ($sku) $sku = '[' . $sku . ']' ?>
                      <p>{{$item['name']}}<br><small>{{$variation['name']}} {{$sku}}</small></p>
                    </div>
                    @include('sync-items.includes.item-attribute-input')
                  </div>
                  @endforeach
                  @else
                  <div class="row item-row" data-item-id="{{$item['item_id']}}" data-variation-id="0">
                    <div class="col-12">
                      <?php $sku = trim($item['item_sku']);
                      if ($sku) $sku = '[' . $sku . ']' ?>
                      <p>{{$item['name']}}<br><small>{{$sku}}</small></p>
                    </div>
                    @include('sync-items.includes.item-attribute-input')
                  </div>
                  @endif
                  <hr>
                  @endforeach
                </div>
                @endforeach

                  <input type="submit" class="btn btn-primary" value="Save">
              </form>
              <nav aria-label="Page navigation example" style="margin-top:3rem;">
                <ul class="pagination">
                  <li class="page-item"><a class="page-link" onclick="switchToPrevPane()" id="tab-switch-prev">Previous</a></li>
                  @foreach($shopeeItemsChunk as $key => $d)
                  <li class="page-item"><a class="page-link" id="tab-switch-{{$key}}" onclick="switchPane('{{$key}}')">{{$key + 1}}</a></li>
                  <!-- <li class="page-item"><a class="page-link" id="tab-switch-{{$key}}" onclick="switchPane('{{$key}}')">{{$key + 1}}</a></li> -->
                  @endforeach
                  <li class="page-item"><a class="page-link" onclick="switchToNextPane()" id="tab-switch-next">Next</a></li>
                </ul>
              </nav>
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
    var activeTab = null;
    var maxTab = '{{count($shopeeItemsChunk) - 1}}';
    $(function() {
      $('.item-select-div').each(function(idx, obj) {
        var clone = $('#cat-select-template').clone().attr('id', null).removeClass('d-none');
        $(obj).prepend(clone);
        clone.select2();
      });
      switchPane(0);
      // $('.category-select').select2();
      // getProductsData().then(function(data){
      //   $('#items-div').pagination({
      //   dataSource:data,
      //   callback:function(data){
      //     $('#items-div').append(123);
      //   } 
      //   });
      // })

    });

    function switchPane(tabId) {
      $('div[id^="items-tab-"').each(function(idx, obj) {
        $(obj).addClass('d-none');
      });

      $('#items-tab-' + tabId).removeClass('d-none');

      $('a[id^="tab-switch-"').each(function(idx, obj) {
        if ($(obj).attr('id') == 'tab-switch-' + tabId)
          $(obj).parent('.page-item').addClass('active');
        else {
          $(obj).parent('.page-item').removeClass('active');
        }
      });

      activeTab = tabId;

      if (activeTab == 0) $('#tab-switch-prev').addClass('notActive');
      else if (activeTab == maxTab) $('#tab-switch-next').AddClass('notActive');
      else $('#tab-switch-prev', '#tab-switch-next').removeClass('notActive');
    }

    function switchToPrevPane() {
      switchPane(activeTab - 1);
    }

    function switchToNextPane() {
      switchPane(activeTab + 1);
    }

    var i = 0;
    function setCategoryInput(obj) {
      $(obj).attr('disabled',true);

      $(obj).siblings(".select-loading-div").html("loading...");

      return $.ajax({
        async: true,
        type: 'GET',
        url: '/get-category-attribute',
        data: {
          _token: CSRF_TOKEN,
          category_id: $(obj).val(),
          item_id: $(obj).closest('.item-row').data('item-id'),
          variation_id: $(obj).closest('.item-row').data('variation-id'),
          shop_id: '{{$shop_id}}',
          i:i++,
        },
        success: function(data) {
          $(obj).closest('.item-row').find('.item-attr-div').html(data);
          $(obj).closest('.item-row').find('.is-brand').select2({
            minimumInputLength: 2,
          });
        },
        error: ajaxErrorResponse,
        complete:function(){
          $(obj).attr('disabled',false);
          $(obj).siblings(".select-loading-div").html("")
        }
      });
    }
    </script>
  @endsection