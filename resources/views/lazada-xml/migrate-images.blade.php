<?xml version="1.0" encoding="UTF-8"?>

<Request>     
 <Images>
    @foreach($imageUrls as $url)      
        <Url>{{$url}}</Url>         
    @endforeach     
 </Images> 
</Request>