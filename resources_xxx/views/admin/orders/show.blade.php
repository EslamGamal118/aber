@extends('admin.MainComponent')

@section('content')
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-receipt me-2"></i>Order Details #{{ $order->id }}</h1>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Orders
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <!-- Order Items -->
        <div class="col-12">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <h5><i class="bi bi-cart me-2"></i> Order Items</h5>
                </div>
                <div class="card-body table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Description</th>
                                <th class="text-end">Price</th>
                                <th>Additional Price</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $grandTotal = 0;
                            @endphp

                            @foreach($items as $item)
                                <tr>
                                    <td><strong>{{ App\Models\Product::find($item->elementId)->name ?? 'Unknown Item' }}</strong></td>
                                    <td>{{ Str::limit(App\Models\Product::find($item->elementId)->description ?? '-', 50) }}</td>
                                    <td class="text-end">{{ number_format(App\Models\Product::find($item->elementId)->price ?? 0, 2) }}</td>
                                    <td>{{ $item->additionPrice ?? '-' }}</td>
                                    <td class="text-end">{{ $item->quantity }}</td>
                                    <td class="text-end">{{ number_format(($item->total ?? 0) * $item->quantity, 2) }}</td>
                                </tr>
                                @endforeach
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">Promo Code Discount:</td>
                                <td class="text-end">{{ number_format($order->promoCodeDiscount, 2) }}</td>
                            </tr>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">Total Amount:</td>
                                <td class="text-end">{{ number_format($order->total, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if($order->notes)
                <div class="card mb-4 shadow-sm">
                    <div class="card-header"><h5><i class="bi bi-chat-left-text me-2"></i>Special Instructions</h5></div>
                    <div class="card-body">{{ $order->notes }}</div>
                </div>
            @endif
        </div>

        <!-- Order and Customer Info -->
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5><i class="bi bi-info-circle me-2"></i>Order Info</h5></div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">                        
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Order Status:</span>
                            <span class="badge bg-{{ $order->status === 'completed' ? 'success' : ($order->status === 'cancelled' ? 'danger' : 'warning') }}">{{ ucfirst($order->status) }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Payment Status:</span>
                            <span class="badge bg-{{ $order->is_paid ? 'success' : 'danger' }}">{{ $order->is_paid ? 'Paid' : 'Unpaid' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Order Date:</span>
                            <span>{{ $order->created_at->format('M d, Y h:i A') }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Payment Method:</span>
                            <span>{{ ucfirst($order->payment_method) }}</span>
                        </li>
                        @if($order->transaction_id)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Transaction ID:</span>
                                <span>{{ $order->transaction_id }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header"><h5><i class="bi bi-person me-2"></i>Customer Details</h5></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar me-3">
                            @if($order->client?->avatar)
                                <img src="{{ asset('storage/'.$order->client->avatar) }}" class="rounded-circle" width="60" height="60" alt="Customer">
                            @else
                                <img src="{{ asset('images/front/man.png') }}" class="rounded-circle" width="60" height="60" alt="Customer">
                            @endif
                        </div>
                        <div>
                            <h6 class="mb-0">{{ $order->buyerName ?? $order->client?->name ?? 'Guest' }}</h6>
                            <small class="text-muted">{{ $order->client?->phone ?? 'N/A' }}</small>
                        </div>
                    </div>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item"><span class="fw-bold">Email:</span> {{ $order->client?->email ?? $order->email ?? 'N/A' }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
    }
    .list-group-item {
        border-left: 0;
        border-right: 0;
    }
</style>
@endpush
