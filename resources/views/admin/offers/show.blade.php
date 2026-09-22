@extends('admin.MainComponent')

@section('content')
<!-- Main Content -->
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-percent me-2"></i>Offer Details</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.offers.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Offers
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-tag me-2"></i>{{ $offer->title }}</h5>
                <span class="badge bg-{{ $offer->status == 'active' ? 'success' : ($offer->status == 'upcoming' ? 'warning text-dark' : 'secondary') }}">
                    {{ ucfirst($offer->status) }}
                </span>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row">
                <!-- Offer Image -->
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0">
                        <div class="card-body text-center p-4">
                            @if($offer->image)
                            <img src="{{ $offer->image }}" alt="{{ $offer->title }}" 
                                 class="img-fluid rounded" style="max-height: 300px; width: auto;">
                            @else
                            <div class="d-flex align-items-center justify-content-center bg-light" 
                                 style="height: 200px; border-radius: .25rem;">
                                <i class="bi bi-image text-muted" style="font-size: 3rem;"></i>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Offer Details -->
                <div class="col-md-8">
                    <div class="row">
                        <div class="col-12 mb-3">
                            <h5 class="border-bottom pb-2">Offer Information</h5>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">
                                        <i class="bi bi-calendar-event me-2"></i>Duration
                                    </h6>
                                    <div class="d-flex flex-column">
                                        <div class="mb-2">
                                            <span class="fw-bold">Starts:</span>
                                            <span class="ms-2">{{ $offer->start_date->format('M d, Y h:i A') }}</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold">Ends:</span>
                                            <span class="ms-2">{{ $offer->end_date->format('M d, Y h:i A') }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light border-0 h-100">
                                <div class="card-body">
                                    <h6 class="card-title text-muted mb-3">
                                        <i class="bi bi-clock-history me-2"></i>Time Remaining
                                    </h6>
                                    <div>
                                        @if($offer->status == 'active')
                                            <span class="badge bg-info">
                                                {{ $offer->end_date->diffForHumans() }}
                                            </span>
                                        @elseif($offer->status == 'upcoming')
                                            <span class="badge bg-warning text-dark">
                                                Starts {{ $offer->start_date->diffForHumans() }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">
                                                Ended {{ $offer->end_date->diffForHumans() }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Created/Updated Info -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card bg-light border-0">
                                <div class="card-body py-2">
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span>
                                            <i class="bi bi-plus-circle me-1"></i>
                                            Created: {{ $offer->created_at->format('M d, Y h:i A') }}
                                        </span>
                                        <span>
                                            <i class="bi bi-pencil-square me-1"></i>
                                            Last Updated: {{ $offer->updated_at->format('M d, Y h:i A') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.offers.edit', $offer) }}" class="btn btn-primary px-4">
                            <i class="bi bi-pencil-square me-1"></i> Edit Offer
                        </a>
                        <button type="button" class="btn btn-outline-danger px-4" 
                                data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="bi bi-trash me-1"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>
                    Confirm Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the offer "<strong>{{ $offer->title }}</strong>"? 
                This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </button>
                <form action="{{ route('admin.offers.destroy', $offer) }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash-fill me-1"></i> Delete Permanently
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card.bg-light {
        background-color: #f8f9fa !important;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.5em 0.75em;
    }
</style>
@endpush