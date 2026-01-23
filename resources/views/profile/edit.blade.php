@extends('layouts.app')

@section('title', __('Profile Settings'))

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<style>
    .img-container {
        max-height: 500px;
        display: block;
    }
    .img-container img {
        max-width: 100%;
        display: block;
    }
    .preview {
        overflow: hidden;
        width: 160px; 
        height: 160px;
        margin: 10px;
        border: 1px solid red;
    }
</style>
@endpush

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="mb-4">
                <h2 class="fw-bold">{{ __('Profile Settings') }}</h2>
                <p class="text-body-secondary">{{ __('Manage your account settings and preferences.') }}</p>
            </div>

            <!-- Profile Information -->
            <div class="card shadow-sm border-0 border-top border-4 border-primary mb-4">
                <div class="card-header py-3">
                    <h5 class="mb-0 fw-bold">{{ __('Profile Information') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-body-secondary small mb-4">
                        {{ __("Update your account's profile information and email address.") }}
                    </p>

                    @if (session('status') === 'profile-updated')
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-check-circle me-1"></i> {{ __('Profile updated successfully.') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="post" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('patch')

                        <div class="mb-3">
                            <label for="avatar" class="form-label fw-bold">{{ __('Profile Photo') }}</label>
                            <div class="mb-2">
                                <img id="avatar-preview" src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3f6ad8&color=fff' }}" alt="Avatar" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                            <input type="hidden" name="avatar_base64" id="avatar_base64">
                            <input id="avatar" name="avatar" type="file" class="form-control @error('avatar') is-invalid @enderror" accept="image/*">
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold">{{ __('Name') }}</label>
                            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold">{{ __('Email') }}</label>
                            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-save me-1"></i> {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Update Password -->
            <div class="card shadow-sm border-0 border-top border-4 border-warning">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">{{ __('Update Password') }}</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">
                        {{ __('Ensure your account is using a long, random password to stay secure.') }}
                    </p>

                    @if (session('status') === 'password-updated')
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fa-solid fa-check-circle me-1"></i> {{ __('Password updated successfully.') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="post" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-bold">{{ __('Current Password') }}</label>
                            <input id="current_password" name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror" autocomplete="current-password">
                            @error('current_password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold">{{ __('New Password') }}</label>
                            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-bold">{{ __('Confirm Password') }}</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation') is-invalid @enderror" autocomplete="new-password">
                            @error('password_confirmation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <button type="submit" class="btn btn-warning text-white">
                                <i class="fa-solid fa-key me-1"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Delete Account (Optional - Add if needed or leave out for now) -->
             <div class="card shadow-sm border-0 border-top border-4 border-danger mt-4">
                <div class="card-header bg-body py-3">
                    <h5 class="mb-0 fw-bold text-danger">Delete Account</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">
                        Once your account is deleted, all of its resources and data will be permanently deleted.
                    </p>
                    
                    <form method="post" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
                        @csrf
                        @method('delete')

                        <div class="mb-3">
                             <label for="password_delete" class="form-label fw-bold">Password</label>
                             <input id="password_delete" name="password" type="password" class="form-control @error('password', 'userDeletion') is-invalid @enderror" placeholder="Enter password to confirm">
                             @error('password', 'userDeletion')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-danger">
                            <i class="fa-solid fa-trash me-1"></i> Delete Account
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Crop Modal -->
<div class="modal fade" id="cropModal" tabindex="-1" aria-labelledby="cropModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cropModalLabel">{{ __('Crop Image') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="img-container">
                    <div class="row">
                        <div class="col-md-8">
                            <img id="image-to-crop" src="" alt="Picture">
                        </div>
                        <div class="col-md-4">
                            <div class="preview"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button type="button" class="btn btn-primary" id="cropButton">{{ __('Crop & Save') }}</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var avatarInput = document.getElementById('avatar');
        var avatarBase64Input = document.getElementById('avatar_base64');
        var avatarPreview = document.getElementById('avatar-preview');
        var image = document.getElementById('image-to-crop');
        var cropModalElement = document.getElementById('cropModal');
        var cropModal = new bootstrap.Modal(cropModalElement);
        var cropper;
        var cropSuccess = false;

        avatarInput.addEventListener('change', function (e) {
            var files = e.target.files;
            if (files && files.length > 0) {
                var file = files[0];
                var url = URL.createObjectURL(file);
                
                // Restore name attribute in case it was removed previously
                avatarInput.setAttribute('name', 'avatar');
                
                image.src = url;
                cropSuccess = false; 
                cropModal.show();
            }
        });

        cropModalElement.addEventListener('shown.bs.modal', function () {
            if (cropper) {
                cropper.destroy();
            }
            
            cropper = new Cropper(image, {
                aspectRatio: 1,
                viewMode: 1,
                preview: '.preview',
                autoCropArea: 1,
            });
        });

        cropModalElement.addEventListener('hidden.bs.modal', function () {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
            
            if (!cropSuccess) {
                avatarInput.value = ''; // Reset input if cancelled
                avatarBase64Input.value = ''; // Reset base64
                // Reset preview to original image
                avatarPreview.src = "{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) . '&background=3f6ad8&color=fff' }}";
            }
        });

        document.getElementById('cropButton').addEventListener('click', function (e) {
            e.preventDefault(); // Prevent default button behavior
            var btn = this;
            var originalText = '{{ __("Crop & Save") }}';
            
            // UI Feedback
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Processing...';

            // Use setTimeout to allow UI to update before heavy processing
            setTimeout(function() {
                try {
                    if (cropper) {
                        // Get the cropped canvas
                        var canvas = cropper.getCroppedCanvas({
                            width: 500,
                            height: 500,
                            fillColor: '#fff',
                            imageSmoothingEnabled: true,
                            imageSmoothingQuality: 'high',
                        });

                        if (!canvas) {
                            throw new Error('Could not crop image. Canvas creation failed.');
                        }

                        // Get Base64 string
                        var base64data = canvas.toDataURL('image/jpeg', 0.85);
                        
                        // Set hidden input value
                        avatarBase64Input.value = base64data;
                        
                        // Update preview
                        avatarPreview.src = base64data;
                        
                        // CRITICAL: Remove the name attribute from the file input so it is NOT submitted
                        // This prevents "Post too large" errors and double submission
                        avatarInput.removeAttribute('name');

                        cropSuccess = true;
                        cropModal.hide();
                    }
                } catch (e) {
                    console.error(e);
                    alert('Error: ' + e.message);
                } finally {
                    // Reset button state
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            }, 50);
        });
    });
</script>
@endpush
@endsection