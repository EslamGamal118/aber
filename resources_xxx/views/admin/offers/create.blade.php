@extends('admin.MainComponent')

@section('content')
<!-- Main Content -->
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-plus-circle me-2"></i>Create New Offer</h1>
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
            <form action="{{ route('admin.offers.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="title" class="form-label">Offer Title *</label>
                        <input type="text" class="form-control @error('title') is-invalid @enderror" 
                               id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="image" class="form-label">Offer Image *</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror" 
                               id="image" name="image" accept="image/*" required>
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Recommended size: 800x400px (JPG, PNG, GIF)</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="start_date" class="form-label">Start Date *</label>
                        <input type="datetime-local" class="form-control @error('start_date') is-invalid @enderror" 
                               id="start_date" name="start_date" value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="end_date" class="form-label">End Date *</label>
                        <input type="datetime-local" class="form-control @error('end_date') is-invalid @enderror" 
                               id="end_date" name="end_date" value="{{ old('end_date') }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-outline-secondary px-4">
                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Create Offer
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <div>
                                <strong>Offer Duration Tip:</strong> Make sure your end date is after the start date.
                                Offers will automatically become active/inactive based on these dates.
                            </div>
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

    // Initialize min dates on page load
    document.addEventListener('DOMContentLoaded', function() {
        const startDateField = document.getElementById('start_date');
        const now = new Date();
        const timezoneOffset = now.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(now - timezoneOffset)).toISOString().slice(0, 16);
        
        startDateField.min = localISOTime;
        
        if (!startDateField.value) {
            startDateField.value = localISOTime;
        }
    });
</script>
@endpush