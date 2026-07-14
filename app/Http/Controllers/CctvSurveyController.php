<?php

namespace App\Http\Controllers;

use App\Models\CctvBooking;
use App\Models\CctvSurvey;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CctvSurveyController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:cctv.booking'),
        ];
    }

    public function create(CctvBooking $booking)
    {
        return view('cctv.surveys.create', compact('booking'));
    }

    public function store(Request $request, CctvBooking $booking)
    {
        $validated = $request->validate([
            'survey_date' => ['nullable', 'date'],
            'location' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['nullable', 'in:pending,completed'],
        ]);

        $survey = CctvSurvey::create([
            'cctv_booking_id' => $booking->id,
            'survey_date' => $validated['survey_date'] ?? null,
            'surveyor_id' => auth()->id(),
            'location' => $validated['location'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'] ?? 'pending',
        ]);

        $this->auditLogService->logAction('cctv.survey.created', $survey, [], $survey->toArray());

        return redirect()->route('cctv.bookings.show', $booking)->with('success', 'Survey berhasil dibuat.');
    }

    public function edit(CctvSurvey $survey)
    {
        $survey->loadMissing('booking');
        return view('cctv.surveys.edit', compact('survey'));
    }

    public function update(Request $request, CctvSurvey $survey)
    {
        $validated = $request->validate([
            'survey_date' => ['nullable', 'date'],
            'location' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'in:pending,completed'],
        ]);

        $old = $survey->toArray();
        $survey->update([
            'survey_date' => $validated['survey_date'] ?? null,
            'location' => $validated['location'],
            'notes' => $validated['notes'] ?? null,
            'status' => $validated['status'],
        ]);
        $this->auditLogService->logAction('cctv.survey.updated', $survey, $old, $survey->toArray());

        return redirect()->route('cctv.bookings.show', $survey->booking)->with('success', 'Survey berhasil diperbarui.');
    }
}

