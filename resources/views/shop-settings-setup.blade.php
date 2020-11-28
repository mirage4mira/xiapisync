@extends('dashboard.authBase')

@section('content')

<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card" style="height:100% !important;">
        <div class="card-body">
          <h2 class="card-title">
            Shop Configuration
          </h2>
          <p class="card-subtitle mb-2 text-muted">Setting up your shop setting. Your shop is about to be ready.</p>
          <div class="row" style="margin-top:30px">
            <div class="nav-tabs-boxed" style="width:100%">
              <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#home-1" role="tab" aria-controls="home" aria-selected="false">Stock</a></li>
              </ul>
              <div class="tab-content" style="height:100%">
                <div class="tab-pane active " id="home-1" role="tabpanel">
                  <div class="row">
                    <div class="col-12">
                    <small>Setting default value for existing and future new products. You can always change it later at {{URL::to('/inventory')}}</small>
                    </div>
                  </div>
                  <div class="row mt-4">
                    <div class="col-6 border-right">
                      <div class="form-group row ">
                        <label class="col-md-8 col-form-label" for="text-input">% of COGS from Sales <i class="fa fa-question-circle" aria-hidden="true" data-toggle="popover" data-content="This value will determine how much profit you make from sales" data-placement="top"></i></label>
                        <div class="col-md-4">
                          <input class="form-control" type="number" name="default_cogs_percentage" min="0" max="100" step="1" placeholder="70" autocomplete="off" value="70">
                        </div>
                      </div>
                      <div class="form-group row full-width">
                        <label class="col-md-8 col-form-label" for="text-input">Preparation Cost ($) <i class="fa fa-question-circle" aria-hidden="true" data-toggle="popover" data-content="Cost to prepare products for shipment, but exclude shipping fee (eg., packing cost, labour cost)" data-placement="top"></i></label>
                        <div class="col-md-4">
                          <input class="form-control" type="number" name="default_prep_cost" placeholder="1" min="0" step="0.1" autocomplete="off" value="1">
                        </div>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="form-group row full-width">
                        <label class="col-md-8 col-form-label" for="text-input">Days to Replenish Stock <i class="fa fa-question-circle" aria-hidden="true" data-toggle="popover" data-content="Days required to replenish stock" data-placement="top"></i></label>
                        <div class="col-md-4">
                          <input class="form-control" type="number" name="default_days_to_supply" placeholder="30" min="0" step="1" onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                  title="Numbers only" autocomplete="off" value="30">
                        </div>
                      </div>
                      <div class="form-group row full-width">
                        <label class="col-md-8 col-form-label" for="text-input">Safely Stock Quantity <i class="fa fa-question-circle" aria-hidden="true" data-toggle="popover" data-content="Extra quantity of stock to prevent out of stock" data-placement="top"></i></label>
                        <div class="col-md-4">
                          <input class="form-control" type="number" name="default_safety_stock" placeholder="50" min="0" step="1" onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                  title="Numbers only" autocomplete="off" value="50">
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="mt-4 row">
                    <div class="col-10">
                      
                    </div>
                    <div class="col-2">
                      <button class="btn btn-primary float-right" id="stock-config-btn">Next</button>
                    </div>
                  </div>
                </div>
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
<script src="{{ asset('js/main.js') }}" ></script>
<script>
  $(function(){
    $('[data-toggle="popover"]').popover({trigger:'hover'});

    $('#stock-config-btn').click(function(){
      setShopSettings(this)
    });
  });

  function setShopSettings(ele){

    ele = $(ele);
    ele.addClass('loading');

    var data = {};
    data._token = CSRF_TOKEN;

    if(ele.attr('id') == 'stock-config-btn'){
      data.default_cogs_percentage = $('input[name="default_cogs_percentage"]').val(); 
      data.default_prep_cost = $('input[name="default_prep_cost"]').val();
      data.default_days_to_supply = $('input[name="default_days_to_supply"]').val();
      data.default_safety_stock = $('input[name="default_safety_stock"]').val();
    }
    
    return $.ajax({
      async: true,
      type: 'POST',
      url: '/shop-settings-setup',
      data: data,
      success: function(data) {
        $(location).attr('href', '/');
      },
      error: ajaxErrorResponse,
      complete:function(){
        ele.removeClass('loading');
      }
    });
  }
</script>
@endsection