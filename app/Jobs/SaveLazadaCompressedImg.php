<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Intervention\Image\Facades\Image;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SaveLazadaCompressedImg implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $stock;
    protected $product;
    protected $shop_id;

    public function __construct($product,$stock,$shop_id)
    {
        $this->product = $product;
        $this->stock = $stock;
        $this->shop_id = $shop_id;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        
        if(isset($this->product['skus'][0]['Images'][0])){
            $imgUrl = $this->product['skus'][0]['Images'][0];
            $extension = \File::extension($imgUrl);
            $path = 'images/'.$this->shop_id.'/';
            $imgPath = $path.uniqid().'.'.$extension;

            if (!file_exists(storage_path($path))) {
                mkdir(storage_path($path), 666, true);
            }
            Image::make($imgUrl)->fit(100)->save(storage_path($imgPath));
            $this->stock->compressed_img_path = $imgPath;
            $this->stock->save();
            
        }
    }
}
