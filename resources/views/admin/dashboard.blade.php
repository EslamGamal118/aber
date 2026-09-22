@extends('admin.MainComponent')


@section('content')
 <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-3">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center main-header">
                    <h1 class="h2 mb-0"><i class="bi bi-speedometer2 me-2"></i> Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="{{ route('admin.providers.index') }}" class="btn btn-sm btn-primary">
                                <i class="bi bi-person-badge me-1"></i> Manage Providers
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3 g-3">
                    <div class="col-md-4">
                        <div class="dashboard-card stat-card-primary">
                            <h5><i class="bi bi-people me-2"></i> Total Users</h5>
                            <h2>{{ App\Models\Client::count() }}</h2>
                            <p>Registered users in the system</p>
                        </div>
                    </div>
                   <div class="col-md-4">
                        <div class="dashboard-card stat-card-success">
                            <h5><i class="bi bi-person-check me-2"></i> Recent Users</h5>
                            <h2>{{ App\Models\Client::where('created_at', '>=', now()->subDays(30))->count() }}</h2>
                            <p>Registered in last 30 days</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card stat-card-warning">
                            <h5><i class="bi bi-shield-lock me-2"></i> Total Admins</h5>
                            <h2>{{ App\Models\Admin::count() }}</h2>
                            <p>Administrators in the system</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card stat-card-danger">
                            <h5><i class="bi bi-cart-check me-2"></i> Total Orders</h5>
                            <h2>{{ App\Models\Order::count() }}</h2>
                            <p>Orders in the system</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card stat-card-info">
                            <h5><i class="bi bi-flag me-2"></i> Total Cities</h5>
                            <h2>{{ App\Models\City::count() }}</h2>
                            <p>Cities in the system</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="dashboard-card stat-card-secondary">
                            <h5><i class="bi bi-tags me-2"></i> Total Categories</h5>
                            <h2>{{ App\Models\Category::count() }}</h2>
                            <p>Categories in the system</p>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <h5><i class="bi bi-person-badge me-2"></i> Service Providers</h5>
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-3">
                                        <h2 class="text-warning"><i class="bi bi-hourglass-split me-2"></i>{{ App\Models\Provider::where('status', 'pending')->count() }}</h2>
                                        <p>Pending Approval</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-3">
                                        <h2 class="text-success"><i class="bi bi-check-circle me-2"></i>{{ App\Models\Provider::where('status', 'active')->count() }}</h2>
                                        <p>Active Providers</p>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    <i class="bi bi-info-circle me-1"></i> Last updated: Just now
                                </div>
                                <a href="{{ route('admin.providers.index') }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-arrow-right-circle me-1"></i> Manage Providers
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="dashboard-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5><i class="bi bi-person-circle me-2"></i> Account Information</h5>
                                <a href="{{ route('admin.profile.edit') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil-square me-1"></i> Edit Profile
                                </a>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                    <i class="bi bi-person-fill text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">{{ Auth::guard('admin')->user()->name }}</h6>
                                    <small class="text-muted">Administrator</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <p class="mb-1"><i class="bi bi-envelope me-2"></i> Email:</p>
                                    <p class="text-muted small">{{ Auth::guard('admin')->user()->email }}</p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1"><i class="bi bi-clock-history me-2"></i> Last Login:</p>
                                    <p class="text-muted small">{{ Auth::guard('admin')->user()->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endsection