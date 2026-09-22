    @extends('admin.MainComponent')

    @section('content')
            <!-- Main Content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Service Providers</h1>
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
                
                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="{{ route('admin.providers.index') }}" method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="status" class="form-label">Filter by Status</label>
                                <select name="status" id="status" class="form-select" onchange="this.form.submit()">
                                    <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All Providers</option>
                                    <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending Approval</option>
                                    <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="rejected" {{ $status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    <option value="blocked" {{ $status == 'blocked' ? 'selected' : '' }}>Blocked</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="search" class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, email or phone..." value="{{ request('search') }}">
                                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Providers Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Registration Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($providers as $provider)
                                        <tr>
                                            <td>{{ $provider->id }}</td>
                                            <td>{{ $provider->name }}</td>
                                            <td>{{ $provider->email }}</td>
                                            <td>{{ $provider->phone }}</td>
                                            <td>{{ ucfirst($provider->type) }}</td>
                                            <td>
                                                @if($provider->status == 'pending')
                                                    <span class="badge bg-warning status-badge">Pending</span>
                                                @elseif($provider->status == 'active')
                                                    <span class="badge bg-success status-badge">Active</span>
                                                @elseif($provider->status == 'rejected')
                                                    <span class="badge bg-danger status-badge">Rejected</span>
                                                @elseif($provider->status == 'blocked')
                                                    <span class="badge bg-dark status-badge">Blocked</span>
                                                @endif
                                            </td>
                                            <td>{{ $provider->created_at->format('Y-m-d') }}</td>
                                            <td>
                                                <div class="btn-group btn-group-sm me-2 gap-2">
                                                    <a href="{{ route('admin.providers.show', $provider) }}" class="btn btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    
                                                    @if($provider->status == 'pending')
                                                        <form action="{{ route('admin.providers.approve', $provider) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-success" title="Approve Provider">
                                                                <i class="bi bi-check-lg"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $provider->id }}" title="Reject Provider">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    @endif
                                                    
                                                    @if($provider->status == 'active')
                                                        <button type="button" class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#blockModal{{ $provider->id }}" title="Block Provider">
                                                            <i class="bi bi-slash-circle"></i>
                                                        </button>
                                                    @endif
                                                    
                                                    @if($provider->status == 'blocked')
                                                        <form action="{{ route('admin.providers.unblock', $provider) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-outline-success" title="Unblock Provider">
                                                                <i class="bi bi-unlock"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                                
                                                <!-- Reject Modal -->
                                                <div class="modal fade" id="rejectModal{{ $provider->id }}" tabindex="-1" aria-labelledby="rejectModalLabel{{ $provider->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form action="{{ route('admin.providers.reject', $provider) }}" method="POST">
                                                                @csrf
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="rejectModalLabel{{ $provider->id }}">Reject Provider</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label for="status_message" class="form-label">Reason for Rejection</label>
                                                                        <textarea class="form-control" id="status_message" name="status_message" rows="3" required></textarea>
                                                                        <div class="form-text">Please provide a reason for rejecting this provider.</div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Block Modal -->
                                                <div class="modal fade" id="blockModal{{ $provider->id }}" tabindex="-1" aria-labelledby="blockModalLabel{{ $provider->id }}" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form action="{{ route('admin.providers.block', $provider) }}" method="POST">
                                                                @csrf
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title" id="blockModalLabel{{ $provider->id }}">Block Provider</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <div class="mb-3">
                                                                        <label for="status_message" class="form-label">Reason for Blocking</label>
                                                                        <textarea class="form-control" id="status_message" name="status_message" rows="3" required></textarea>
                                                                        <div class="form-text">Please provide a reason for blocking this provider.</div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-dark">Block</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center">No service providers found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-center mt-4">
                            {{ $providers->appends(request()->query())->links() }}
                        </div>
                    </div>
                </div>
            </div>
            @endsection