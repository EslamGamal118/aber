<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            color: #fff;
            position: sticky;
            top: 0;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 5px;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: #4361ee;
        }
        .content {
            padding: 20px;
        }
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fff;
        }
        .status-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-pending {
            background-color: #fdf6b2;
            color: #92400e;
        }
        .status-active {
            background-color: #def7ec;
            color: #03543e;
        }
        .status-rejected {
            background-color: #fde8e8;
            color: #9b1c1c;
        }
        .status-blocked {
            background-color: #e5e7eb;
            color: #374151;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="pt-3 text-center">
                    <h4>Provider Panel</h4>
                    <hr>
                </div>
                <ul class="nav flex-column p-2">
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('provider.dashboard') }}">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('provider.profile') }}">
                            <i class="bi bi-person me-2"></i> My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-bag me-2"></i> My Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-calendar-check me-2"></i> Bookings
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="bi bi-chat-left-text me-2"></i> Messages
                        </a>
                    </li>
                    <li class="nav-item mt-3">
                        <form action="{{ route('provider.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-box-arrow-right me-2"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="#" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-bell"></i> Notifications
                            </a>
                        </div>
                    </div>
                </div>
                
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif
                
                <!-- Account Status Card -->
                <div class="dashboard-card mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5>Account Status</h5>
                        <div>
                            @if($provider->status == 'pending')
                                <span class="status-badge status-pending">
                                    <i class="bi bi-hourglass-split me-1"></i> Pending Approval
                                </span>
                            @elseif($provider->status == 'active')
                                <span class="status-badge status-active">
                                    <i class="bi bi-check-circle me-1"></i> Active
                                </span>
                            @elseif($provider->status == 'rejected')
                                <span class="status-badge status-rejected">
                                    <i class="bi bi-x-circle me-1"></i> Rejected
                                </span>
                            @elseif($provider->status == 'blocked')
                                <span class="status-badge status-blocked">
                                    <i class="bi bi-slash-circle me-1"></i> Blocked
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        @if($provider->status == 'pending')
                            <p>Your account is currently under review. We will notify you once it's approved.</p>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-1"></i> 
                                This usually takes 24-48 hours. Thank you for your patience.
                            </div>
                        @elseif($provider->status == 'active')
                            <p>Your account is active. You can now start adding your services.</p>
                            <a href="#" class="btn btn-primary btn-sm">
                                <i class="bi bi-plus-circle me-1"></i> Add New Service
                            </a>
                        @elseif($provider->status == 'rejected')
                            <p>Your account registration was rejected for the following reason:</p>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-1"></i> 
                                {{ $provider->status_message }}
                            </div>
                            <p>Please contact support for more information.</p>
                        @elseif($provider->status == 'blocked')
                            <p>Your account has been blocked for the following reason:</p>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle me-1"></i> 
                                {{ $provider->status_message }}
                            </div>
                            <p>Please contact support for more information.</p>
                        @endif
                    </div>
                </div>
                
                <!-- Profile Completion -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5>Profile Information</h5>
                            <ul class="list-group list-group-flush mt-3">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-person me-2"></i> Name</span>
                                    <span class="text-muted">{{ $provider->name }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-envelope me-2"></i> Email</span>
                                    <span class="text-muted">{{ $provider->email }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-phone me-2"></i> Phone</span>
                                    <span class="text-muted">{{ $provider->phone }}</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-building me-2"></i> Provider Type</span>
                                    <span class="text-muted">{{ ucfirst($provider->type) }}</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5>Account Documents</h5>
                            <ul class="list-group list-group-flush mt-3">
                                @if($provider->type == 'personal')
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-card-text me-2"></i> ID Card</span>
                                        @if($provider->id_card)
                                            <span class="badge bg-success">Uploaded</span>
                                        @else
                                            <span class="badge bg-danger">Missing</span>
                                        @endif
                                    </li>
                                @else
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="bi bi-file-text me-2"></i> Commercial Register</span>
                                        @if($provider->commercial_register)
                                            <span class="badge bg-success">Uploaded</span>
                                        @else
                                            <span class="badge bg-danger">Missing</span>
                                        @endif
                                    </li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 