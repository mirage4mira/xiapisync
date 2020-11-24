@extends('dashboard.base')

@section('content')

          <div class="container-fluid">
            <div class="fade-in">
              <!-- /.row-->
              <div style="position:relative;">
              <div class="card">
                <div class="card-body">
                  <div class="row">
                    <div class="col-sm-1">
                      <h4 class="card-title mb-0">Sales</h4>
                    </div>
                    <div class="col-4">
                      <input type="text" name="product" list="productName" class="form-control" placeholder="Search By Products"/>
                      <datalist id="productName">
                          <option value="Pen">Pen</option>
                          <option value="Pencil">Pencil</option>
                          <option value="Paper">Paper</option>
                      </datalist>
                    </div>
                    <div class="col-3 d-flex flex-row">
                      <input id="date1" class="form-control" placeholder="Date Range">
                      <input id="date2" style="visibility:hidden;width:0;">
                    </div>
                    <div class="col-sm-4 d-none d-md-block">
                      </button>
                      <div class="btn-group btn-group-toggle float-right mr-3" data-toggle="buttons">
                        <label class="btn btn-outline-secondary">
                          <input id="option1" type="radio" name="options" autocomplete="off"> 30 Days
                        </label>
                        <label class="btn btn-outline-secondary active">
                          <input id="option2" type="radio" name="options" autocomplete="off" checked=""> 3 Months
                        </label>
                        <label class="btn btn-outline-secondary">
                          <input id="option3" type="radio" name="options" autocomplete="off"> 1 Year
                        </label>
                      </div>
                    </div>
                    <!-- /.col-->
                  </div>
                  <!-- /.row-->
                  <div class="c-chart-wrapper" style="height:300px;margin-top:40px;">
                    <canvas class="chart" id="main-chart" height="300"></canvas>
                  </div>
                </div>
                <div class="card-footer">
                  <div class="row text-center">
                    <div class="col-sm-12 col-md mb-sm-2 mb-0">
                      <div class="text-muted">Net Sales</div><strong>$12345.0</strong>

                    </div>
                    <div class="col-sm-12 col-md mb-sm-2 mb-0">
                      <div class="text-muted">Gross Margin</div><strong>$123.11</strong>
                    </div>
                    <div class="col-sm-12 col-md mb-sm-2 mb-0">
                      <div class="text-muted">Orders</div><strong>121</strong>
                    </div>
                  </div>
                </div>
              </div>
              <!-- /.card-->
                <div class="loading-modal loading"><!-- Place at bottom of page --></div>
              </div>
              

              <!-- /.row-->
              <div class="row">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">Traffic & Sales</div>
                    <div class="card-body">
                      <table class="table table-responsive-sm table-hover table-outline mb-0">
                        <thead class="thead-light">
                          <tr>
                            <th class="text-center">Date</th>
                            <th class="text-center">Orders</th>
                            <th class="text-center">Gross Sales</th>
                            <th class="text-center">-</th>
                            <th class="text-center">Shipping fee</th>
                            <th class="text-center">-</th>
                            <th class="text-center">Transaction fee</th>
                            <th class="text-center">-</th>
                            <th class="text-center">Discounts</th>
                            <th class="text-center">=</th>
                            <th class="text-center">Net Sales</th>
                            <th class="text-center">-</th>
                            <th class="text-center">COGS</th>
                            <th class="text-center">=</th>
                            <th class="text-center">Gross Margin</th>
                          </tr>
                        </thead>
                        <tbody>
                          <tr>
                            <td class="text-center">1 Aug 2020</td>
                            <td class="text-center">10</td>
                            <td class="text-center">1000</td>
                            <td class="text-center"></td>
                            <td class="text-center">10</td>
                            <td class="text-center"></td>
                            <td class="text-center">5</td>
                            <td class="text-center"></td>
                            <td class="text-center">2</td>
                            <td class="text-center"></td>
                            <td class="text-center">983</td>
                            <td class="text-center"></td>
                            <td class="text-center">500</td>
                            <td class="text-center"></td>
                            <td class="text-center">483</td>

                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
                <!-- /.col-->
              </div>
              <div class="row">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">Inventory alert</div>
                      <div class="card-body">
                        <div class="row">
                          <div class="card-body row text-center">
                            <div class="col">
                            <div class="text-value-xl">89k</div>
                            <div class="text-uppercase text-muted small">friends</div>
                            </div>
                            <div class="c-vr"></div>
                            <div class="col">
                            <div class="text-value-xl">459</div>
                            <div class="text-uppercase text-muted small">feeds</div>
                            </div>
                            <div class="c-vr"></div>
                            <div class="col">
                            <div class="text-value-xl">459</div>
                            <div class="text-uppercase text-muted small">feeds</div>
                            </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- /.col-->
              </div>
              <!-- /.row-->
              <div class="row">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">Inventory alert</div>
                    <div class="card-body">
                      <div class="row">
                        <div class="col-3">
                        <div class="c-callout c-callout-info"><small class="text-muted">Excessive Stock</small>
                        <div class="text-value-lg">9,123</div>
                        </div>
                        </div>

                        <div class="col-3">
                          <div class="c-callout c-callout-success"><small class="text-muted">Balanced Stock Level</small>
                          <div class="text-value-lg">22,643</div>
                          </div>
                          </div>

                        <div class="col-3">
                          <div class="c-callout c-callout-warning"><small class="text-muted">Low On Stock</small>
                          <div class="text-value-lg">20</div>
                          </div>
                          </div>

                          <div class="col-3">
                            <div class="c-callout c-callout-danger"><small class="text-muted">Out Of Stock</small>
                            <div class="text-value-lg">50</div>
                            </div>
                            </div>
                    </div>
                  </div>
                </div>
                <!-- /.col-->
              </div>
            </div>
          </div>

@endsection

@section('javascript')

    <script src="{{ asset('js/Chart.min.js') }}"></script>
    <script src="{{ asset('js/coreui-chartjs.bundle.js') }}"></script>
    <script src="{{ asset('js/main.js') }}" defer></script>
    <script>
    $(function() {

      // datepicker
      var date1 = $('#date1');
      var date2 = $('#date2');

      date1.datepicker().on('changeDate',function(){
        $(this).datepicker('hide');
      })
      date2.datepicker({
        orientation: "auto right"
      }).on('changeDate',function(){
        $(this).datepicker('hide');
      })
      

      date1.datepicker().on('hide',function(selectedDate){
        var date = $(this).datepicker("getDate");
                date2.datepicker("setDate", date);
                date2.datepicker( "show" );
      });

      date2.datepicker().on('hide',function(selectedDate){
        var date = $(this).datepicker({ dateFormat: 'mm/dd/yy' }).val();
        date1.val(date1.val() + " - " + date);
      });


      
      

      //get orders
      var status = 'ALL';
      var start_date = Date.today().addDays(-30).toString(AJAX_DATE_FORMAT);
      var end_date = Date.today().toString(AJAX_DATE_FORMAT);

      $.ajax({
          async: true,
          type: 'POST',
          url: '{{URL::to("/get-orders-esrow-detail")}}',
          data: {_token: "{{ csrf_token() }}",status,start_date,end_date},
          success: function(data) {
            console.log(data);
            timeToDisplaySalesGraph();
          },
          error: ajaxErrorResponse
      });
      
      //get products

      $.ajax({
          async: true,
          type: 'POST',
          url: '{{URL::to("/get-products-detail")}}',
          data: {_token: "{{ csrf_token() }}"},
          success: function(data) {
            console.log(data);
            timeToDisplaySalesGraph();
          },
          error: ajaxErrorResponse
      });
    });
    var display_sales_graph = 0;

    function timeToDisplaySalesGraph(){
      display_sales_graph += 1;
      if(display_sales_graph >= 2){
        $('.loading-modal').removeClass("loading");
      }
    }



    </script>
@endsection
