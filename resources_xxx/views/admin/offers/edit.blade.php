@extends('admin.MainComponent')

@section('content')
<!-- Main Content -->
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-pencil-square me-2"></i>Edit Offer</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.offers.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Offers
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="mb-0"><i class="bi bi-percent me-2"></i>Offer Details</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.offers.update', $offer->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Offer Title *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title', $offer->title) }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label">Offer Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" 
                               id="image" name="image" accept="image/*">
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Recommended size: 800x400px (JPG, PNG, GIF)</small>
                        @if($offer->image)
                            <div class="mt-2">
                                <img src="{{ $offer->image }}" alt="Current Offer Image" class="img-thumbnail" style="max-height: 100px;">
                                <p class="small text-muted mb-0">Current image</p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-5">
                        <label for="start_date" class="form-label">Start Date *</label>
                        <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" 
                               id="start_date" name="start_date" value="{{ old('start_date', \Carbon\Carbon::parse($offer->start_date)->format('Y-m-d\TH:i')) }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-5">
                        <label for="end_date" class="form-label">End Date *</label>
                        <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" 
                               id="end_date" name="end_date" value="{{ old('end_date', \Carbon\Carbon::parse($offer->end_date)->format('Y-m-d\TH:i')) }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch">
                            <input type="hidden" name="status" value="inactive">
                            <input class="form-check-input @error('status') is-invalid @enderror" 
                                   type="checkbox" role="switch" id="status" name="status" 
                                   value="active" {{ old('status', $offer->status) == 'active' ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">
                                {{ $offer->status == 'active' ? 'Active' : 'Inactive' }}
                            </label>
                            @error('status')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-warning d-flex align-items-center">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <div>
                                <strong>Status:</strong> 
                                <span class="badge bg-{{ $offer->status == 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($offer->status) }}
                                </span>
                                <span class="ms-2">
                                    @if($offer->status == 'active')
                                        This offer is currently visible to customers.
                                    @else
                                        This offer is currently hidden from customers.
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Update Offer
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Set minimum end date based on start date selection
    document.getElementById('start_date').addEventListener('change', function() {
        const startDate = this.value;
        const endDateField = document.getElementById('end_date');
        endDateField.min = startDate;
        
        // If current end date is before new start date, reset it
        if (endDateField.value && endDateField.value < startDate) {
            endDateField.value = '';
        }
    });

    // Update status label when toggle is changed
    document.getElementById('status').addEventListener('change', function() {
        const label = this.nextElementSibling;
        label.textContent = this.checked ? 'Active' : 'Inactive';
    });

    // Initialize min dates on page load
    document.addEventListener('DOMContentLoaded', function() {
        const startDateField = document.getElementById('start_date');
        const now = new Date();
        const timezoneOffset = now.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
        
        // Only set min if the current start date is in the future
        if (new Date(startDateField.value) > now) {
            startDateField.min = localISOTime;
        }

        // Show warning if offer is expired but still active
        const endDate = new Date(document.getElementById('end_date').value);
        const statusToggle = document.getElementById('status');
        
        if (endDate < now && statusToggle.checked) {
            const warningDiv = document.createElement('div');
            warningDiv.className = 'alert alert-danger mt-3';
            warningDiv.innerHTML = `
                <i class="bi bi-exclamation-octagon-fill me-2"></i>
                <strong>Warning:</strong> This offer has expired but is still active.
                Consider deactivating it or updating the end date.
            `;
            statusToggle.closest('.row').after(warningDiv);
        }
    });
</script>
@endpush