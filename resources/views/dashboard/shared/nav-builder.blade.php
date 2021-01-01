    <div class="c-sidebar-brand">
        <img class="c-sidebar-brand-full" src="/assets/brand/brand-inline.png">
        <img class="c-sidebar-brand-minimized" src="/assets/brand/brand-icon.png" style="padding:5px">
    </div>
    <ul class="c-sidebar-nav">
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="/">
                <i class="cil-speedometer c-sidebar-nav-icon"></i>
                Dashboard
            </a>
        </li>
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="/inventory">
                <i class="cil-3d c-sidebar-nav-icon"></i>
                Inventory
            </a>
        </li>
        <li class="c-sidebar-nav-item">
            <a class="c-sidebar-nav-link" href="/inventory/inbound">
                <i class="cil-exit-to-app c-sidebar-nav-icon"></i>
                Inbound Order
            </a>
        </li>


        <li class="c-sidebar-nav-dropdown"><a class="c-sidebar-nav-dropdown-toggle"><i class="cil-clipboard c-sidebar-nav-icon"></i>Tools</a>
            @if(auth()->user()->currentShop->platform == "SHOPEE")
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="/sync-items/add"><span class="c-sidebar-nav-icon"></span>Add Items to Lazada</a></li>
            </ul>
            @endif
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="/sync-items"><span class="c-sidebar-nav-icon"></span>Sync Stock with 
                    @if(auth()->user()->currentShop->platform == "SHOPEE")
                    Lazada
                    @elseif(auth()->user()->currentShop->platform == "LAZADA")
                    Shopee
                    @endif
                </a></li>
            </ul>
        </li>
        <li class="c-sidebar-nav-dropdown"><a class="c-sidebar-nav-dropdown-toggle"><i class="cil-settings c-sidebar-nav-icon"></i>Settings</a>
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="/user/edit"><span class="c-sidebar-nav-icon"></span>User Settings</a></li>
            </ul>
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="/shop/sign-in"><span class="c-sidebar-nav-icon"></span>Add More Shops</a></li>
            </ul>
        </li>
        <li class="c-sidebar-nav-dropdown"><a class="c-sidebar-nav-dropdown-toggle"><i class="cil-settings c-sidebar-nav-icon"></i>Help</a>
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="https://documentation.xiapisync.com"><span class="c-sidebar-nav-icon"></span>Documentation</a></li>
            </ul>
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="/feedback"><span class="c-sidebar-nav-icon"></span>Leave a Feedback</a></li>
            </ul>
            <ul class="c-sidebar-nav-dropdown-items">
                <li class="c-sidebar-nav-item"><a class="c-sidebar-nav-link" href="/about"><span class="c-sidebar-nav-icon"></span>About</a></li>
            </ul>
        </li>
    </ul>
    <button class="c-sidebar-minimizer c-class-toggler" type="button" data-target="_parent" data-class="c-sidebar-minimized"></button>
</div>