@php
    $teknisiShift = $shiftConfig['teknisi'] ?? null;
    $washShift = $shiftConfig['wash'] ?? null;
@endphp

@if($teknisiShift)
    <div>
        <strong>Teknisi:</strong>
        S1 {{ $teknisiShift['shift_1_start'] }}-{{ $teknisiShift['shift_1_end'] }},
        S2 {{ $teknisiShift['shift_2_start'] }}-{{ $teknisiShift['shift_2_end'] }},
        Longshift {{ $teknisiShift['longshift_start'] ?? '08:00' }}-{{ $teknisiShift['longshift_end'] ?? '20:00' }}
    </div>
@endif

@if($washShift)
    <div>
        <strong>Operator Wash:</strong>
        S1 {{ $washShift['shift_1_start'] }}-{{ $washShift['shift_1_end'] }},
        S2 {{ $washShift['shift_2_start'] }}-{{ $washShift['shift_2_end'] }},
        Longshift {{ $washShift['longshift_start'] ?? '08:00' }}-{{ $washShift['longshift_end'] ?? '20:00' }}
    </div>
@endif
