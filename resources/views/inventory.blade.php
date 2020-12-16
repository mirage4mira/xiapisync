@extends('dashboard.base')

@section('content')
<style>
  td,
  th {
    vertical-align: middle !important;
    font-size: 0.7rem;

  }
  tr:nth-child(2) th{
    padding: 0 !important;
  }
  tr:nth-child(2) th input{
    width: 100%;
  }
  td {
    padding: 0 !important;
  }

  td[contenteditable]:hover {
    border: 1px solid black
  }

  .pull-left {
    float: left !important;
  }

  .pull-right {
    float: right !important;
  }

  /* .dataTables_scrollHeadInner {
width: 100% !important;
}
.dataTables_scrollHeadInner table {
width: 100% !important;
}
table{
  width: 100% !important;
} */

  .inventory-table td div:not(:first-child) {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 50px;
    overflow: hidden;
  }

  /* .dataTables_scroll
{ 
  position: relative;
  height: 400px;
    overflow:auto;
} */
  /* .inventory-table,
.inventory-table th,
.inventory-table td {
  -webkit-box-sizing: content-box;
  -moz-box-sizing: content-box;
  box-sizing: content-box; */
  /* } */
  table.dataTable.table-striped.DTFC_Cloned tbody tr:nth-of-type(odd) {
    background-color: #F3F3F3;
}
table.dataTable.table-striped.DTFC_Cloned tbody tr:nth-of-type(even) {
    background-color: white;
}
</style>

<div class="container-fluid">
  <div class="fade-in">
    <!-- /.row-->
    <div class="row">
      <div class="col-md-12">
        <div class="position-relative">
          <div class="card">
            <div class="card-header">Stocks <button class="btn btn-info float-right" type="button" onclick="inventoryTable.showImportFromExcelModal()">Import From Excel</button></div>
            <div class="card-body">
              <div style="overflow:hidden;">
                <!-- <table class="inventory-table"> -->
                <table class="table table-bordered inventory-table table-striped table-editable">
                  <thead>
                    <tr>
                      <th style="min-width:250px;background-color:white;">Item</th>
                      <th class="text-center">Stock Status</th>
                      <th class="text-center">Total</th>
                      <th class="text-center">Inbound</th>
                      <th class="text-center">Available</th>
                      <th class="text-center">Reserved</th>
                      <th class="text-center">Days to Supply</th>
                      <th class="text-center">Price</th>
                      <th class="text-center">Cost</th>
                      <th class="text-center">Asset Value</th>
                      <th class="text-center">% of Asset Value</th>
                      <th class="text-center">Average Monthly Quantity</th>
                      <th class="text-center">Average Monthly Sales</th>
                      <th class="text-center">Average Monthly Profit</th>
                      <th class="text-center">ROI</th>
                      <th class="text-center">Rating Star</th>
                    </tr>
                    <tr id="filterrow">
                      <th style="min-width:250px;background-color:white;"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                      <th class="text-center"></th>
                    </tr>
                  </thead>
                  <tbody>
                  </tbody>
                  <tfoot>
                    <tr>
                      <th style="background-color:white;"></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      <th></th>
                      
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
          <div class="inventory loading-modal loading"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="cost-modal modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Cost</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-success mr-auto" onclick="inventoryTable.addNewCost()">Add Cost</button>
          <button type="button" class="btn btn-primary" onclick="inventoryTable.saveCost()">Save changes</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <div class="import-from-excel-modal modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Import from Excel</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-6 border-right d-flex align-items-center justify-content-center">
              <a href="/inventory/download-excel-template">
                <button type="button" class="btn btn-info btn-lg">Download Template</button>
              </a>
            </div>
            <div class="col-6">
              <form action="/inventory/import-excel" method="post" class="text-center" enctype="multipart/form-data"> @csrf <!-- <div class="d-flex align-items-center justify-content-center"> -->
                <input type="file" name="excel" required>
                <button type="submit" class="btn btn-primary mt-2">Import</button>
                <!-- </div> -->
              </form>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <!-- <button type="button" class="btn btn-success mr-auto" onclick="inventoryTable.addNewCost()">Add Cost</button> -->
          <!-- <button type="button" class="btn btn-primary" onclick="inventoryTable.saveCost()">Save changes</button> -->
          <!-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> -->
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
  $(document).ready(function() {

    getProductsData().then(function(data) {
      inventoryTable.productsData = data;
      inventoryTable.loadInventoryTableData();
      inventoryTable.loadDataTable();
      inventoryTable.removeLoadingModal();
    });


  });

  class InventoryTable {
    productsData;

    loadInventoryTableData() {
      var datas = [];
      this.productsData.forEach(function(productData) {
        if (productData.variations.length) {
          productData.variations.forEach(function(variation) {
            var data = {};
            data.item_id = productData.item_id;
            data.variation_id = variation.variation_id;
            data.image = productData.images[0];
            data.name = productData.name;
            data.variation_name = variation.name;
            data.sku = productData.item_sku + " " + variation.variation_sku;
            data.total = variation._append.inbound.reduce((a,b)=> a + b.pivot.quantity,0) + variation.stock;
            data.inbound = variation._append.inbound;
            data.available = variation.stock;
            data.reserved = variation._append.safety_stock;
            data.days_to_supply = variation._append.days_to_supply;
            data.price = variation.price;
            data.prep_cost = variation._append.prep_cost;
            data.cost = variation._append.cost;
            data.costs = variation._append.costs;
            data.asset_value = data.cost * data.total;
            data.wholesales = productData.wholesales;
            data.stock_id = variation._append.stock_id;
            data.currency = productData.currency;
            data.rating_star = productData.rating_star;
            data.stock_status = variation.stock?(variation._append.low_on_stock?"Low On Stock":"Good"):"Out Of Stock";
            data.additional_stock_required = variation._append.additional_stock_required;
            data.avg_monthly_profit = variation._append.avg_monthly_profit;
            data.avg_monthly_quantity = variation._append.avg_monthly_quantity;
            data.avg_monthly_sales = variation._append.avg_monthly_sales;
            data.avg_monthly_cost = variation._append.avg_monthly_cost;
            datas.push(data);
          })
        } else {

          var data = {};
          data.item_id = productData.item_id;
          data.variation_id = 0;
          data.image = productData.images[0];
          data.name = productData.name;
          data.sku = productData.item_sku;
          data.total = productData._append.inbound.reduce((a,b)=> a + b.pivot.quantity,0) + productData.stock;
          data.inbound = productData._append.inbound;
          data.available = productData.stock;
          data.reserved = productData._append.safety_stock;
          data.days_to_supply = productData._append.days_to_supply;
          data.price = productData.price;
          data.prep_cost = productData._append.prep_cost;
          data.cost = productData._append.cost;
          data.costs = productData._append.costs;
          data.asset_value = data.cost * data.total;
          data.wholesales = productData.wholesales;
          data.stock_id = productData._append.stock_id;
          data.currency = productData.currency;
          data.rating_star = productData.rating_star;
          data.stock_status = productData.stock?(productData._append.low_on_stock?"Low On Stock":"Good"):"Out Of Stock";
          data.additional_stock_required = productData._append.additional_stock_required;
          data.avg_monthly_profit = productData._append.avg_monthly_profit;
          data.avg_monthly_quantity = productData._append.avg_monthly_quantity;
          data.avg_monthly_sales = productData._append.avg_monthly_sales;
          data.avg_monthly_cost = productData._append.avg_monthly_cost;
          datas.push(data);
        }
      });

      var totalAssetValue = 0;

      datas.forEach(function(data) {
        totalAssetValue += data.asset_value;
      });

      datas.forEach(function(data) {
        data.precentage_asset_value = Math.round(data.asset_value / totalAssetValue * 100, 2);
      });
      this.loadTableRow(datas);
    }

    loadTableRow(datas) {
 
      datas.forEach(function(data) {

        var priceTooltipText;

        if (data.wholesales) {
          var priceTooltipTextArr = [];
          data.wholesales.forEach(function(pricing) {
            priceTooltipTextArr.push(pricing.min + " to " + pricing.max + " @" + data.currency + money(pricing.unit_price));
          });
          priceTooltipText = priceTooltipTextArr.join("&#013;");
        }

        var costTooltipText;
        if (data.costs) {
          var costTooltipTextArr = [];
          data.costs.forEach(function(cost, idx) {
            var startdateStr = (Date.parse(cost['from_date']).toString(DATEPICKER_DATE_FORMAT) == EARLIEST_DATE) ? "Past" : Date.parse(cost['from_date']).toString(DATEPICKER_DATE_FORMAT);
            if (data.costs[idx + 1]) {
              costTooltipTextArr.push(startdateStr + " to " + Date.parse(data.costs[idx + 1]['from_date']).addDays(-1).toString(DATEPICKER_DATE_FORMAT) + " @" + data.currency + money(cost['cost']));
            } else {
              costTooltipTextArr.push(startdateStr + " to Present" + " @" + data.currency + money(cost['cost']));
            }
          });
          costTooltipText = costTooltipTextArr.join("&#013;");
        }

        var inboundTooltipText;
        var inbound = 0;
        if (data.inbound.length) {
          var inboundTooltipTextArr = [];
          data.inbound.forEach(function(inboundOrder) {
            console.log(123);
            inboundTooltipTextArr.push(inboundOrder.pivot.quantity + " @ " + Date.parse(inboundOrder.payment_date).toString(DATEPICKER_DATE_FORMAT));
            inbound += parseFloat(inboundOrder.pivot.quantity);
          });
          inboundTooltipText = inboundTooltipTextArr.join("&#013;");
        }
        console.log(data.inbound);
        $('table.inventory-table tbody').append(`
            <tr data-item-id="${data.item_id}" data-variation-id="${data.variation_id}" data-stock-id="${data.stock_id}">
              <td><div class="d-flex">
                <img src="${data.image}_tn" style="object-fit:contain;width:50px;height:50px;">
                <div class="ml-1 d-flex flex-column align-items-start">
                  <strong style="height:80px;overflow:hidden;">${data.name}</strong>
                  <div>${data.variation_name || ''} ${data.sku ? '[' + data.sku + ']':'' }</div>
                </div>
              </div></td>
              <td><div class="text-center"><span class="badge badge-${data.stock_status == "Good"?"success":(data.stock_status == "Low On Stock"?"warning" : "danger")}">${data.stock_status}<br>${data.additional_stock_required?`- ${data.additional_stock_required}`:''}</span></div></td>
              <td><div class="text-center">${data.total}</div></td>
              <td><div class="text-center inbound-input" data-toggle="tooltip" data-placement="bottom" title="${inboundTooltipText}">${inbound}</div></td>
              <td contenteditable='true' name="available"><div class="text-center available-input">${data.available}</div></td>
              <td contenteditable='true' name="reserved"><div class="text-center reserved-input">${data.reserved}</div></td>
              <td contenteditable='true' name="days-to-supply"><div class="text-center days-to-supply-input">${data.days_to_supply}</div></td>
              <td ${priceTooltipText?'onclick="alert(\'Price for item with wholesale prices are uneditable!\')"':"contenteditable='true'"}  data-toggle="tooltip" data-placement="bottom" title="${priceTooltipText}" name="price"><div class="text-center price-input">${data.price}</div></td>
              <td contenteditable='false' data-toggle="tooltip" data-placement="bottom" title="${costTooltipText}"><div class="text-center" onclick="inventoryTable.costModal(this)">${data.cost}</div></td>
              <td><div class="text-center">${money(data.asset_value)}</div></td>
              <td><div class="text-center">${data.precentage_asset_value}</div></td>
              <td><div class="text-center">${data.avg_monthly_quantity}</div></td>
              <td><div class="text-center">${data.avg_monthly_sales}</div></td>
              <td><div class="text-center">${data.avg_monthly_profit}</div></td>
              <td><div class="text-center">${Math.round((data.avg_monthly_profit/data.avg_monthly_cost) * 100 || 0)}</div></td>
              <td><div class="text-center">${Math.round(data.rating_star * 100) / 100}</div></td>
            </tr>`);
      });

      $('.inventory-table').on('focus', '[contenteditable="true"]', function() {
        var $this = $(this);
        $this.data('before', $this.html());
        return $this;
      }).on('blur keyup paste input', '[contenteditable="true"]', function() {
        var $this = $(this);
        if ($this.data('before') !== $this.html()) {
          $this.data('before', $this.html());
          $this.trigger('change');
        }
        return $this;
      });
      $('.inventory-table td').on('blur', function(e) {
        if (e) {
          inventoryTable.updateStock(e);
        }
      });
    }


    updateStock(e) {
      var obj = $(e.target);
      console.log(obj);
      var tr = obj.closest('tr');
      var item_id = tr.data('item-id');
      var variation_id = tr.data('variation-id');
      var stock_id = tr.data('stock-id');
      var available = tr.find('.available-input').html();
      var reserved = tr.find('.reserved-input').html();
      var days_to_supply = tr.find('.days-to-supply-input').html();
      var price = tr.find('.price-input').html();

      $.ajax({
        async: false,
        type: 'POST',
        url: '/inventory/update-item',
        data: {
          _token: CSRF_TOKEN,
          data: JSON.stringify({
            update_price: $(obj.children()[0]).hasClass('price-input'),
            update_stock_count: $(obj.children()[0]).hasClass('available-input'),
            item_id: item_id,
            variation_id,
            stock_id,
            available,
            reserved,
            days_to_supply,
            price,
          })

        },
        success: function(data) {
          $.notify(`${_.startCase(obj.attr("name").replaceAll("-"," "))} Successfully Updated!`,"success");
        },
        error: ajaxErrorResponse
      })
    }

    costModal(obj) {
      obj = $(obj);
      var item_id = obj.closest('tr').data('item-id');
      var variation_id = obj.closest('tr').data('variation-id');

      var costs;
      this.productsData.forEach(function(product) {
        if (product.item_id == item_id) {
          if (variation_id) {
            product.variations.forEach(function(variation) {
              if (variation.variation_id == variation_id) {
                costs = variation._append.costs;
              }
            });
          } else {
            costs = product._append.costs;
          }
        }
      })
      $('.cost-modal .modal-body').empty();
      costs.forEach(function(cost, idx, costs) {
        this.addCostRow(cost, idx, costs);
      }, this);
      this.refreshCostDate();

      $('.cost-modal').data('item-id',item_id);
      $('.cost-modal').data('variation-id',variation_id);
      $('.cost-modal').modal('show');
    }

    addNewCost() {
      // var prev_start_date = $('.cost-modal .modal-body div.row:nth-last-child(1) input[name="start_date"]').val();
      // var today = Date.today().toString(DATEPICKER_DATE_FORMAT);
      // // var prev_end_date = $('.cost-modal .modal-body div.row:nth-last-child(1) input[name="start_date"]');
      // if(prev_start_date != today){
      this.addCostRow();
      this.refreshCostDate();
      // }else{
      // alert("Error! Date " + today + " already exist!");
      // }


    }

    addCostRow(cost, idx, costs) {

      if (!cost && !idx && !costs) {
        var prev_start_date = Date.parse($('.cost-modal .modal-body div.row:nth-last-child(1) input[name="start_date"]').val());
        var startDateStr = Date.today().compareTo(prev_start_date) == 1 ? Date.today().toString(DATEPICKER_DATE_FORMAT) : prev_start_date.addDays(1).toString(DATEPICKER_DATE_FORMAT);
        var cost_id = 0;
        var _cost = $('.cost-modal .modal-body div.row:nth-last-child(1) input[name="cost"]').val();
        var end_date = "Present";
        var first_row = false;
      } else {
        var startDateStr = Date.parse(cost.from_date).toString(DATEPICKER_DATE_FORMAT);
        if (startDateStr == EARLIEST_DATE) startDateStr = "Past";
        var cost_id = cost.id || 0;
        var _cost = cost.cost;
        var end_date = costs[idx + 1] ? Date.parse(costs[idx + 1].from_date).addDays(-1).toString(DATEPICKER_DATE_FORMAT) : "Present";
        var first_row = idx == 0 ? true : false;
      }

      $('.cost-modal .modal-body').append(`
        <div class="row my-1" data-stock-cost-id="${cost_id}">
          <div class="col-6"><div class="input-group"><input class="form-control" name="start_date" value="${startDateStr}" ${first_row?'readonly':''} onchange="inventoryTable.refreshCostDate()"><input class="form-control" name="end_date" value="${end_date}" readonly></div></div>
          <div class="col-4"><input  class="form-control" name="cost" type="number" min="0.01" step="0.01" value="${_cost}"></div>
          ${startDateStr == "Past" ? '': '<div class="col-2"><button class="btn btn-danger" type="button" onclick="inventoryTable.deleteStockCost(this)"><i class="fa fa-trash-o" aria-hidden="true" ></i></button></div>'}
        </div>
      `);
      // var start_date_input = $('.cost-modal .modal-body  div.row:nth-last-child(1) input[name="start_date"]');
      // var last_start_date_input = $('.cost-modal .modal-body  div.row:nth-last-child(2) input[name="start_date"]');  
      // var second_last_start_date_input = $('.cost-modal .modal-body  div.row:nth-last-child(3) input[name="start_date"]');  

      // if(last_start_date_input.length){
      //   var start_date = Date.parse(last_start_date_input.val() == "Past" ? EARLIEST_DATE : last_start_date_input.val()).addDays(1);
      //   start_date_input.datepicker({
      //     startDate: start_date,
      //   });
      //   if(second_last_start_date_input.length){
      //     console.log(second_last_start_date_input.val());
      //     var _start_date = Date.parse(second_last_start_date_input.val() == "Past" ? EARLIEST_DATE : second_last_start_date_input.val()).addDays(1);
      //     last_start_date_input.datepicker('destroy').datepicker({
      //       start_date : _start_date,
      //       endDate: start_date.addDays(-1),
      //     })
      //   }
      // }

    }


    saveCost() {
      var data = {};

      data.item_id = $('.cost-modal').data('item-id');
      data.variation_id = $('.cost-modal').data('variation-id');
      data._token = CSRF_TOKEN; 

      data.costs = [];
      $('.cost-modal .modal-body div.row').each(function(idx, obj) {
        var obj = $(obj);
        data.costs.push({
          'stock_cost_id': obj.data('stock-cost-id'),
          'from_date': obj.find('input[name="start_date"]').val() == "Past" ? EARLIEST_DATE : obj.find('input[name="start_date"]').val(),
          'cost': obj.find('input[name="cost"]').val()
        });
      });

      $.ajax({
        async: false,
        type: 'POST',
        url: '/inventory/update-cost',
        data,
        success: function(data) {
          $.notify(`Cost Successfully Updated!`,"success");
        window.location.reload();
        },
        error: ajaxErrorResponse
      })
    }
    deleteStockCost(obj) {
      var obj = $(obj);
      var row = obj.closest('div.row');
      row.remove();
      this.refreshCostDate();
    }

    refreshCostDate() {
      $('.cost-modal .modal-body div.row').each(function(idx, obj) {
        // console.log(idx,$('.cost-modal .modal-body div.row').length);
        // if(idx < $('.cost-modal .modal-body div.row').length){
        var row = $(obj);
        var end_date_input = row.find('[name="end_date"]');
        var end_date = Date.parse(end_date_input.val());
        // console.log(end_date);
        var next_start_date_input = $('.cost-modal .modal-body div.row:nth-child(' + (idx + 2) + ') input[name="start_date"]');
        var next_start_date = Date.parse(next_start_date_input.val());

        end_date_input.val(next_start_date ? next_start_date.addDays(-1).toString(DATEPICKER_DATE_FORMAT) : "Present");
        // }


        var start_date_input = $('.cost-modal .modal-body  div.row:nth-child(' + (idx + 1) + ') input[name="start_date"]');
        var last_start_date_input = $('.cost-modal .modal-body  div.row:nth-child(' + (idx) + ') input[name="start_date"]');
        var second_last_start_date_input = $('.cost-modal .modal-body  div.row:nth-child(' + (idx - 1) + ') input[name="start_date"]');

        if (last_start_date_input.length) {
          start_date_input.datepicker('destroy').datepicker({
            startDate: last_start_date_input.val() == "Past" ? Date.parse(EARLIEST_DATE).addDays(1).toString(DATEPICKER_DATE_FORMAT) : Date.parse(last_start_date_input.val()).addDays(1).toString(DATEPICKER_DATE_FORMAT),
          });
          if (second_last_start_date_input.length) {
            last_start_date_input.datepicker('destroy').datepicker({
              startDate: second_last_start_date_input.val() == "Past" ? Date.parse(EARLIEST_DATE).addDays(1).toString(DATEPICKER_DATE_FORMAT) : Date.parse(second_last_start_date_input.val()).addDays(1).toString(DATEPICKER_DATE_FORMAT),
              endDate: Date.parse(start_date_input.val()).addDays(-1).toString(DATEPICKER_DATE_FORMAT),
            });
          }
        }
        // var second_last_start_date_input = $('.cost-modal .modal-body  div.row:nth-child('+ (idx - 1) +') input[name="start_date"]');
        // console.log(idx,last_start_date_input,second_last_start_date_input);
        // last_start_date_input.datepicker('destroy').datepicker({

        // });
        // if (last_start_date_input.length) {
        //   var start_date = Date.parse(last_start_date_input.val() == "Past" ? EARLIEST_DATE : last_start_date_input.val()).addDays(1);
        //   start_date_input.datepicker({
        //     startDate: start_date,
        //   });
        //   if (second_last_start_date_input.length) {
        //     console.log(second_last_start_date_input.val());
        //     var _start_date = Date.parse(second_last_start_date_input.val() == "Past" ? EARLIEST_DATE : second_last_start_date_input.val()).addDays(1);
        //     last_start_date_input.datepicker('destroy').datepicker({
        //       start_date: _start_date,
        //       endDate: start_date.addDays(-1),
        //     })
        //   }
        // }
      })
    }

    loadDataTable() {

          // Setup - add a text input to each footer cell
      $('.inventory-table thead tr#filterrow th').each( function (idx,ele) {
          $(this).html( `<input type="text" class="form-control" onclick="stopPropagation(event);" ${idx == 0? `placeholder="Search"`:''}/>` );
      } );

      var api;
      var table = $('.inventory-table').dataTable({
        "dom": '<"pull-left"f><"pull-right"l>tip',
        "language": {
          "search": "Search Products:&nbsp;",

        },
        orderCellsTop: true,
        scrollY: 400,
        scrollX: true,
        scrollCollapse: true,
        paging: false,
        fixedColumns: true,
        fixedHeader: true,
        autoWidth: true,

        drawCallback: function(tfoot) {
          // $(".dataTables_scrollHeadInner").css({"width":"100%",'height':"100%"});
          // $('.inventory-table').css({"width":"100%"});
          api = this.api();
          // api.fixedHeader.adjust();
          // api.column(9).every(function() {
          //   var sum = this
          //     .data()
          //     .reduce(function(a, b) {
          //       var x = parseFloat(a);
          //       var y = parseFloat(b);
          //       return x + y;
          //     }, 0);
          //   console.log(sum); //alert(sum);
          //   $(this.footer()).html(sum);
          // });

          var sum = api.column(9).data().reduce(function(a,b){
            b = b.replace(/<[^>]*>?/gm, '').replace(/,/g ,"")
            // console.log(b);
            return a + parseFloat(b);

          },0);
          // console.log(sum);
          $(api.column(9).footer()).html(

            // money(1)
            money(sum)
          );


        },
        //         "initComplete": function (settings, json) {  
        // },
      });

      var urlParams = new URLSearchParams(window.location.search);
      var stock = urlParams.get('stock');
      
      if(stock){
        console.log(stock.replaceAll("-",' '));
        api.column(1).search(stock.replaceAll("-",' ')).draw();
      }

      $(".inventory-table thead input").on( 'keyup change', function () {
        api
            .column( $(this).parent().index()+':visible' )
            .search( this.value )
            .draw();
    } );
      // $(".inventory-table").wrap("<div style='overflow:auto; width:100%;position:relative;height:400px;'></div>");            

      // $('.inventory-table').wrap('<div class="dataTables_scroll" />');
      // jQuery('.dataTable').wrap('<div class="dataTables_scroll" />');
      // table.columns.adjust().draw();
      $(".dataTables_scrollFootInner").css({"box-sizing":"content-box"})
      $(".DTFC_LeftBodyLiner").css({"overflow":"hidden","width":"100%"})
      $('.dataTables_filter input[type="search"]').css({
        'width': '350px',
        'display': 'inline-block'
      });
    }
    removeLoadingModal() {
      // $('button.c-sidebar-minimizer.c-class-toggler').trigger('click');
      $('.inventory.loading-modal').removeClass("loading");
    }

    showImportFromExcelModal() {
      $('.import-from-excel-modal').modal('show');
    }
  }
  var inventoryTable = new InventoryTable();

  function stopPropagation(evt) {
		if (evt.stopPropagation !== undefined) {
			evt.stopPropagation();
		} else {
			evt.cancelBubble = true;
		}
	}

</script>
@endsection