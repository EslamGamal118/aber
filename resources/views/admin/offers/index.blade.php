@extends('admin.MainComponent')

@section('content')
<!-- Main Content -->
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-percent me-2"></i>Manage Offers</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.offers.create') }}" class="btn btn-sm btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Create New Offer
            </a>
        </div>
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

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Current Offers</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Title</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($offers as $offer)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if($offer->image)
                                    <img src="{{ $offer->image }}" alt="{{ $offer->title }}" class="img-thumbnail" style="width: 80px; height: 60px; object-fit: cover;">
                                @else
                                    <div class="img-thumbnail d-flex align-items-center justify-content-center" style="width: 80px; height: 60px; background: #f8f9fa;">
                                        <i class="bi bi-image text-muted"></i>
                                    </div>
                                @endif
                            </td>
                            <td>{{ $offer->title }}</td>
                            <td>{{ $offer->start_date->format('M d, Y') }} - {{ $offer->end_date->format('M d, Y') }}</td>
                            <td>
                                <span class="badge {{ $offer->status == 'active' ? 'bg-success' : 'bg-secondary' }}">{{ $offer->status }}</span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('admin.offers.show', $offer) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('admin.offers.edit', $offer) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <form action="{{ route('admin.offers.destroy', $offer) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this offer?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-exclamation-circle fs-4 text-muted"></i>
                                <p class="text-muted mb-0">No offers found. Create your first offer!</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($offers->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $offers->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
