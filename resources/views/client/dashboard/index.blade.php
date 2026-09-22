<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - ServiceBooking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background-color: #343a40;
            color: #fff;
            padding: 20px 0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .brand-logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #4361ee;
        }
        
        .sidebar-menu {
            padding: 0;
            list-style: none;
            margin-top: 20px;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu li a {
            padding: 10px 20px;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-size: 15px;
            transition: 0.3s;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left: 3px solid #4361ee;
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            font-size: 18px;
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .page-header {
            padding: 15px 0;
            margin-bottom: 20px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #4361ee;
        }
        
        .service-card {
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .service-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .service-card-body {
            padding: 15px;
            background: #fff;
        }
        
        .service-card-title {
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .badge {
            font-weight: 500;
            font-size: 0.75rem;
        }
        
        .service-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -260px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .toggler {
                display: block;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="brand-logo">ServiceBooking</div>
                <div class="text-muted small">Client Dashboard</div>
            </div>
            
            <ul class="sidebar-menu">
                <li>
                    <a href="{{ route('client.dashboard') }}" class="active">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('client.profile') }}">
                        <i class="bi bi-person"></i> My Profile
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-search"></i> Browse Services
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-calendar-check"></i> My Bookings
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-star"></i> Favorites
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-chat-dots"></i> Messages
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-bell"></i> Notifications
                    </a>
                </li>
                <li>
                    <form action="{{ route('client.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn p-0 w-100">
                            <a class="text-start w-100" style="background: none; border: none;">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </button>
                    </form>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="page-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0">Welcome, {{ $client->name }}!</h4>
                        <p class="text-muted">Here's what's happening with your account today.</p>
                    </div>
                    <div>
                        <button class="btn btn-primary">
                            <i class="bi bi-plus-circle"></i> Book New Service
                        </button>
                    </div>
                </div>
                
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                
                <!-- Status Cards Row -->
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card text-center h-100">
                            <div class="stat-card-icon">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                            <h3 class="fs-4 mb-1">5</h3>
                            <p class="mb-0 text-muted">Total Bookings</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center h-100">
                            <div class="stat-card-icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <h3 class="fs-4 mb-1">2</h3>
                            <p class="mb-0 text-muted">Pending Bookings</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center h-100">
                            <div class="stat-card-icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <h3 class="fs-4 mb-1">3</h3>
                            <p class="mb-0 text-muted">Completed Services</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center h-100">
                            <div class="stat-card-icon">
                                <i class="bi bi-heart"></i>
                            </div>
                            <h3 class="fs-4 mb-1">7</h3>
                            <p class="mb-0 text-muted">Favorite Services</p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings Section -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Bookings</h5>
                        <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Provider</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Home Cleaning</td>
                                        <td>Clean Masters Co.</td>
                                        <td>03 Apr, 2025</td>
                                        <td><span class="badge bg-warning">Pending</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary">Details</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>AC Maintenance</td>
                                        <td>Cool Air Services</td>
                                        <td>01 Apr, 2025</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary">Details</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Plumbing Repair</td>
                                        <td>Fix It Plumbers</td>
                                        <td>28 Mar, 2025</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-secondary">Details</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Recommended Services -->
                <h5 class="mb-3">Recommended Services</h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="service-card">
                            <span class="badge bg-primary service-tag">Popular</span>
                            <img src="https://img.freepik.com/premium-photo/male-hands-typing-computer-keyboard-service-concept_220873-10436.jpg?semt=ais_hybrid" alt="Service Image">
                            <div class="service-card-body">
                                <h5 class="service-card-title">Home Cleaning</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Starting from $25</span>
                                    <div>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <span>4.8 (120)</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary w-100 mt-3">Book Now</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-card">
                            <span class="badge bg-danger service-tag">Hot Deal</span>
                            <img src="https://img.freepik.com/premium-photo/male-hands-typing-computer-keyboard-service-concept_220873-10436.jpg?semt=ais_hybrid" alt="Service Image">
                            <div class="service-card-body">
                                <h5 class="service-card-title">AC Repair & Maintenance</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Starting from $40</span>
                                    <div>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <span>4.7 (95)</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary w-100 mt-3">Book Now</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="service-card">
                            <img src="https://img.freepik.com/premium-photo/male-hands-typing-computer-keyboard-service-concept_220873-10436.jpg?semt=ais_hybrid" alt="Service Image">
                            <div class="service-card-body">
                                <h5 class="service-card-title">Pest Control</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">Starting from $35</span>
                                    <div>
                                        <i class="bi bi-star-fill text-warning"></i>
                                        <span>4.6 (78)</span>
                                    </div>
                                </div>
                                <button class="btn btn-primary w-100 mt-3">Book Now</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 