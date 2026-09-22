@extends('admin.MainComponent')
@section('content')
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Provider Details</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="{{ route('admin.providers.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to List
                        </a>
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
                
                <div class="row">
                    <!-- Provider Info Card -->
                    <div class="col-md-12">
                        <div class="detail-card col-md-12 row bg-white p-4 mb-4 rounded shadow-sm align-items-center" >

                            
                            <div class="col-md-6">
                                <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <span class="fw-bold"><i class="bi bi-person me-1"></i> Name:</span>
                                    <span class="float-end">{{ $provider->name }}</span>
                                </li>
                                <li class="list-group-item">
                                    <span class="fw-bold"><i class="bi bi-envelope me-1"></i> Email:</span>
                                    <span class="float-end">{{ $provider->email }}</span>
                                </li>
                                <li class="list-group-item">
                                    <span class="fw-bold"><i class="bi bi-telephone me-1"></i> Phone:</span>
                                    <span class="float-end">{{ $provider->phone }}</span>
                                </li>
                                <li class="list-group-item">
                                    <span class="fw-bold"><i class="bi bi-person-lines-fill me-1"></i> Account Status:</span>
                                    <span class="float-end">
                                        @if($provider->status == 'pending')
                                    <span class="badge bg-warning status-badge">Pending Approval</span>
                                        @elseif($provider->status == 'active')
                                            <span class="badge bg-success status-badge">Active</span>
                                        @elseif($provider->status == 'rejected')
                                            <span class="badge bg-danger status-badge">Rejected</span>
                                        @elseif($provider->status == 'blocked')
                                            <span class="badge bg-dark status-badge">Blocked</span>
                                        @endif
                                    </span>
                                </li>
                                <li class="list-group-item">
                                    <span class="fw-bold"><i class="bi bi-geo-alt me-1"></i> Location:</span>
                                    <span class="float-end">{{ $provider->city ?? 'Not specified' }}</span>
                                </li>
                                <li class="list-group-item">
                                    <span class="fw-bold"><i class="bi bi-calendar-check me-1"></i> Registered:</span>
                                    <span class="float-end">{{ $provider->created_at->format('Y-m-d') }}</span>
                                </li>
                                <li class="list-group-item">
                                    <span class="fw-bold"><i class="bi bi-building me-1"></i> Provider Type:</span>
                                    <span class="float-end">{{ ucfirst($provider->type) }}</span>
                                </li>
                                @if($provider->approved_at)
                                <li class="list-group-item">
                                    <span class="fw-bold"><i class="bi bi-check-circle me-1"></i> Approved:</span>
                                    <span class="float-end">{{ \Carbon\Carbon::parse($provider->approved_at)->format('Y-m-d') }}</span>
                                </li>
                                @endif
                            </ul>
                            </div>
                            
                            <div class="text-center mb-4 col-md-6">
                                @if($provider->avatar)
                                    <img src="{{ asset('storage/'.$provider->avatar) }}" class="rounded shadow" alt="Provider Profile">
                                @else
                                    <div class="profile-image d-flex align-items-center justify-content-center bg-primary text-white mx-auto">
                                        <span class="display-6">{{ substr($provider->name, 0, 1) }}</span>
                                    </div>
                                @endif
                            </div>
                            @if($provider->status_message)
                            <div class="mt-3">
                                <h6 class="fw-bold">Status Message:</h6>
                                <div class="alert alert-secondary">
                                    {{ $provider->status_message }}
                                </div>
                            </div>
                            @endif
                            
                            @if($provider->bio)
                            <div class="mt-3">
                                <h6 class="fw-bold">Bio:</h6>
                                <p>{{ $provider->bio }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Documents & Actions -->
                    <div class="col-md-12">
                        <!-- Documents Card -->
                        <div class="detail-card">
                            <h5 class="mb-3">Documents</h5>
                            
                            <div class="list-group mb-4">
                                @if($provider->type == 'personal')
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">ID Card</h6>
                                                <p class="text-muted mb-0 small">Personal identification document</p>
                                            </div>
                                            <div>
                                                @if($provider->id_card)
                                                    <a href="{{ route('admin.providers.document', ['provider' => $provider, 'type' => 'id_card']) }}" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                @else
                                                    <span class="badge bg-light text-dark">Not uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Commercial Register</h6>
                                                <p class="text-muted mb-0 small">Company registration document</p>
                                            </div>
                                            <div>
                                                @if($provider->commercial_register)
                                                    <a href="{{ route('admin.providers.document', ['provider' => $provider, 'type' => 'commercial_register']) }}" class="btn btn-sm btn-primary" target="_blank">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                @else
                                                    <span class="badge bg-light text-dark">Not uploaded</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            
                            <!-- Actions -->
                            <h5 class="mb-3">Actions</h5>
                            
                            <div class="d-grid gap-2">
                                @if($provider->status == 'pending')
                                    <form action="{{ route('admin.providers.approve', $provider) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-success mb-2 w-100">
                                            <i class="bi bi-check-lg me-1"></i> Approve Provider
                                        </button>
                                    </form>
                                    
                                    <button type="button" class="btn btn-danger mb-2" data-bs-toggle="modal" data-bs-target="#rejectProviderModal">
                                        <i class="bi bi-x-lg me-1"></i> Reject Provider
                                    </button>
                                @endif
                                
                                @if($provider->status == 'active')
                                    <button type="button" class="btn btn-dark mb-2" data-bs-toggle="modal" data-bs-target="#blockProviderModal">
                                        <i class="bi bi-slash-circle me-1"></i> Block Provider
                                    </button>
                                @endif
                                
                                @if($provider->status == 'blocked')
                                    <form action="{{ route('admin.providers.unblock', $provider) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-success mb-2 w-100">
                                            <i class="bi bi-unlock me-1"></i> Unblock Provider
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

    
    <!-- Reject Provider Modal -->
    <div class="modal fade" id="rejectProviderModal" tabindex="-1" aria-labelledby="rejectProviderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.providers.reject', $provider) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="rejectProviderModalLabel">Reject Provider</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status_message" class="form-label">Reason for Rejection</label>
                            <textarea class="form-control" id="status_message" name="status_message" rows="4" required></textarea>
                            <div class="form-text">Please provide a detailed reason for rejecting this provider. This message will be shown to the provider.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Reject Provider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Block Provider Modal -->
    <div class="modal fade" id="blockProviderModal" tabindex="-1" aria-labelledby="blockProviderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.providers.block', $provider) }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="blockProviderModalLabel">Block Provider</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status_message" class="form-label">Reason for Blocking</label>
                            <textarea class="form-control" id="status_message" name="status_message" rows="4" required></textarea>
                            <div class="form-text">Please provide a detailed reason for blocking this provider. This message will be shown to the provider.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-dark">Block Provider</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endsection