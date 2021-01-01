<div class="c-wrapper">
  <header class="c-header c-header-light c-header-fixed c-header-with-subheader">
    <button class="c-header-toggler c-class-toggler d-lg-none mr-auto" type="button" data-target="#sidebar" data-class="c-sidebar-show"><span class="c-header-toggler-icon"></span></button><a class="c-header-brand d-sm-none" href="#"><img src="/assets/brand/brand-inline.png" style="padding:5px"></a>
    <button class="c-header-toggler c-class-toggler ml-3 d-md-down-none" type="button" data-target="#sidebar" data-class="c-sidebar-lg-show" responsive="true"><span class="c-header-toggler-icon"></span></button>
    <ul class="c-header-nav d-md-down-none">
      <div class="dropdown show">
        <a class="btn btn-outline-datk dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          {{getShopsSession()[Auth::user()->current_shop_id]['platform']." - ".getShopsSession()[Auth::user()->current_shop_id]['shop_name']." - ".getShopsSession()[Auth::user()->current_shop_id]['shop_country']}}
        </a>
        <div class="dropdown-menu" aria-labelledby="dropdownMenuLink">
          @foreach(getShopsSession() as $shop_info)
          @if($shop_info['id'] != Auth::user()->current_shop_id)
          <a class="dropdown-item" href="/shop/change?id={{$shop_info['id']}}">{{$shop_info['platform']." - ".$shop_info['shop_name']." - ".$shop_info['shop_country']}}</a>
          @endif
          @endforeach
          <!-- <a class="dropdown-item" href="#">Action</a> -->
          <!-- <a class="dropdown-item" href="#">Another action</a> -->
          @if(count(getShopsSession()) > 1)
          <div class="dropdown-divider"></div>
          @endif
          <a class="dropdown-item flex align-items-center" href="/shop/sign-in">
            <i class="fa fa-plus-square-o" aria-hidden="true" style="font-size:1.1rem;line-height: inherit;"></i>&nbsp;&nbsp;Add More Shop
          </a>
        </div>
      </div>
      <!-- <li class="c-header-nav-item px-3"><a class="c-header-nav-link" href="#">Dashboard</a></li>
          <li class="c-header-nav-item px-3"><a class="c-header-nav-link" href="#">Users</a></li>
          <li class="c-header-nav-item px-3"><a class="c-header-nav-link" href="#">Settings</a></li> -->
    </ul>
    <ul class="c-header-nav ml-auto mr-4">

      <li class="c-header-nav-item d-md-down-none mx-2">
        <a class="c-header-nav-link">
          <small class="mr-1" id="sync-text"></small>
          <button class="btn" onclick="syncLatestData()">
            <i class="cil-sync"></i> Sync Now <br> 
          </button>
        </a>
      </li>
      <li class="c-header-nav-item d-md-down-none mx-2">
        <a class="c-header-nav-link">
          <form action="/logout" method="POST"> @csrf <button type="submit" class="btn">Logout</button></form>
          <!-- <svg class="c-icon mr-2">
                <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-account-logout"></use>
              </svg> -->
          <!-- <svg class="c-icon mr-2">
          <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-account-logout"></use>
        </svg> -->
        </a>
      </li>
      <!-- <li class="c-header-nav-item dropdown"><a class="c-header-nav-link" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
          <div class="c-avatar"><img class="c-avatar-img" src="{{ env('APP_URL', '') }}/assets/img/avatars/6.jpg" alt="user@email.com"></div>
        </a>
        <div class="dropdown-menu dropdown-menu-right pt-0">
          <div class="dropdown-header bg-light py-2"><strong>Account</strong></div><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-bell"></use>
            </svg> Updates<span class="badge badge-info ml-auto">42</span></a><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-envelope-open"></use>
            </svg> Messages<span class="badge badge-success ml-auto">42</span></a><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-task"></use>
            </svg> Tasks<span class="badge badge-danger ml-auto">42</span></a><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-comment-square"></use>
            </svg> Comments<span class="badge badge-warning ml-auto">42</span></a>
          <div class="dropdown-header bg-light py-2"><strong>Settings</strong></div><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-user"></use>
            </svg> Profile</a><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-settings"></use>
            </svg> Settings</a><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-credit-card"></use>
            </svg> Payments<span class="badge badge-secondary ml-auto">42</span></a><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-file"></use>
            </svg> Projects<span class="badge badge-primary ml-auto">42</span></a>
          <div class="dropdown-divider"></div><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-lock-locked"></use>
            </svg> Lock Account</a><a class="dropdown-item" href="#">
            <svg class="c-icon mr-2">
              <use xlink:href="{{ env('APP_URL', '') }}/icons/sprites/free.svg#cil-account-logout"></use>
            </svg>
            <form action="/logout" method="POST"> @csrf <button type="submit" class="btn btn-ghost-dark btn-block">Logout</button></form>
          </a>
        </div>
      </li> -->
    </ul>
    <!-- <div class="c-subheader px-3">
          <ol class="breadcrumb border-0 m-0">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <?php $segments = ''; ?>
            @for($i = 1; $i <= count(Request::segments()); $i++)
                <?php $segments .= '/' . Request::segment($i); ?>
                @if($i < count(Request::segments()))
                    <li class="breadcrumb-item">{{ Request::segment($i) }}</li>
                @else
                    <li class="breadcrumb-item active">{{ Request::segment($i) }}</li>
                @endif
            @endfor
          </ol>
        </div> -->
  </header>