@extends('dashboard.base')

@section('content')

<div class="container-fluid">
  <div class="fade-in">
    <!-- /.row-->
    <div style="position:relative;">
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-lg-1">
              <h4 class="card-title mb-0">Sales</h4>
            </div>
            <div class="col-4">
              <!-- <input type="text" name="product" list="products-selection" id="product" class="form-control" placeholder="Search By Products" onchange="salesGraph.reloadGraphData(this);this.blur();" onfocus="this.value=''" />
              <datalist id="products-selection">
                <option></option>
              </datalist> -->
              <select type="text" name="product" id="products-selection" class="form-control" placeholder="Search By Products" onchange="salesGraph.reloadGraphData(this);this.blur();" onfocus="this.value=''">
                <option>-- All --</option>
              </select>              
            </div>
            <div class="col-3 d-flex flex-row">
              <input id="date1" onchange="salesGraph.reloadGraphData(this);" class="form-control" placeholder="Date Range">
              <input id="date2" style="visibility:hidden;width:0;">
            </div>
            <div class="col-sm-4 d-none d-md-block">
              </button>
              <div class="btn-group btn-group-toggle float-right mr-2 date-selection-btn-group" data-toggle="buttons">
                <label class="btn btn-outline-secondary  active">
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
              <div class="text-muted">Sales</div><strong id="sales"></strong>

            </div>
            <div class="col-sm-12 col-md mb-sm-2 mb-0">
              <div class="text-muted">Esrow Amount</div><strong id="escrow"></strong>
            </div>
            <div class="col-sm-12 col-md mb-sm-2 mb-0">
              <div class="text-muted">Profit</div><strong id="profit"></strong>
            </div>
            <div class="col-sm-12 col-md mb-sm-2 mb-0">
              <div class="text-muted" id="orders_text">Orders</div><strong id="orders"></strong>
            </div>
          </div>
        </div>
      </div>
      <!-- /.card-->
      <div class="sales-graph loading-modal loading">
        <!-- Place at bottom of page -->
      </div>
    </div>


    <!-- /.row-->
    <div class="row">
      <div class="col-md-12">
        <div style="position:relative">
          <div class="card">
            <div class="card-header">Performance indicatiors</div>
            <div class="card-body">
              <table class="table table-responsive-sm table-hover table-outline mb-0 p-indicator-table">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center">Date</th>
                    <th class="text-center">Orders</th>
                    <th class="text-center">Sales</th>
                    <th class="text-center">Esrow Amount</th>
                    <!-- <th class="text-center">Cost</th> -->
                    <th class="text-center">Profit</th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
              </table>
            </div>
          </div>
          <div class="p-indicator loading-modal loading"></div>
        </div>
      </div>
      <!-- /.col-->
    </div>
    <!-- /.row-->
    <div class="row">
      <div class="col-md-12">
        <div style="position:relative">
          <div class="card">
            <div class="card-header">Inventory alert</div>
            <div class="card-body">
              <div class="row">
                <div class="col-6">
                  <div class="c-callout c-callout-warning"><small class="text-muted">Low On Stock</small>
                    <a href="inventory?stock=low-on-stock"><div class="text-value-lg" id="low-on-stock-text"></div></a>
                  </div>
                </div>
                
                <div class="col-6">
                  <div class="c-callout c-callout-danger"><small class="text-muted">Out Of Stock</small>
                    <a href="inventory?stock=out-of-stock"><div class="text-value-lg" id="no-stock-text"></div></a>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="inventory-alert loading-modal loading"></div>
          <!-- Place at bottom of page -->
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
<script src="{{ asset('js/main.js') }}"></script>
<script>
  var start_date = Date.today().addMonths(-3).toString(AJAX_DATE_FORMAT);
  var end_date = Date.today().toString(AJAX_DATE_FORMAT);
  var ordersEsrowData;
  var productsData;
  var status = 'PAID';


  $(function() {

    $('#products-selection').select2();

    getOrdersEscrowData(status, start_date, end_date).then(function(data) {
      ordersEsrowData = data;
      salesGraph.ordersEsrowData = ordersEsrowData;
      salesGraph.timeToDisplaySalesGraph();
      inventoryAlert.timeToDisplayInventoryAlert();
      pIndicator.timeToDisplayPIndicatorTable();
    });

    getProductsData().then(function(data) {

      productsData = data;
      salesGraph.setProductsSelection(data);
      salesGraph.timeToDisplaySalesGraph();
      inventoryAlert.timeToDisplayInventoryAlert();
      pIndicator.timeToDisplayPIndicatorTable();
    });

    initDoubleDatepicker('#date1', '#date2');
  });

  class PIndicator {
    display_p_indicator = 0;

    timeToDisplayPIndicatorTable() {
      this.display_p_indicator += 1;
      if (this.display_p_indicator == 2) {
        this.loadPIndicatorTable();
      }
    }

    loadPIndicatorTable() {
      $('.p-indicator.loading-modal').removeClass("loading");

      var dateRanges = [];
      dateRanges.push({
        name: 'Today',
        start_date: Date.today().clearTime(),
        end_date: Date.today().addDays(1).clearTime().addMilliseconds(-1)
      });
      dateRanges.push({
        name: 'Yesterday',
        start_date: Date.parse('yesterday').clearTime(),
        end_date: Date.parse('yesterday').addDays(1).clearTime().addMilliseconds(-1)
      });
      dateRanges.push({
        name: '7 Days',
        start_date: Date.today().addDays(-7).clearTime(),
        end_date: Date.today().addDays(1).clearTime().addMilliseconds(-1)
      });
      dateRanges.push({
        name: '30 Days',
        start_date: Date.today().addDays(-30).clearTime(),
        end_date: Date.today().addDays(1).clearTime().addMilliseconds(-1)
      });
      dateRanges.push({
        name: 'This Month',
        start_date: Date.today().moveToFirstDayOfMonth().clearTime(),
        end_date: Date.today().addDays(1).clearTime().addMilliseconds(-1)
      });
      dateRanges.push({
        name: 'Last Month',
        start_date: Date.today().addMonths(-1).moveToFirstDayOfMonth().clearTime(),
        end_date: Date.today().addMonths(-1).moveToLastDayOfMonth().addDays(1).clearTime().addMilliseconds(-1)
      });

      dateRanges.forEach(function(dateRange) {
        var orders = 0;
        var sales = 0;
        var escrow_amount = 0;
        var profit = 0;
        ordersEsrowData.forEach(function(order) {
          if (new Date(order['pay_time'] * 1000).between(dateRange['start_date'], dateRange['end_date'])) {
            orders += 1;
            sales += parseFloat(order['total_amount']);
            escrow_amount += parseFloat(order['escrow_amount']);
            profit += parseFloat(order['escrow_amount']) - parseFloat(order['estimated_shipping_fee']) - order.items.reduce(function(a, b) {
              return a + parseFloat(b._append.cost) * parseFloat(b.variation_quantity_purchased);
            }, 0);

          }
        }, this);
        $('.p-indicator-table tbody').append(`
          <tr>
           <td class="text-center">${dateRange.name}</td>
           <td class="text-center">${orders}</td>
           <td class="text-center">${money(sales)}</td>
           <td class="text-center">${money(escrow_amount)}</td>
           <td class="text-center">${money(profit)}</td>
          </tr>
        `);
      });

    }
  }
  var pIndicator = new PIndicator();

  class SalesGraph {

    start_date;
    end_date;
    status;
    item = null;
    ordersEsrowData;
    display_sales_graph = 0;
    mainChart;

    constructor(status, start_date, end_date) {
      this.status = status;
      this.start_date = start_date;
      this.end_date = end_date;
    }

    setProductsSelection(data) {

      $('#products-selection').empty();
      $('#products-selection').append(`<option value=" ">-- All --</option>`);
      data.forEach(function(content) {
        if (content['variations'].length == 0 || content['variations'].length > 1) {
          $('#products-selection').append(`<option data-item-id="${content['item_id']}">${content['name']} ${content['item_sku']}</option>`);
        }
        content['variations'].forEach(function(variation) {
          $('#products-selection').append(`<option data-item-id="${content['item_id']}" data-variation-id="${variation['variation_id']}">${content['name']} ${variation['name'] || ''} ${(content['item_sku'] + variation['variation_sku']) ? '[' + (content['item_sku'] + variation['variation_sku']) +']' : ''}</option>`);
        });
      })
    }

    reloadGraphData(obj) {
      var obj = $(obj);
      var objId = obj.attr('id');

      //if input is date
      if (objId === "date1") {
        var dateRange = obj.val().split('-');
        if (dateRange.length !== 2) return;
        var _start_date = Date.parse(dateRange[0]);
        var _end_date = Date.parse(dateRange[1]);
        if (!_start_date || !_end_date) return;
        $('.date-selection-btn-group').children('label').each(function() {
          $(this).removeClass('active');
        });

        this.start_date = _start_date.toString(AJAX_DATE_FORMAT);
        this.end_date = _end_date.toString(AJAX_DATE_FORMAT);
      }
      //if input is select date button
      else if (objId === "option1" || objId === "option2" || objId === "option3") {
        $('#date1').val("");
        _end_date = Date.today();
        if (objId === "option1") _start_date = Date.today().addDays(-30);
        if (objId === "option2") _start_date = Date.today().addMonths(-3);
        if (objId === "option3") _start_date = Date.today().addYears(-1);

        this.start_date = _start_date.toString(AJAX_DATE_FORMAT);
        this.end_date = _end_date.toString(AJAX_DATE_FORMAT);
      }
      //if input is product
      else if (objId === "products-selection") {
        var _item = $('#products-selection :selected');
        var itemId = _item.data('item-id');

        if (!itemId) {
          this.item = null;
          this.addLoadingToGraph();
          this.loadGraphData();
          return;
        }
        var variationId = _item.data('variation-id') || 0;
        this.item = {
          itemId,
          variationId
        };

        this.addLoadingToGraph();
        this.loadGraphData();
        return;
      }

      this.addLoadingToGraph();
      getOrdersEscrowData(status, this.start_date, this.end_date).then((data) => this.ordersEsrowData = data).then(() => this.loadGraphData());
    }

    addLoadingToGraph() {
      var selector = '.sales-graph.loading-modal';
      if (!$(selector).hasClass("loading")) $(selector).addClass("loading");
    }

    timeToDisplaySalesGraph() {
      this.display_sales_graph += 1;
      if (this.display_sales_graph == 2) {
        this.loadGraphData();
      }
    }

    loadGraphData() {

      var dates = [];
      var labels = [];
      var sales = 0;
      var escrowAmount = 0;
      var profit = 0;

      var temp_date = Date.parse(this.start_date);
      while (temp_date.compareTo(Date.parse(this.end_date).addDays(1)) === -1) {
        dates.push({
          'start_date': new Date(temp_date).clearTime(),
          'end_date': new Date(temp_date).addDays(1).clearTime().addMilliseconds(-1)
        });
        labels.push(temp_date.toString('M/d'));
        temp_date = temp_date.addDays(1);
      }

      var salesData = new Array(labels.length).fill(0);
      var escrowAmountData = new Array(labels.length).fill(0);
      var profitData = new Array(labels.length).fill(0);
      var orders = 0;
      var qty_sold = 0;

      dates.forEach(function(dateRange, key) {
        this.ordersEsrowData.forEach(function(result) {
          if (new Date(result['pay_time'] * 1000).between(dateRange['start_date'], dateRange['end_date'])) {
            if (this.item) {
              result['items'].forEach(function(orderItem) {
                if ((orderItem.item_id == this.item.itemId && orderItem.variation_id == this.item.variationId) || (orderItem.item_id == this.item.itemId && this.item.variationId == 0)) {
                  var quantity_purchased = parseFloat(orderItem['variation_quantity_purchased']);
                  var item_sales_amount = parseFloat(orderItem['variation_discounted_price']) * quantity_purchased;
                  var item_cost = orderItem._append.cost * quantity_purchased;
                  
                  salesData[key] += item_sales_amount;
                  sales += item_sales_amount;
                  // console.log(result['_esrow_detail']);
                  var fees = parseFloat(result['_escrow_detail']['income_details']['seller_transaction_fee']) + parseFloat(result['_escrow_detail']['income_details']['service_fee']);
                  var _escrow_amount = item_sales_amount - (item_sales_amount / result['_append']['item_amount'] * fees);
                  // var _escrow_amount = item_sales_amount - (()/ result['_append']['item_count']);
                  escrowAmount += _escrow_amount
                  escrowAmountData[key] += _escrow_amount;

                  profitData[key] += _escrow_amount - item_cost;
                  profit += _escrow_amount - item_cost;
                  qty_sold += quantity_purchased;
                }
              }, this);
            } else {
              var total_amount = parseFloat(result['total_amount']);
              var _escrow_amount = parseFloat(result['escrow_amount']);
              var cost = 0;
              // result['items'].forEach()
              sales += total_amount;
              salesData[key] += total_amount;

              escrowAmount += _escrow_amount;
              escrowAmountData[key] += _escrow_amount;

              var _profit = _escrow_amount - parseFloat(result['estimated_shipping_fee']) - result['items'].reduce(function(a, b) {
                return a + (parseFloat(b._append.cost) * parseFloat(b.variation_quantity_purchased));
              }, 0);
              profit += _profit
              profitData[key] += _profit;

              orders++;

            }
          }
        }, this);
      }, this);

      if (this.mainChart) {
        this.mainChart.destroy();
      }

      this.mainChart = new Chart(document.getElementById('main-chart'), {
        type: 'line',
        data: {
          labels: labels,
          datasets: [{
              label: 'Sales',
              backgroundColor: coreui.Utils.hexToRgba(coreui.Utils.getStyle('--info'), 10),
              borderColor: coreui.Utils.getStyle('--primary'),
              pointHoverBackgroundColor: '#fff',
              borderWidth: 2,
              data: salesData.map(a => a.toFixed(2)),
            },
            {
              label: 'Esrow Amount',
              backgroundColor: 'transparent',
              borderColor: coreui.Utils.getStyle('--info'),
              pointHoverBackgroundColor: '#fff',
              borderWidth: 2,
              data: escrowAmountData.map(a => a.toFixed(2)),
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
                return {
                  backgroundColor: chart.data.datasets[tooltipItem.datasetIndex].borderColor
                };
              }
            }
          }
        }
      });

      $('#sales').html('$' + money(sales));
      $('#escrow').html('$' + money(escrowAmount));
      $('#profit').html('$' + money(profit));

      if (this.item) {
        $('#orders_text').html('Quantity Sold');
        $('#orders').html(qty_sold);
      } else {
        $('#orders_text').html('Orders');
        $('#orders').html(orders);
      }

      $('.sales-graph.loading-modal').removeClass("loading");
    }
  }

  var salesGraph = new SalesGraph(status, Date.parse(end_date).addMonths(-1).toString(AJAX_DATE_FORMAT), end_date);


  class InventoryAlert {
    display_inventory_alert = 0;

    timeToDisplayInventoryAlert() {
      this.display_inventory_alert += 1;
      if (this.display_inventory_alert == 2) {
        this.loadInventoryAlert();
      }
    }

    loadInventoryAlert() {
      // var itemsThreeMonthsSoldQty = [];

      // ordersEsrowData.forEach(function(order) {
      //   order['items'].forEach(function(soldItem) {
      //     var existed = false;
      //     itemsThreeMonthsSoldQty.forEach(function(itemThreeMonthsSoldQty) {
      //       if (itemThreeMonthsSoldQty.item_id == soldItem.item_id && itemThreeMonthsSoldQty.variation_id == soldItem.variation_id) {
      //         itemThreeMonthsSoldQty.quantity_sold_in_3_months += soldItem.quantity_purchased;
      //         existed = true;
      //       }
      //     })
      //     if (existed === false) {
      //       itemsThreeMonthsSoldQty.push({
      //         item_id: soldItem.item_id,
      //         variation_id: soldItem.variation_id,
      //         quantity_sold_in_3_months: soldItem.quantity_purchased
      //       });
      //     }
      //   });
      // });

      // var itemsStockData = [];

      // productsData.forEach(function(productData) {
      //   if (productData.variations.length) {
      //     productData.variations.forEach(function(variation) {
      //       var itemStockData = {};
      //       itemStockData.item_id = productData.item_id;
      //       itemStockData.variation_id = variation.variation_id;
      //       itemStockData.inbound = variation._append.inbound;
      //       itemStockData.days_to_supply = variation._append.days_to_supply;
      //       itemStockData.safety_stock = variation._append.safety_stock;
      //       itemStockData.average_monthly_sold_qty = 0;
      //       itemStockData.current_stock = variation.stock;
      //       itemsStockData.push(itemStockData);
      //     });
      //   } else {
      //     var itemStockData = {};
      //     itemStockData.item_id = productData.item_id;
      //     itemStockData.variation_id = productData.variation_id;
      //     itemStockData.inbound = productData._append.inbound;
      //     itemStockData.days_to_supply = productData._append.days_to_supply;
      //     itemStockData.safety_stock = productData._append.safety_stock;
      //     itemStockData.average_monthly_sold_qty = 0;
      //     itemStockData.current_stock = productData['stock'];
      //     itemsStockData.push(itemStockData);
      //   }
      // });

      // var low_on_stock = 0;
      // var no_stock = 0;

      // itemsStockData.forEach(function(itemStockData) {

      //   itemsThreeMonthsSoldQty.forEach(function(itemThreeMonthsSoldQty) {
      //     if (itemStockData.item_id == itemThreeMonthsSoldQty.item_id && itemStockData.variation_id == itemThreeMonthsSoldQty.variation_id) {
      //       itemStockData.average_monthly_sold_qty = (itemThreeMonthsSoldQty.quantity_sold_in_3_months / 3);
      //     }
      //   });
      //   if ((itemStockData.current_stock - parseInt(itemStockData.safety_stock) + (itemStockData.inbound / parseInt(itemStockData.days_to_supply) * 30)) < itemStockData.average_monthly_sold_qty) {
      //     itemStockData.low_on_stock = true;
      //     low_on_stock += 1;
      //   } else {
      //     itemStockData.low_on_stock = false;
      //   }

      //   if (itemStockData.current_stock == 0) {
      //     no_stock += 1;
      //   }
      // });
      // console.log(itemsStockData);
      var low_on_stock = 0;
      var out_of_stock = 0;

      productsData.forEach(function(productData) {
        if (productData.variations.length) {
          productData.variations.forEach(function(variation) {
            if(variation.stock == 0)out_of_stock++;
            else if(variation._append.low_on_stock)low_on_stock++;
          });
        } else {
          if(productData.stock == 0)out_of_stock++;
          else if(productData._append.low_on_stock)low_on_stock++;
        }
      });

      $('#low-on-stock-text').html(low_on_stock);
      $('#no-stock-text').html(out_of_stock);

      $('.inventory-alert.loading-modal').removeClass("loading");
    }
  }

  var inventoryAlert = new InventoryAlert;
</script>
@endsection