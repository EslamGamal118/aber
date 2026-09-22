@extends('admin.MainComponent')

@section('content')
<!-- Main Content -->
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-cart-check me-2"></i>Orders Management</h1>
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
            <form action="{{ route('admin.orders.index') }}" method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Statuses</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="payment_status" class="form-label">Payment Status</label>
                    <select name="payment_status" id="payment_status" class="form-select">
                        <option value="all" {{ request('payment_status') === 'all' ? 'selected' : '' }}>All Payments</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search by order ID, customer name..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel-fill me-1"></i> Apply
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-header bg-white border-bottom-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>Orders List</h5>
                <div class="text-muted small">
                    Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} entries
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                        <tr>
                            <td>#{{ $order->partitionId ?? $order->id }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm me-3">
                                        @if($order->client?->avatar)
                                            <img src="{{ asset('storage/'.$order->client->avatar) }}" class="rounded-circle" width="40" height="40" alt="Customer">
                                        @else
                                            <img src="{{ asset('images/front/man.png') }}" class="rounded-circle" width="40" height="40" alt="Customer">
                                        @endif
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $order->buyerName ?? $order->client?->name ?? 'Guest' }}</h6>
                                        <small class="text-muted">{{ $order->client?->phone ?? 'N/A' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                {{ $order->total }}
                                @if($order->additionPrice)
                                    <small class="text-muted d-block">+{{ $order->additionPrice }} additions</small>
                                @endif
                            </td>
                            <td>
                                @php
                                    $statusClass = [
                                        'pending' => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ][$order->status] ?? 'info';
                                @endphp
                                <span class="badge bg-{{ $statusClass }} bg-opacity-15 text-white">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td>
                                @if($order->is_paid)
                                    <span class="badge bg-success bg-opacity-15 text-white">
                                        <i class="bi bi-check-circle me-1"></i> Paid
                                    </span>
                                @else
                                    <span class="badge bg-danger bg-opacity-15 text-white">
                                        <i class="bi bi-x-circle me-1"></i> Unpaid
                                    </span>
                                @endif
                            </td>
                            <td>
                                {{ $order->created_at->format('M d, Y') }}
                                <div class="text-muted small">{{ $order->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="tooltip" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="d-flex flex-column align-items-center">
                                    <i class="bi bi-cart-x text-muted" style="font-size: 2.5rem;"></i>
                                    <h5 class="mt-3">No orders found</h5>
                                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
           <!-- Replace the existing pagination section with this: -->
@if($orders->hasPages())
<div class="card-footer bg-white border-top-0 py-3">
    <div class="d-flex justify-content-between align-items-center">
        <div class="text-muted small">
            Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} entries
        </div>
        
        <nav aria-label="Orders pagination">
            <ul class="pagination pagination-sm mb-0">
                {{-- Previous Page Link --}}
                @if($orders->onFirstPage())
                    <li class="page-item disabled">
                        <span class="page-link">&laquo;</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $orders->previousPageUrl() }}" rel="prev">&laquo;</a>
                    </li>
                @endif

                {{-- Pagination Elements --}}
                @foreach($orders->links()->elements[0] as $page => $url)
                    @if($page == $orders->currentPage())
                        <li class="page-item active" aria-current="page">
                            <span class="page-link">{{ $page }}</span>
                        </li>
                    @else
                        <li class="page-item">
                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                        </li>
                    @endif
                @endforeach

                {{-- Next Page Link --}}
                @if($orders->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $orders->nextPageUrl() }}" rel="next">&raquo;</a>
                    </li>
                @else
                    <li class="page-item disabled">
                        <span class="page-link">&raquo;</span>
                    </li>
                @endif
            </ul>
        </nav>
    </div>
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