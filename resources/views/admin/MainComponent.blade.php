<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50, #1a1a2e);
            color: #fff;
            position: sticky;
            top: 0;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
            border-radius: 5px;
            transition: all 0.3s ease;
            padding: 10px 15px;
            font-size: 0.95rem;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background: linear-gradient(90deg, #4b6cb7, #182848);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        .content {
            padding: 20px;
        }
        .dashboard-card {
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            padding: 20px;
            background-color: #fff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            height: 100%;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .dashboard-card h5 {
            font-size: 1.1rem;
            color: #495057;
            margin-bottom: 15px;
            font-weight: 600;
        }
        .dashboard-card h2 {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .dashboard-card p {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0;
        }
        .navbar-brand {
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .sidebar-header {
            padding: 20px 15px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-header h4 {
            font-weight: 600;
            margin-bottom: 0;
            color: #fff;
        }
        .sidebar-header img {
            width: 40px;
            height: 40px;
            margin-right: 10px;
        }
        .btn-logout {
            background: linear-gradient(90deg, #ff416c, #ff4b2b);
            border: none;
            border-radius: 5px;
            padding: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn-logout:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 75, 43, 0.3);
        }
        .main-header {
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .stat-card-icon {
            font-size: 2.5rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            top: 20px;
        }
        .stat-card-primary { border-left: 4px solid #4e73df; }
        .stat-card-success { border-left: 4px solid #1cc88a; }
        .stat-card-warning { border-left: 4px solid #f6c23e; }
        .stat-card-danger { border-left: 4px solid #e74a3b; }
        .stat-card-info { border-left: 4px solid #36b9cc; }
        .stat-card-secondary { border-left: 4px solid #858796; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="sidebar-header d-flex align-items-center justify-content-center">
                    <i class="bi bi-shield-lock-fill" style="font-size: 1.8rem;"></i>
                    <h4>Admin Panel</h4>
                </div>
                <ul class="nav flex-column p-3">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/providers*') ? 'active' : '' }}" href="{{ route('admin.providers.index') }}">
                            <i class="bi bi-person-badge"></i> Service Providers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/clients*') ? 'active' : '' }}" href="{{ route('admin.clients.index') }}">
                            <i class="bi bi-people-fill"></i> Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/orders*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                            <i class="bi bi-card-checklist"></i> Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/categories*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                            <i class="bi bi-tags-fill"></i> Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/offers*') ? 'active' : '' }}" href="{{ route('admin.offers.index') }}">
                            <i class="bi bi-percent"></i> Offers
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <form action="{{ route('admin.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-logout w-100">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            @yield('content')
            <!-- Main Content -->
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>