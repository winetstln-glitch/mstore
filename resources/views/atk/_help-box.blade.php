
<div class="card mb-4 border-info">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0"><i class="fa-solid fa-circle-info me-2"></i>{{ $title ?? 'Petunjuk' }}</h6>
        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#helpBoxContent" aria-expanded="true" aria-controls="helpBoxContent">
            <i class="fa-solid fa-chevron-down"></i>
        </button>
    </div>
    <div class="collapse show" id="helpBoxContent">
        <div class="card-body">
            {!! $content !!}
        </div>
    </div>
</div>
