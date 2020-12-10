<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use App\Stock;
use App\StockCost;

class InventoryImport implements ToCollection,WithValidation, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $stocks = Stock::with('costs')->where('shop_id',auth()->user()->current_shop_id)->get();

            
        foreach($collection as $key => $row){
            // dd($row);
            // if($key == 0) continue;
            if(!$row['stock_id']) break;
            $stock = $stocks->where('id',$row['stock_id'])->first();
            $stock->days_to_supply = $row['days_to_supply'];
            $stock->safety_stock = $row['safety_stock'];
            $stock->costs->where('from_date','1970-01-01')->first()->update(['cost' => $row['cost_initial']]);
            if($row['cost_now']){
                $todayDate = date("Y-m-d");
                $todayCost = $stock->costs->where('from_date',$todayDate)->first();
                if($todayCost)
                    $todayCost->update(['cost' => $row['cost_now']]);
                else
                    StockCost::create(['stock_id' => $stock->id,'cost' => $row['cost_now'],'from_date' => $todayDate]);
            }
            $stock->save();
        }
    }

    public function rules(): array
    {
        // dd(1234);
        return [
            'safety_stock' => 'required|numeric|gte:0',
            'days_to_supply' => 'required|numeric|gte:0',
            'cost_initial' => 'required|numeric|gte:0.01',
            'cost_now' => 'nullable|numeric|gte:0.01',
        ];
    }
}
