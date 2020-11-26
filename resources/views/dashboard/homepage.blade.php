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
                      <input type="text" name="product" list="products-selection" class="form-control" placeholder="Search By Products"/>
                      <datalist id="products-selection">
                          <option></option>
                          <option value="224">123</option>
                      </datalist>
                    </div>
                    <div class="col-3 d-flex flex-row">
                      <input id="date1" onchange="reloadGraphData(this);" class="form-control" placeholder="Date Range">
                      <input id="date2" style="visibility:hidden;width:0;">
                    </div>
                    <div class="col-sm-4 d-none d-md-block">
                      </button>
                      <div class="btn-group btn-group-toggle float-right mr-3 date-selection-btn-group" data-toggle="buttons">
                        <label class="btn btn-outline-secondary">
                          <input id="option1" type="radio" name="options" autocomplete="off" onchange="reloadGraphData(this);"> 30 Days
                        </label>
                        <label class="btn btn-outline-secondary">
                          <input id="option2" type="radio" name="options" onchange="reloadGraphData(this);"> 3 Months
                        </label>
                        <label class="btn btn-outline-secondary">
                          <input id="option3" type="radio" name="options" onchange="reloadGraphData(this);"> 1 Year
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
                      <div class="text-muted">Net Sales</div><strong id="sales"></strong>

                    </div>
                    <div class="col-sm-12 col-md mb-sm-2 mb-0">
                      <div class="text-muted">Gross Margin</div><strong id="profit"></strong>
                    </div>
                    <div class="col-sm-12 col-md mb-sm-2 mb-0">
                      <div class="text-muted">Orders</div><strong id="orders"></strong>
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
    <script src="{{ asset('js/main.js') }}" ></script>
    <script>

    var status = 'ALL';
    var start_date = Date.today().addDays(-29).toString(AJAX_DATE_FORMAT);
    var end_date = Date.today().toString(AJAX_DATE_FORMAT);
    var selectedProduct = null;
    var ordersEsrowData;
    var productsData;

    var display_sales_graph = 0;

    $(function() {

      getOrdersEscrowData();
      getProductsData().then(function(data){setProductsSelection(data)});
      initGraphDatepicker();
    });

    function setProductsSelection(data){
      $('#products-selection').empty();
      $('#products-selection').append(`<option value=" "></option>`);
      data.forEach(function(content){
        $('#products-selection').append(`<option data-product-id="${content['item']['item_id']}">${content['item']['name']}`);
        console.log($('#products-selection'));
        content['item']['variations'].forEach(function(variation){
          $('#products-selection').append(`<option value="${content['item']['name']}" data-product-id="${content['item']['item_id']}" data-variation-id="${variation['variation_id']}">${variation['name']}`);
        });
      })
    }
    
    function reloadGraphData(obj){
      var obj = $(obj);
      var objId = obj.attr('id'); 
      if(objId === "date1"){
        var dateRange = obj.val().split('-');
        if(dateRange.length !== 2) return;
        var _start_date = Date.parse(dateRange[0]); 
        var _end_date = Date.parse(dateRange[1]); 
        if(!_start_date || !_end_date)return;
        $('.date-selection-btn-group').children('label').each(function(){
          $(this).removeClass('active');
        });
      }else if(objId === "option1" || objId === "option2" || objId === "option3"){
        $('#date1').val("");
        _end_date = Date.today();
        if(objId  === "option1")_start_date = Date.today().addDays(-30);   
        if(objId  === "option2")_start_date = Date.today().addMonths(-3);   
        if(objId  === "option3")_start_date = Date.today().addYears(-1);   
      }

      start_date = _start_date.toString(AJAX_DATE_FORMAT);
      end_date = _end_date.toString(AJAX_DATE_FORMAT);
      addLoadingToGraph();
      getOrdersEscrowData().then(function(){loadGraphData()});
    }

    function addLoadingToGraph(){
      if(!$('.loading-modal').hasClass("loading"))$('.loading-modal').addClass("loading");
    }

    function getProductsData(){
      return $.ajax({
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
    }

    function getOrdersEscrowData(){
      return $.ajax({
        async: true,
        type: 'POST',
        url: '{{URL::to("/get-orders-esrow-detail")}}',
        data: {_token: "{{ csrf_token() }}",status,start_date,end_date},
        success: function(data) {
          ordersEsrowData = data;
          console.log(data);
        },
        error: ajaxErrorResponse
      });
    }

    function timeToDisplaySalesGraph(){ 

      display_sales_graph += 1;
      if(display_sales_graph = 2){
        loadGraphData();
      }
    }

    function loadGraphData(){
      
      var dates = [];
      var labels = [];
      var sales = 0;
      var profit = 0;
      var max = 0;

      var temp_date = Date.parse(start_date);
      while(temp_date.compareTo(Date.parse(end_date).addDays(1)) === -1){
        dates.push({'start_date':new Date(temp_date).clearTime(),'end_date': new Date(temp_date).addDays(1).clearTime().addMilliseconds(-1)});
        labels.push(temp_date.toString('M/d'));
        temp_date = temp_date.addDays(1);
      }    

      var salesData = new Array(labels.length).fill(0);
      var profitData = new Array(labels.length).fill(0);
      var orders = 0;

      dates.forEach(function(dateRange,key){
        ordersEsrowData.forEach(function(result){
          if(new Date(result['order']['update_time'] * 1000).between(dateRange['start_date'],dateRange['end_date'])){
            salesData[key] += parseFloat(result['order']['income_details']['total_amount']);
            sales += salesData[key];

            if(salesData[key] > max)max = salesData[key];
            
            profitData[key] += (parseFloat(result['order']['income_details']['total_amount'] - parseFloat(result['order']['income_details']['seller_transaction_fee']) - parseInt(result['order']['income_details']['voucher'])));
            profit += profitData[key];
            if(profit[key] > max)max = profit[key];
          }
        });
      });

      $('#main-chart').empty();
      const mainChart = new Chart(document.getElementById('main-chart'), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Sales',
              backgroundColor: coreui.Utils.hexToRgba(coreui.Utils.getStyle('--info'), 10),
              borderColor: coreui.Utils.getStyle('--info'),
              pointHoverBackgroundColor: '#fff',
              borderWidth: 2,
              data: salesData.map(a => a.toFixed(2)),
            },
            {
              label: 'Profit',
              backgroundColor: 'transparent',
              borderColor: coreui.Utils.getStyle('--success'),
              pointHoverBackgroundColor: '#fff',
              borderWidth: 2,
              data: profitData.map(a => a.toFixed(2)),
            },
          ]
        },
        options: {
          maintainAspectRatio: false,
          legend: {
            display: false
          },
          scales: {
            xAxes: [{
              gridLines: {
                drawOnChartArea: false
              }
            }],
            yAxes: [{
              ticks: {
                beginAtZero: true,
                // maxTicksLimit: 5,
                // stepSize: Math.ceil(1000 / 5),
                // max: max*110/100
              }
            }]
          },
          elements: {
            point: {
              radius: 0,
              hitRadius: 10,
              hoverRadius: 4,
              hoverBorderWidth: 3
            }
          },
          tooltips: {
            intersect: true,
            callbacks: {
              labelColor: function(tooltipItem, chart) {
                return { backgroundColor: chart.data.datasets[tooltipItem.datasetIndex].borderColor };
              }
            }
          }
        }
      });

      $('#sales').html('$'+sales.toFixed(2));
      $('#profit').html('$'+profit.toFixed(2));
      $('#orders').html(ordersEsrowData.length);
      
      $('.loading-modal').removeClass("loading");
    }

    function initGraphDatepicker(){

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
        date1.change();
      });
    }

  </script>
@endsection
