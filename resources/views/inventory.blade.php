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
                                  <tr>
                                    <td class="d-flex"><img src="/gifs/loading.gif"><div>Nike shoes Nike shoes Nike shoes Nike shoes Nike shoes Nike shoes Nike shoes</div></td>
                                    <td class="text-center">123334</td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>

                                    <td class="text-center">5%</td>
                                    <td class="text-center">5%</td>
                                  </tr>
                                  <tr>
                                    <td>Nike shoes Nike shoes Nike shoes Nike shoes Nike shoes Nike shoes Nike shoes</td>
                                    <td class="text-center">123334</td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>
                                    <td class="text-center" contenteditable='true'></td>

                                    <td class="text-center">5%</td>
                                    <td class="text-center">5%</td>
                                  </tr>
                                </tbody>
                              </table>
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
    <script src="{{ asset('js/main.js') }}"></script>

    <script>



      $(document).ready( function () {
        $('.inventory-table').DataTable({
        "dom": '<"pull-left"f><"pull-right"l>tip'
    });
      } );
    </script>
@endsection
