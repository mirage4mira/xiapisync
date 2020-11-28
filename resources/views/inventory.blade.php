@extends('dashboard.base')

@section('content')
<style>
td, th {vertical-align:middle !important;font-size:0.7rem;}
td[contenteditable]:hover{border:1px solid black}
.pull-left{float:left!important;}
.pull-right{float:right!important;}
</style>
          <div class="container-fluid">
            <div class="fade-in">
              <!-- /.row-->
              <div class="row">
                <div class="col-md-12">
                  <div class="position-relative">
                  <div class="card">
                    <div class="card-header">Stocks</div>
                    <div class="card-body">
                        <div style="overflow-x:auto;">
                            <table class="table table-bordered inventory-table table-editable">
                                <thead>
                                  <tr>
                                    <th style="min-width:200px">Item</th>
                                    <th class="text-center">SKU</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Inbound</th>
                                    <th class="text-center">Available</th>
                                    <th class="text-center">Reserved</th>
                                    <th class="text-center">Days to supply</th>
                                    <th class="text-center">Price</th>
                                    <th class="text-center">Prep Cost</th>
                                    <th class="text-center">cost</th>
                                    <th class="text-center">Asset Value</th>
                                    <th class="text-center">% of asset Value</th>
                                  </tr>
                                </thead>
                                <tbody>
                                </tbody>
                              </table>
                        </div>
                      
                    </div>
                  </div>
                    <div class="inventory loading-modal loading"></div>
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



      $(document).ready( function () {

        getProductsData().then(function(data){
          inventoryTable.productsData = data;
          inventoryTable.loadInventoryTableData();
          inventoryTable.loadDataTable();
          inventoryTable.removeLoadingModal();
        });
      });

      class InventoryTable{
        productsData;

        loadInventoryTableData(){
          var datas = [];
          this.productsData.forEach(function(productData){
            if(productData.item.variations.length){
              productData.item.variations.forEach(function(variation){
                var data = {};
                data.image = productData.item.images[0];
                data.name = productData.item.name + " - " + variation.name;
                data.sku = productData.item.item_sku + " - " + variation.variation_sku;
                data.total = variation._append.inbound + variation.stock;
                data.inbound = variation._append.inbound;
                data.available = variation.stock;
                data.reserved = variation._append.safety_stock;
                data.days_to_supply = variation._append.days_to_supply;
                data.price = variation.price;
                data.prep_cost = variation._append.prep_cost;
                data.cost = variation._append.cost;
                data.asset_value = data.cost * data.total;
                datas.push(data);
              })
            }else{
              var data = {};
              data.image = productData.item.images[0];
              data.name = productData.item.name;
              data.sku = productData.item.item_sku;
              data.total = productData.item._append.inbound + productData.item.stock;
              data.inbound = productData.item._append.inbound;
              data.available = productData.item.stock;
              data.reserved = productData.item._append.safety_stock;
              data.days_to_supply = productData.item._append.days_to_supply;
              data.price = productData.item.price;
              data.prep_cost = productData.item._append.prep_cost;
              data.cost = productData.item._append.cost;
              data.asset_value = data.cost * data.total;
              datas.push(data);
            }
          });

          var totalAssetValue = 0;
          
          datas.forEach(function(data){
            totalAssetValue += data.asset_value;
          });

          datas.forEach(function(data){
            data.precentage_asset_value = Math.round(data.asset_value / totalAssetValue * 100,1);
          });
          this.loadTableRow(datas);  
          console.log(datas);
        }

        loadTableRow(datas){
          datas.forEach(function(data){
            $('table.inventory-table tbody').append(`
            <tr data-item-id="" data-variation-id="">
              <td class="d-flex"><img src="${data.image}_tn" width="50px"><div>${data.name}</div></td>
              <td class="text-center">${data.sku}</td>
              <td class="text-center" contenteditable='true'>${data.total}</td>
              <td class="text-center" contenteditable='true'>${data.inbound}</td>
              <td class="text-center" contenteditable='true'>${data.available}</td>
              <td class="text-center" contenteditable='true'>${data.reserved}</td>
              <td class="text-center" contenteditable='true'>${data.days_to_supply}</td>
              <td class="text-center" contenteditable='true'>${data.price}</td>
              <td class="text-center" contenteditable='true'>${data.prep_cost}</td>
              <td class="text-center" contenteditable='true'>${data.cost}</td>
              <td class="text-center">${data.asset_value}</td>
              <td class="text-center">${data.precentage_asset_value}</td>
            </tr>`);
          });
        }

        loadDataTable(){
          $('.inventory-table').DataTable({
          "dom": '<"pull-left"f><"pull-right"l>tip'
          });
        }

        removeLoadingModal(){
          $('.inventory.loading-modal').removeClass("loading");
        }
      }

      var inventoryTable = new InventoryTable();

    </script>
@endsection
