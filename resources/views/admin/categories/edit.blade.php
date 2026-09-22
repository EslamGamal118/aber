@extends('admin.MainComponent')

@section('content')
<!-- Main Content -->
<div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2"><i class="bi bi-tag me-2"></i>Edit Category</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="{{ route('admin.categories.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Categories
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom-0 py-3">
            <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Category Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.categories.update', $category) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', $category->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="icon" class="form-label">Icon (Bootstrap Icons)</label>
                        <div class="input-group">
                            <span class="input-group-text">bi-</span>
                            <input type="text" class="form-control @error('icon') is-invalid @enderror" 
                                   id="icon" name="icon" value="{{ old('icon', $category->icon) }}" 
                                   placeholder="e.g. 'shopping-bag' for bi-shopping-bag">
                            @error('icon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="text-muted">Leave blank if no icon needed</small>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="image" class="form-label">Category Image</label>
                        <input type="file" class="form-control @error('image') is-invalid @enderror"
                               id="image" name="image" accept="image/jpeg,image/png,image/webp">
                        @error('image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">JPEG, PNG or WEBP, max 2 MB</small>
                    </div>
                    @if($category->image)
                        <div class="col-md-6">
                            <label class="form-label d-block">Current Image</label>
                            <img src="{{ $category->image }}" alt="{{ $category->name }}"
                                 class="img-thumbnail mb-2" style="max-height: 120px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image" value="1">
                                <label class="form-check-label" for="remove_image">Remove current image</label>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Status *</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" 
                                   id="active" name="active" value="1" 
                                   {{ old('active', $category->active) ? 'checked' : '' }}>
                            <label class="form-check-label" for="active">
                                Active Category
                            </label>
                        </div>
                        <small class="text-muted">Inactive categories won't be shown to customers</small>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-end align-items-center h-100 gap-2">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save me-1"></i> Update Category
                            </button>
                            <button type="button" class="btn btn-outline-danger" 
                                    data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="bi bi-trash me-1"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="bi bi-info-circle-fill me-2"></i>
                            <div>
                                <strong>Icon Tip:</strong> Use icon names from 
                                <a href="https://icons.getbootstrap.com/" target="_blank" class="alert-link">
                                    Bootstrap Icons
                                </a>. Just enter the part after "bi-" (e.g. for "bi-heart", enter "heart")
                            </div>
                        </div>
                    </div>
                </div>
            </form>
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
                Are you sure you want to delete the category "<strong>{{ $category->name }}</strong>"? 
                This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Cancel
                </button>
                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST">
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
    .form-switch .form-check-input {
        width: 2.5em;
        height: 1.5em;
    }
</style>
@endpush