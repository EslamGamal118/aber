@extends('admin.MainComponent')

@section('content')
<!-- Main Content -->
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Client Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to List
            </a>
        </div>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="row">
        <!-- Client Info Card -->
        <div class="col-md-12">
            <div class="detail-card col-md-12 row bg-white p-4 mb-4 rounded shadow-sm align-items-center">
                <div class="col-md-6">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <span class="fw-bold"><i class="bi bi-person me-1"></i> Name:</span>
                            <span class="float-end">{{ $client->name }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold"><i class="bi bi-envelope me-1"></i> Email:</span>
                            <span class="float-end">{{ $client->email }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold"><i class="bi bi-telephone me-1"></i> Phone:</span>
                            <span class="float-end">{{ $client->phone }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold"><i class="bi bi-gender-ambiguous me-1"></i> Gender:</span>
                            <span class="float-end">{{ $client->gender ? ucfirst($client->gender) : 'Not specified' }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold"><i class="bi bi-person-lines-fill me-1"></i> Status:</span>
                            <span class="float-end">
                                @if($client->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold"><i class="bi bi-geo-alt me-1"></i> Location:</span>
                            <span class="float-end">{{ $client->city ?? 'Not specified' }}</span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold"><i class="bi bi-calendar-check me-1"></i> Registered:</span>
                            <span class="float-end">{{ $client->created_at->format('Y-m-d') }}</span>
                        </li>
                        @if($client->status_message)
                        <li class="list-group-item">
                            <span class="fw-bold"><i class="bi bi-chat-left-text me-1"></i> Status Message:</span>
                            <span class="float-end">{{ $client->status_message }}</span>
                        </li>
                        @endif
                    </ul>
                </div>
                
                <div class="text-center mb-4 col-md-6">
                    @if($client->avatar)
                        <img src="{{ asset('storage/'.$client->avatar) }}" class="rounded-circle shadow" width="200" height="200" alt="Client Profile">
                    @else
                        <div class="profile-image d-flex align-items-center justify-content-center bg-primary text-white mx-auto rounded-circle" style="width: 200px; height: 200px;">
                            <span class="display-4">{{ substr($client->name, 0, 1) }}</span>
                        </div>
                    @endif
                </div>
                
                @if($client->address)
                <div class="col-12 mt-3">
                    <h6 class="fw-bold"><i class="bi bi-house-door me-1"></i> Address:</h6>
                    <p>{{ $client->address }}</p>
                </div>
                @endif
            </div>
        </div>
        
        <!-- Actions -->
        <div class="col-md-12">
            <div class="detail-card bg-white p-4 rounded shadow-sm">
                <h5 class="mb-3">Actions</h5>
                
                <div class="d-flex gap-2">
                    @if($client->is_active)
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#blockClientModal">
                            <i class="bi bi-slash-circle me-1"></i> Block Client
                        </button>
                    @else
                        <form action="{{ route('admin.clients.unblock', $client) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-success">
                                <i class="bi bi-check-circle me-1"></i> Activate Client
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Block Client Modal -->
<div class="modal fade" id="blockClientModal" tabindex="-1" aria-labelledby="blockClientModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.clients.block', $client) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger bg-opacity-10">
                    <h5 class="modal-title text-danger" id="blockClientModalLabel">
                        <i class="bi bi-slash-circle-fill me-1"></i> Block Client
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to block this client? They will no longer be able to access their account.</p>
                    <div class="mb-3">
                        <label for="status_message" class="form-label">Reason for Blocking</label>
                        <textarea class="form-control" id="status_message" name="status_message" rows="3" required></textarea>
                        <div class="form-text">This message will be visible to the client.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm Block</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .profile-image {
        width: 200px;
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
    }
    .detail-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .list-group-item {
        border-left: 0;
        border-right: 0;
    }
    .list-group-item:first-child {
        border-top: 0;
    }
</style>
@endpush