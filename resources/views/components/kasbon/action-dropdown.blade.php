@props([
    'editRoute' => null,
    'deleteRoute' => null,
    'installmentRoute' => null,
    'deleteConfirm' => 'Hapus data ini?',
])

<div class="dropdown">
    <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="fas fa-ellipsis-v"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        @if($editRoute)
            <li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#{{ $editRoute }}">
                <i class="fas fa-edit me-2"></i> Edit
            </button></li>
        @endif
        @if($installmentRoute)
            <li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#{{ $installmentRoute }}">
                <i class="fas fa-receipt me-2"></i> Tambah Cicilan
            </button></li>
        @endif
        @if($deleteRoute)
            <li><hr class="dropdown-divider"></li>
            <li>
                <form action="{{ $deleteRoute }}" method="POST" class="d-inline" data-no-loading="true">
                    @csrf
                    @method('DELETE')
                    <button class="dropdown-item text-danger" type="submit" onclick="return confirm('{{ $deleteConfirm }}')">
                        <i class="fas fa-trash me-2"></i> Hapus
                    </button>
                </form>
            </li>
        @endif
    </ul>
</div>