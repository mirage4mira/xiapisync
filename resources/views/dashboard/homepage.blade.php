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
                      <input type="text" name="product" list="products-selection" id="product" class="form-control" placeholder="Search By Products" onchange="salesGraph.reloadGraphData(this);"/>
                      <datalist id="products-selection">
                          <option></option>
                          <option value="224">123</option>
                      </datalist>
                    </div>
                    <div class="col-3 d-flex flex-row">
                      <input id="date1" onchange="salesGraph.reloadGraphData(this);" class="form-control" placeholder="Date Range">
                      <input id="date2" style="visibility:hidden;width:0;">
                    </div>
                    <div class="col-sm-4 d-none d-md-block">
                      </button>
                      <div class="btn-group btn-group-toggle float-right mr-3 date-selection-btn-group" data-toggle="buttons">
                        <label class="btn btn-outline-secondary">
                          <input id="option1" type="radio" name="options" autocomplete="off" onchange="salesGraph.reloadGraphData(this);"> 30 Days
                        </label>
                        <label class="btn btn-outline-secondary">
                          <input id="option2" type="radio" name="options" onchange="salesGraph.reloadGraphData(this);"> 3 Months
                        </label>
                        <label class="btn btn-outline-secondary">
                          <input id="option3" type="radio" name="options" onchange="salesGraph.reloadGraphData(this);"> 1 Year
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
                      <div class="text-muted" id="orders_text">Orders</div><strong id="orders"></strong>
                    </div>
                  </div>
                </div>
              </div>
              <!-- /.card-->
              <div class="sales-graph loading-modal loading"><!-- Place at bottom of page --></div>
              </div>
              

              <!-- /.row-->
              <div class="row">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">Performance indicatiors</div>
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
              <!-- /.row-->
              <div class="row">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">Inventory alert</div>
                    <div class="card-body">
                      <div class="row">
                        <div class="col-4">
                        <div class="c-callout c-callout-info"><small class="text-muted">Excessive Stock</small>
                        <div class="text-value-lg">9,123</div>
                        </div>
                        </div>
                        <div class="col-4">
                          <div class="c-callout c-callout-warning"><small class="text-muted">Low On Stock</small>
                          <div class="text-value-lg">20</div>
                          </div>
                          </div>

                          <div class="col-4">
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

    var start_date = Date.today().addDays(-29).toString(AJAX_DATE_FORMAT);
    var end_date = Date.today().toString(AJAX_DATE_FORMAT);
    var ordersEsrowData;
    var productsData;
    var status = 'ALL';
    

    $(function() {

      getOrdersEscrowData(status,start_date,end_date).then(function (data){
        ordersEsrowData = data;
        salesGraph.ordersEsrowData = ordersEsrowData; 
        salesGraph.timeToDisplaySalesGraph();
      });
      
      getProductsData().then(function(data){
        productsData = data;
        salesGraph.setProductsSelection(data);
        salesGraph.timeToDisplaySalesGraph();
      });
      
      initDoubleDatepicker('#date1','#date2');
    });

    class SalesGraph {

      start_date;
      end_date;
      status;
      item = null;
      ordersEsrowData;
      display_sales_graph = 0;
      mainChart;

      constructor(status,start_date,end_date){
        this.status = status;
        this.start_date = start_date;
        this.end_date = end_date;
      }

      setProductsSelection(data){
        $('#products-selection').empty();
        $('#products-selection').append(`<option value=" "></option>`);
        data.forEach(function(content){
          $('#products-selection').append(`<option data-item-id="${content['item']['item_id']}" value="${content['item']['name']}">${content['item']['item_sku']}</option>`);
          content['item']['variations'].forEach(function(variation){
            $('#products-selection').append(`<option data-item-id="${content['item']['item_id']}" data-variation-id="${variation['variation_id']}" value="${content['item']['name']} - ${variation['name']}">${content['item']['item_sku']} - ${variation['variation_sku']}</option>`);
          });
        })
      }

      reloadGraphData(obj){
        var obj = $(obj);
        var objId = obj.attr('id'); 

        //if input is date
        if(objId === "date1"){
          var dateRange = obj.val().split('-');
          if(dateRange.length !== 2) return;
          var _start_date = Date.parse(dateRange[0]); 
          var _end_date = Date.parse(dateRange[1]); 
          if(!_start_date || !_end_date)return;
          $('.date-selection-btn-group').children('label').each(function(){
            $(this).removeClass('active');
          });

          this.start_date = _start_date.toString(AJAX_DATE_FORMAT);
          this.end_date = _end_date.toString(AJAX_DATE_FORMAT);
        }
        //if input is select date button
        else if(objId === "option1" || objId === "option2" || objId === "option3"){
          $('#date1').val("");
          _end_date = Date.today();
          if(objId  === "option1")_start_date = Date.today().addDays(-30);   
          if(objId  === "option2")_start_date = Date.today().addMonths(-3);   
          if(objId  === "option3")_start_date = Date.today().addYears(-1);
          
          this.start_date = _start_date.toString(AJAX_DATE_FORMAT);
          this.end_date = _end_date.toString(AJAX_DATE_FORMAT);   
        }
        //if input is product
        else if(objId === "product"){
          var _item = $('#products-selection option[value="' + obj.val() + '"]');
          var itemId =  _item.data('item-id');
          if(!itemId){
            this.item = null;
            this.addLoadingToGraph();
            this.loadGraphData();  
            return;
          }
          var variationId =  _item.data('variation-id') || 0;
          this.item = {itemId,variationId};
          this.addLoadingToGraph();
          this.loadGraphData();
          return;
        }

        this.addLoadingToGraph();
        getOrdersEscrowData(status,start_date,end_date).then((data) => this.ordersEsrowData = data).then(() => this.loadGraphData());
      }

      addLoadingToGraph(){
        var selector = '.sales-graph.loading-modal';
        if(!$(selector).hasClass("loading"))$(selector).addClass("loading");
      }

      timeToDisplaySalesGraph(){ 
        this.display_sales_graph += 1;
        if(this.display_sales_graph == 2){
          this.loadGraphData();
        }
      }

      loadGraphData(){
      
        var dates = [];
        var labels = [];
        var sales = 0;
        var profit = 0;
        var temp_date = Date.parse(this.start_date);
        while(temp_date.compareTo(Date.parse(this.end_date).addDays(1)) === -1){
          dates.push({'start_date':new Date(temp_date).clearTime(),'end_date': new Date(temp_date).addDays(1).clearTime().addMilliseconds(-1)});
          labels.push(temp_date.toString('M/d'));
          temp_date = temp_date.addDays(1);
        }    

        var salesData = new Array(labels.length).fill(0);
        var profitData = new Array(labels.length).fill(0);
        var orders = 0;
        var qty_sold = 0;

        dates.forEach(function(dateRange,key){
          this.ordersEsrowData.forEach(function(result){
            if(new Date(result['order']['update_time'] * 1000).between(dateRange['start_date'],dateRange['end_date'])){
              if(this.item){
                result['order']['items'].forEach(function(orderItem){
                  if(orderItem.item_id == this.item.itemId && orderItem.variation_id == this.item.variationId){
                    salesData[key] += parseFloat(orderItem['original_price']);
                    sales += salesData[key];
                    
                    profitData[key] += parseFloat(orderItem['discounted_price']);
                    profit += profitData[key];                
                    qty_sold += 1;
                  }
                },this);
              }else{
                salesData[key] += parseFloat(result['order']['income_details']['total_amount']);
                sales += salesData[key];

                profitData[key] += (parseFloat(result['order']['income_details']['total_amount'] - parseFloat(result['order']['income_details']['seller_transaction_fee']) - parseInt(result['order']['income_details']['voucher'])));
                profit += profitData[key];
              }
            }
          },this);
        },this);
        
        if(this.mainChart){
          this.mainChart.destroy();
        }
        this.mainChart = new Chart(document.getElementById('main-chart'), {
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

        if(this.item){
          $('#orders_text').html('Quantity Sold');
          $('#orders').html(qty_sold);
        }else{
          $('#orders_text').html('Orders');
          $('#orders').html(this.ordersEsrowData.length);
        }
        
        $('.sales-graph.loading-modal').removeClass("loading");
      }
    }

    var salesGraph = new SalesGraph(status,start_date,end_date);



  </script>
@endsection
