@extends('dashboard.base')

@section('content')

          <div class="container-fluid">
            <div class="fade-in">
              <!-- /.row-->
              <div class="row">
                <div class="col-md-12">
                  <div class="card">
                    <div class="card-header">Traffic & Sales</div>
                    <div class="card-body">
                        <div style="overflow-x:scroll;">
                            <table class="inventory-table">
                                <thead class="thead-light">
                                  <tr>
                                    <th class="text-center">Image</th>
                                    <th class="text-center">Item</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Inbound</th>
                                    <th class="text-center">Available</th>
                                    <th class="text-center">Reserved</th>
                                    <th class="text-center">Days to supply</th>
                                    <th class="text-center">Price</th>
                                    <th class="text-center">COGS</th>
                                    <th class="text-center">Asset Value</th>
                                    <th class="text-center">$ of asset Value</th>
                                    <th class="text-center">Gross Sales</th>
                                    <th class="text-center">Average Monthly Sales</th>
                                    <th class="text-center">Sales Quantity</th>
                                    <th class="text-center">Average Monthly Quantity</th>
                                    <th class="text-center">Profit</th>
                                    <th class="text-center">ROI</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  <tr>
                                    <td class="text-center">img</td>
                                    <td class="text-center">Nike shoes</td>
                                    <td class="text-center">10</td>
                                    <td class="text-center"><input type="text" style="border: 0;text-align:center"></td>
                                    <td class="text-center">8</td>
                                    <td class="text-center">1</td>
                                    <td class="text-center">30</td>
                                    <td class="text-center">100</td>
                                    <td class="text-center">80</td>
                                    <td class="text-center">800</td>
                                    <td class="text-center">5%</td>
                                    <td class="text-center">5%</td>
                                    <td class="text-center">5%</td>
                                    <td class="text-center">5%</td>
                                    <td class="text-center">5%</td>
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
    <script src="{{ asset('js/main.js') }}" defer></script>
@endsection
