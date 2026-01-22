@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
        <div class="card shadow-sm border-0 border-top border-4 border-primary">
            <div class="card-body p-4">
                <h4 class="text-center fw-bold mb-4">{{ __('Technician Attendance') }}</h4>
                
                <div class="text-center mb-4">
                    <div class="display-4 fw-bold font-monospace" id="clock">00:00:00</div>
                    <div class="text-muted">{{ now()->format('l, d F Y') }}</div>
                </div>

                @if(!Auth::user()->avatar)
                    <div class="alert alert-danger text-center mb-4">
                        <i class="fa-solid fa-triangle-exclamation fs-1 mb-2"></i>
                        <h5 class="fw-bold">{{ __('Profile Photo Required') }}</h5>
                        <p>{{ __('You must set your profile photo before you can mark attendance.') }}</p>
                        <a href="{{ route('profile.edit') }}" class="btn btn-outline-danger">{{ __('Go to Profile') }}</a>
                    </div>
                @else
                    <div id="face-model-status" class="alert alert-info text-center mb-4" style="display: none;">
                        <i class="fa-solid fa-spinner fa-spin me-2"></i> {{ __('Loading Face Recognition Models...') }}
                    </div>

                    <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
                    <i class="fa-solid fa-info-circle fs-4 me-3"></i>
                    <div>
                        <div class="small">{{ __('Clock In') }}: <span class="fw-bold">{{ $clockInStart }} - {{ $clockInEnd }} WIB</span></div>
                        <div class="small">{{ __('Clock Out') }}: <span class="fw-bold">{{ $clockOutStart }} - {{ $clockOutEnd }} WIB</span></div>
                    </div>
                </div>

                {{-- Alerts handled by SweetAlert in Layout --}}

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($todayAttendance && $todayAttendance->clock_out)
                    <!-- Already completed for today -->
                    <div class="text-center p-4 bg-success-subtle rounded border border-success-subtle">
                        <div class="display-1 text-success mb-3">
                            <i class="fa-solid fa-check-circle"></i>
                        </div>
                        <h4 class="fw-bold text-success">{{ __('Attendance Completed') }}</h4>
                        <p class="text-success mb-2">{{ __('You have clocked out at :time.', ['time' => $todayAttendance->clock_out->format('H:i')]) }}</p>
                        <small class="text-muted">{{ __('See you tomorrow!') }}</small>
                    </div>

                @elseif(Auth::user()->avatar)
                    @if($todayAttendance && !$todayAttendance->clock_out)
                        <!-- Clock Out Form -->
                        <form action="{{ route('attendance.update', $todayAttendance->id) }}" method="POST" enctype="multipart/form-data" id="attendanceForm">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">

                            <div class="alert alert-warning text-center mb-4">
                                {!! __('You clocked in at :time.', ['time' => '<strong>' . $todayAttendance->clock_in->format('H:i') . '</strong>']) !!}
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('Selfie Photo (Clock Out)') }}</label>
                                <input type="file" name="photo" accept="image/*" capture="user" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('Notes (Optional)') }}</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('Task summary...') }}"></textarea>
                            </div>

                            <button type="submit" id="submitBtn" class="btn btn-danger w-100 py-3 fw-bold shadow-sm" disabled>
                                <i class="fa-solid fa-sign-out-alt me-2"></i> {{ __('CLOCK OUT') }}
                            </button>
                        </form>

                    @else
                        <!-- Clock In Form -->
                        <form action="{{ route('attendance.store') }}" method="POST" enctype="multipart/form-data" id="attendanceForm">
                            @csrf
                            <input type="hidden" name="latitude" id="latitude">
                            <input type="hidden" name="longitude" id="longitude">

                            <div class="mb-3">
                                <label class="form-label fw-bold">{{ __('Selfie Photo (Clock In)') }}</label>
                                <input type="file" name="photo" accept="image/*" capture="user" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">{{ __('Notes (Optional)') }}</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="{{ __('Plan for today...') }}"></textarea>
                            </div>

                            <button type="submit" id="submitBtn" class="btn btn-primary w-100 py-3 fw-bold shadow-sm" disabled>
                                <i class="fa-solid fa-sign-in-alt me-2"></i> {{ __('CLOCK IN') }}
                            </button>
                        </form>
                    @endif
                @endif
                @endif
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Face Recognition Logic
    const MODEL_URL = 'https://cdn.jsdelivr.net/gh/justadudewhohacks/face-api.js/weights';
    let userDescriptor = null;
    const hasAvatar = {{ Auth::user()->avatar ? 'true' : 'false' }};
    const faceVerificationEnabled = {{ $faceVerificationEnabled == '1' ? 'true' : 'false' }};
    const avatarUrl = "{{ Auth::user()->avatar ? asset('storage/' . Auth::user()->avatar) : '' }}";
    const statusDiv = document.getElementById('face-model-status');
    const submitBtns = document.querySelectorAll('#submitBtn');

    async function loadModels() {
        if (!hasAvatar || !faceVerificationEnabled) return;
        
        if(statusDiv) statusDiv.style.display = 'block';
        
        try {
            await Promise.all([
                faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
            ]);

            // Load User Descriptor
            const img = await faceapi.fetchImage(avatarUrl);
            const detection = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();
            
            if (detection) {
                userDescriptor = detection.descriptor;
                if(statusDiv) {
                    statusDiv.classList.remove('alert-info');
                    statusDiv.classList.add('alert-success');
                    statusDiv.innerHTML = '<i class="fa-solid fa-check-circle me-2"></i> {{ __("Face Recognition Ready") }}';
                    setTimeout(() => { statusDiv.style.display = 'none'; }, 2000);
                }
                // Enable inputs
                document.querySelectorAll('input[name="photo"]').forEach(input => input.disabled = false);
            } else {
                if(statusDiv) {
                    statusDiv.classList.remove('alert-info');
                    statusDiv.classList.add('alert-danger');
                    statusDiv.innerHTML = '<i class="fa-solid fa-exclamation-circle me-2"></i> {{ __("Face not found in Profile Photo. Please update your profile photo.") }}';
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Profile Photo Error',
                    text: '{{ __("Could not detect a face in your profile photo. Please upload a clear photo of your face in Profile settings.") }}',
                    footer: '<a href="{{ route("profile.edit") }}">{{ __("Go to Profile") }}</a>'
                });
            }
        } catch (error) {
            console.error(error);
            if(statusDiv) {
                statusDiv.classList.remove('alert-info');
                statusDiv.classList.add('alert-danger');
                statusDiv.innerHTML = '<i class="fa-solid fa-exclamation-circle me-2"></i> {{ __("Error loading Face Recognition models.") }}';
            }
        }
    }

    if (hasAvatar && faceVerificationEnabled) {
        loadModels();
    } else if (!faceVerificationEnabled) {
        // If verification disabled, ensure inputs are enabled
        document.querySelectorAll('input[name="photo"]').forEach(input => input.disabled = false);
    }

    // On file change
    document.querySelectorAll('input[name="photo"]').forEach(input => {
        input.addEventListener('change', async function(e) {
            if (!e.target.files.length) return;
            
            // If verification is disabled, just enable submit button
            if (!faceVerificationEnabled) {
                submitBtns.forEach(btn => btn.disabled = false);
                return;
            }

            if (!userDescriptor) {
                Swal.fire('Error', '{{ __("System not ready or Profile Photo invalid.") }}', 'error');
                e.target.value = '';
                return;
            }

            Swal.fire({ 
                title: '{{ __("Verifying Face...") }}', 
                text: '{{ __("Please wait while we verify your identity.") }}',
                allowOutsideClick: false, 
                didOpen: () => Swal.showLoading() 
            });

            try {
                const file = e.target.files[0];
                const img = await faceapi.bufferToImage(file);
                
                const detection = await faceapi.detectSingleFace(img).withFaceLandmarks().withFaceDescriptor();
                
                if (!detection) {
                    Swal.fire('Error', '{{ __("Face not detected in the photo. Please try again with better lighting and a clear view of your face.") }}', 'error');
                    e.target.value = '';
                    submitBtns.forEach(btn => btn.disabled = true);
                    return;
                }

                const distance = faceapi.euclideanDistance(userDescriptor, detection.descriptor);
                // Distance < 0.6 is usually a match. 0.5 is safer.
                const threshold = 0.55;
                
                if (distance > threshold) {
                    Swal.fire({
                        icon: 'error',
                        title: '{{ __("Verification Failed") }}',
                        text: '{{ __("Face does not match your profile photo.") }}',
                        footer: 'Match Score: ' + Math.round((1-distance)*100) + '%'
                    });
                    e.target.value = '';
                    submitBtns.forEach(btn => btn.disabled = true);
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("Verified!") }}',
                        text: '{{ __("Face matched successfully.") }}',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    submitBtns.forEach(btn => btn.disabled = false);
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', '{{ __("An error occurred during verification.") }}', 'error');
                e.target.value = '';
                submitBtns.forEach(btn => btn.disabled = true);
            }
        });
    });


    // Clock
    function updateClock() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { hour12: false });
        document.getElementById('clock').textContent = timeString;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Geolocation
    document.addEventListener('DOMContentLoaded', function() {
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(function(position) {
                const latInput = document.getElementById('latitude');
                const lngInput = document.getElementById('longitude');
                if(latInput && lngInput) {
                    latInput.value = position.coords.latitude;
                    lngInput.value = position.coords.longitude;
                }
            }, function(error) {
                console.error("Error getting location:", error);
                // alert("{{ __('Please enable location services to mark attendance.') }}");
            });
        } else {
            // alert("{{ __('Geolocation is not supported by this browser.') }}");
            console.error("Geolocation is not supported by this browser.");
        }
    });
</script>
@endsection
