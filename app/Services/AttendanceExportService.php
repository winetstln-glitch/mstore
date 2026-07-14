<?php

namespace App\Services;

use App\Models\TechnicianAttendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class AttendanceExportService
{
    public function exportDailyToXlsx($users, $attendancesByDate, $dates, $startDate, $endDate): \Closure
    {
        return function () use ($users, $attendancesByDate, $dates, $startDate, $endDate) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues(['RINCIAN KEHADIRAN KARYAWAN']));
            $writer->addRow(Row::fromValues(['Periode: ' . Carbon::parse($startDate)->translatedFormat('d F Y') . ' - ' . Carbon::parse($endDate)->translatedFormat('d F Y')]));
            $writer->addRow(Row::fromValues(['Tanggal Cetak: ' . now()->translatedFormat('d F Y H:i')]));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues([
                'Nama Karyawan',
                'Tanggal',
                'Jam Masuk',
                'Jam Pulang',
                'Status',
                'Catatan',
            ]));

            foreach ($dates as $dateStr) {
                foreach ($users as $user) {
                    $isOff = app(AttendanceService::class)->isUserOffOnDate($user, $dateStr);
                    $attendances = $attendancesByDate->get($dateStr, collect());
                    $attendance = $attendances->get($user->id);

                    if ($isOff) {
                        $status = __('OFF');
                        $clockIn = '-';
                        $clockOut = '-';
                        $notes = '-';
                    } elseif ($attendance) {
                        $status = __(ucfirst($attendance->status));
                        $clockIn = $attendance->clock_in?->format('H:i') ?? '-';
                        $clockOut = $attendance->clock_out?->format('H:i') ?? '-';
                        $notes = $attendance->notes ?? '-';
                    } else {
                        $status = __('Belum Absen');
                        $clockIn = '-';
                        $clockOut = '-';
                        $notes = '-';
                    }

                    $writer->addRow(Row::fromValues([
                        $user->name,
                        Carbon::parse($dateStr)->translatedFormat('d F Y'),
                        $clockIn,
                        $clockOut,
                        $status,
                        $notes,
                    ]));
                }
            }

            $writer->close();
        };
    }

    public function exportDetailsToXlsx($attendances): \Closure
    {
        return function () use ($attendances) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(Row::fromValues(['RINCIAN KEHADIRAN KARYAWAN']));
            $writer->addRow(Row::fromValues(['Tanggal Cetak: ' . now()->translatedFormat('d F Y H:i')]));
            $writer->addRow(Row::fromValues([]));
            $writer->addRow(Row::fromValues([
                'Nama Karyawan',
                'Tanggal',
                'Jam Masuk',
                'Jam Pulang',
                'Status',
                'Catatan',
            ]));

            foreach ($attendances as $attendance) {
                $writer->addRow(Row::fromValues([
                    $attendance->user?->name ?? '-',
                    ($attendance->work_date ?? $attendance->clock_in)?->translatedFormat('d F Y') ?? '-',
                    $attendance->clock_in?->format('H:i') ?? '-',
                    $attendance->clock_out?->format('H:i') ?? '-',
                    __(ucfirst($attendance->status)),
                    $attendance->notes ?? '-',
                ]));
            }

            $writer->close();
        };
    }

    public function exportSummaryToXlsx($summary): \Closure
    {
        return function () use ($summary) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['REKAP GAJI TEKNISI']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Nama Teknisi',
                'Total Hadir',
                'Total Terlambat',
                'Total Cuti',
                'Total Izin',
                'Total Sakit',
                'Total Alpha',
                'Total Hari Dibayar',
                'Gaji Harian',
                'Total Bonus',
                'Total Kasbon',
                'Total Potongan',
                'Total Gaji',
            ]));

            foreach ($summary as $data) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $data['user']->name,
                    $data['present_count'],
                    $data['late_count'],
                    $data['leave_count'],
                    $data['permit_count'],
                    $data['sick_count'],
                    $data['alpha_count'],
                    $data['paid_days'],
                    $data['daily_salary'],
                    $data['total_bonus'],
                    $data['total_kasbon'],
                    $data['total_deductions'],
                    $data['total_salary'],
                ]));
            }

            $writer->addNewSheetAndMakeItCurrent();
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['DETAIL ABSENSI']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                'Nama Teknisi',
                'Tanggal',
                'Jam Masuk',
                'Jam Pulang',
                'Status',
                'Catatan',
            ]));

            foreach ($summary as $data) {
                foreach ($data['dates'] as $attendance) {
                    $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                        $data['user']->name,
                        ($attendance->work_date ?? $attendance->clock_in)?->translatedFormat('d F Y') ?? '-',
                        $attendance->clock_in?->format('H:i') ?? '-',
                        $attendance->clock_out?->format('H:i') ?? '-',
                        __(ucfirst($attendance->status)),
                        $attendance->notes ?? '-',
                    ]));
                }
            }

            $writer->close();
        };
    }
}
