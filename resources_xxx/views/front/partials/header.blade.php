<!-- Navbar -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/">
            <img src="{{ asset('images/front/logos/WhatsApp Image 2025-12-28 at 2.59.06 PM.jpeg') }}" alt="" class="logo-img">
            <span class="logo-text"></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="/">الرئيسية</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">عربات الطعام</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">الأصناف</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">عن عابر</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">تواصل معنا</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="sellersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        أصحاب العربات
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="sellersDropdown">
                        <li><a class="dropdown-item" href="{{ route('provider.login') }}">تسجيل الدخول</a></li>
                        <li><a class="dropdown-item" href="{{ route('provider.phone') }}">تسجيل عربة جديدة</a></li>
                    </ul>
                </li>
            </ul>
            <div class="d-flex align-items-center">
                <a href="#" class="btn-link" title="البحث">
                    <i class="fas fa-search"></i>
                </a>
                <a href="#" class="btn-link" title="المفضلة">
                    <i class="fas fa-heart"></i>
                </a>
                <a href="#" class="btn-link position-relative" title="سلة الطلبات">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
                </a>
                <a href="{{ route('client.login') }}" class="btn btn-outline-primary ms-3">تسجيل الدخول</a>
            </div>
        </div>
    </div>
</nav> 