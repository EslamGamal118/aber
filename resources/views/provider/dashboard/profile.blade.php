<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Profile</title>
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
        .profile-card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            background-color: #fff;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 15px;
            object-fit: cover;
            border: 5px solid #f8f9fa;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .btn-upload {
            position: relative;
            overflow: hidden;
        }
        .btn-upload input[type=file] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .tab-content {
            padding-top: 20px;
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
                        <a class="nav-link" href="{{ route('provider.dashboard') }}">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="{{ route('provider.profile') }}">
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
                    <h1 class="h2">My Profile</h1>
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
                
                <div class="profile-card profile-header">
                    <div class="position-relative">
                        @if($provider->avatar)
                            <img src="{{ asset('storage/'.$provider->avatar) }}" alt="Profile Picture" class="profile-avatar">
                        @else
                            <div class="profile-avatar d-flex align-items-center justify-content-center bg-primary text-white">
                                <span class="display-4">{{ substr($provider->name, 0, 1) }}</span>
                            </div>
                        @endif
                        
                        <form action="{{ route('provider.update.avatar') }}" method="POST" enctype="multipart/form-data" class="mt-2">
                            @csrf
                            <div class="btn-upload btn btn-sm btn-outline-primary">
                                <i class="bi bi-camera-fill me-1"></i> Change Photo
                                <input type="file" name="avatar" onchange="this.form.submit()">
                            </div>
                        </form>
                    </div>
                    <h3 class="mt-3">{{ $provider->name }}</h3>
                    <p class="text-muted">
                        <i class="bi bi-building me-1"></i> {{ ucfirst($provider->type) }} Provider
                        &nbsp;|&nbsp;
                        <i class="bi bi-geo-alt me-1"></i> {{ $provider->city ?? 'Not specified' }}
                    </p>
                </div>
                
                <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal" type="button" role="tab" aria-controls="personal" aria-selected="true">
                            Personal Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                            Documents
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false">
                            Security
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="profileTabsContent">
                    <!-- Personal Information Tab -->
                    <div class="tab-pane fade show active" id="personal" role="tabpanel" aria-labelledby="personal-tab">
                        <div class="profile-card">
                            <h5 class="mb-4">Edit Personal Information</h5>
                            
                            <form action="{{ route('provider.update.profile') }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $provider->name) }}">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $provider->email) }}">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="phone" value="{{ $provider->phone }}" disabled>
                                        <small class="text-muted">Phone number cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="city" class="form-label">City</label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city', $provider->city) }}">
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $provider->address) }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="bio" class="form-label">Bio</label>
                                    <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" rows="4" placeholder="Tell clients about yourself and your services...">{{ old('bio', $provider->bio) }}</textarea>
                                    @error('bio')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save me-1"></i> Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Documents Tab -->
                    <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
                        <div class="profile-card">
                            <h5 class="mb-4">Documents</h5>
                            
                            <form action="{{ route('provider.update.documents') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                
                                @if($provider->type == 'personal')
                                    <!-- ID Card Document -->
                                    <div class="mb-4">
                                        <label class="form-label">ID Card</label>
                                        
                                        @if($provider->id_card)
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="bi bi-file-earmark-text fs-4 me-2"></i>
                                                            ID Card Document
                                                        </div>
                                                        <div>
                                                            <a href="{{ asset('storage/'.$provider->id_card) }}" class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                                                <i class="bi bi-eye me-1"></i> View
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                You haven't uploaded your ID card yet.
                                            </div>
                                        @endif
                                        
                                        <div class="mt-2">
                                            <label for="id_card" class="form-label">Upload ID Card</label>
                                            <input class="form-control @error('id_card') is-invalid @enderror" type="file" id="id_card" name="id_card">
                                            <div class="form-text">Upload a clear image of your ID card. Accepted formats: JPG, PNG, PDF. Max size: 5MB.</div>
                                            @error('id_card')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @else
                                    <!-- Commercial Register Document -->
                                    <div class="mb-4">
                                        <label class="form-label">Commercial Register</label>
                                        
                                        @if($provider->commercial_register)
                                            <div class="card mb-3">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="bi bi-file-earmark-text fs-4 me-2"></i>
                                                            Commercial Register Document
                                                        </div>
                                                        <div>
                                                            <a href="{{ asset('storage/'.$provider->commercial_register) }}" class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                                                <i class="bi bi-eye me-1"></i> View
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                You haven't uploaded your commercial register yet.
                                            </div>
                                        @endif
                                        
                                        <div class="mt-2">
                                            <label for="commercial_register" class="form-label">Upload Commercial Register</label>
                                            <input class="form-control @error('commercial_register') is-invalid @enderror" type="file" id="commercial_register" name="commercial_register">
                                            <div class="form-text">Upload a clear image of your commercial register. Accepted formats: JPG, PNG, PDF. Max size: 5MB.</div>
                                            @error('commercial_register')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                @endif
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-cloud-upload me-1"></i> Upload Document
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Security Tab -->
                    <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                        <div class="profile-card">
                            <h5 class="mb-4">Change Password</h5>
                            
                            <form action="{{ route('provider.update.password') }}" method="POST">
                                @csrf
                                @method('PUT')
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password">
                                    @error('current_password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="mb-3">
                                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-lock me-1"></i> Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 