@extends('admin.MainComponent')

@section('content')
<!-- Main Content -->
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-people me-2"></i>Clients Management</h1>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <!-- Filters -->
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filters</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.clients.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="is_active" id="is_active" class="form-select">
                        <option value="all" {{ request('is_active') === 'all' ? 'selected' : '' }}>All Clients</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, email or phone..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Clients Table -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-header bg-white border-bottom-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>Clients List</h5>
                <div class="text-muted small">
                    Showing {{ $clients->firstItem() }} to {{ $clients->lastItem() }} of {{ $clients->total() }} entries
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="border-top-0">#</th>
                            <th class="border-top-0">Client</th>
                            <th class="border-top-0">Email</th>
                            <th class="border-top-0">Phone</th>
                            <th class="border-top-0">Location</th>
                            <th class="border-top-0">Status</th>
                            <th class="border-top-0">Registered</th>
                            <th class="border-top-0 text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clients as $client)
                        <tr>
                            <td class="align-middle">#{{ $client->id }}</td>
                            <td class="align-middle">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3">
                                        @if($client->avatar)
                                            <img src="{{ asset('storage/'.$client->avatar) }}" class="rounded-circle" width="40" height="40" alt="Client Avatar">
                                        @else
                                            <img src="{{ asset('images/front/man.png') }}" class="rounded-circle" width="40" height="40" alt="Client Avatar">
                                        @endif
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $client->name }}</h6>
                                        <small class="text-muted">{{ $client->gender ? ucfirst($client->gender) : 'Not specified' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="align-middle">
                                <div>{{ $client->email }}</div>
                            </td>
                            <td class="align-middle">
                                <div>{{ $client->phone }}</div>
                            </td>
                            <td class="align-middle">
                                <div>{{ $client->city ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $client->address ? Str::limit($client->address, 15) : '' }}</small>
                            </td>
                            <td class="align-middle">
                                @if($client->is_active)
                                    <span class="badge bg-success bg-opacity-15 text-white">
                                        <i class="bi bi-check-circle me-1"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary bg-opacity-15 text-white">
                                        <i class="bi bi-x-circle me-1"></i> Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="align-middle">
                                {{ $client->created_at->format('M d, Y') }}
                                <div class="text-muted small">{{ $client->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="align-middle text-end">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="tooltip" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    @if($client->is_active)
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateModal{{ $client->id }}" data-bs-toggle="tooltip" title="Deactivate">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    @else
                                        <form action="{{ route('admin.clients.unblock', $client) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Activate">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Deactivate Modal -->
                        <div class="modal fade" id="deactivateModal{{ $client->id }}" tabindex="-1" aria-labelledby="deactivateModalLabel{{ $client->id }}" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form action="{{ route('admin.clients.block', $client) }}" method="POST">
                                        @csrf
                                        @method('POST')
                                        <div class="modal-header bg-danger bg-opacity-10">
                                            <h5 class="modal-title text-danger" id="deactivateModalLabel{{ $client->id }}">
                                                <i class="bi bi-slash-circle-fill me-1"></i> Deactivate Client
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Are you sure you want to deactivate this client?</p>
                                            <div class="mb-3">
                                                <label for="deactivation_reason" class="form-label">Reason (optional)</label>
                                                <textarea class="form-control" id="deactivation_reason" name="deactivation_reason" rows="3"></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Deactivate</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-people text-muted" style="font-size: 2.5rem;"></i>
                                    <h5 class="mt-3">No clients found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            @if($clients->hasPages())
            <div class="card-footer bg-white border-top-0 py-3">
                <nav aria-label="Clients pagination">
                    {{ $clients->appends(request()->query())->links() }}
                </nav>
            </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Enable tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    })
</script>
@endpush
@endsection