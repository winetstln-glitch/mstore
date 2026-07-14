<?php

namespace App\Http\Controllers;

use App\Models\NocMetricSnapshot;
use App\Models\SlaBreach;
use App\Models\Ticket;
use App\Models\WhatsAppAnalyticsEvent;
use App\Models\WeddingBooking;
use App\Models\WeddingPayment;
use App\Models\CctvBooking;
use App\Models\CctvPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReportingCenterController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:report.noc.export', only: ['noc', 'nocPdf', 'nocExcel']),
            new Middleware('permission:report.whatsapp.export', only: ['whatsapp', 'whatsappPdf', 'whatsappExcel']),
            new Middleware('permission:report.sla.export', only: ['sla', 'slaPdf', 'slaExcel']),
            new Middleware('permission:report.wedding.export', only: ['wedding', 'weddingPdf', 'weddingExcel']),
            new Middleware('permission:report.cctv.export', only: ['cctv', 'cctvPdf', 'cctvExcel']),
        ];
    }

    public function noc(Request $request)
    {
        $data = $this->buildNocData($request);
        return view('reports.noc', $data);
    }

    public function nocPdf(Request $request)
    {
        $data = $this->buildNocData($request);
        return Pdf::loadView('reports.pdf.noc', $data)->setPaper('a4', 'portrait')->download('noc_report.pdf');
    }

    public function nocExcel(Request $request)
    {
        $data = $this->buildNocData($request);

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;
            $writer->openToFile('php://output');
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['NOC Report']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Tanggal', $data['date']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Captured At', 'Health', 'ONU Online', 'ONU Offline', 'LOS', 'DyingGasp', 'Weak', 'PPPoE Active', 'Outage Active', 'Ticket Open']));
            foreach ($data['snapshots'] as $s) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $s->captured_at?->format('H:i:s'),
                    $s->network_health_score,
                    $s->onu_online,
                    $s->onu_offline,
                    $s->onu_los,
                    $s->onu_dying_gasp,
                    $s->onu_weak_signal,
                    $s->pppoe_active_sessions,
                    $s->outage_active,
                    $s->ticket_open,
                ]));
            }
            $writer->close();
        }, 'noc_report.xlsx');
    }

    public function whatsapp(Request $request)
    {
        $data = $this->buildWhatsAppData($request);
        return view('reports.whatsapp', $data);
    }

    public function whatsappPdf(Request $request)
    {
        $data = $this->buildWhatsAppData($request);
        return Pdf::loadView('reports.pdf.whatsapp', $data)->setPaper('a4', 'portrait')->download('whatsapp_report.pdf');
    }

    public function whatsappExcel(Request $request)
    {
        $data = $this->buildWhatsAppData($request);

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;
            $writer->openToFile('php://output');
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['WhatsApp Report']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['From', $data['from'], 'To', $data['to']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Incoming', $data['summary']['incoming'], 'Outgoing', $data['summary']['outgoing'], 'AI Usage', $data['summary']['ai_usage']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Top Intent', 'Total']));
            foreach ($data['topIntents'] as $row) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([$row['intent'], $row['total']]));
            }
            $writer->close();
        }, 'whatsapp_report.xlsx');
    }

    public function sla(Request $request)
    {
        $data = $this->buildSlaData($request);
        return view('reports.sla', $data);
    }

    public function slaPdf(Request $request)
    {
        $data = $this->buildSlaData($request);
        return Pdf::loadView('reports.pdf.sla', $data)->setPaper('a4', 'portrait')->download('sla_report.pdf');
    }

    public function slaExcel(Request $request)
    {
        $data = $this->buildSlaData($request);

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;
            $writer->openToFile('php://output');
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['SLA Report']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['From', $data['from'], 'To', $data['to']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Compliance %', $data['summary']['compliance_percent'], 'Breach %', $data['summary']['breach_percent']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Ticket', 'Status', 'SLA Status', 'Created At']));
            foreach ($data['criticalTickets'] as $t) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $t->ticket_number,
                    $t->status,
                    $t->sla_status,
                    $t->created_at?->toDateTimeString(),
                ]));
            }
            $writer->close();
        }, 'sla_report.xlsx');
    }

    public function wedding(Request $request)
    {
        $data = $this->buildWeddingData($request);
        return view('reports.wedding', $data);
    }

    public function weddingPdf(Request $request)
    {
        $data = $this->buildWeddingData($request);
        return Pdf::loadView('reports.pdf.wedding', $data)->setPaper('a4', 'portrait')->download('wedding_report.pdf');
    }

    public function weddingExcel(Request $request)
    {
        $data = $this->buildWeddingData($request);

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;
            $writer->openToFile('php://output');
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Wedding Report']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['From', $data['from'], 'To', $data['to']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Total Booking', $data['summary']['total_booking'], 'Revenue', $data['summary']['revenue'], 'Pending Payment', $data['summary']['pending_payment']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Booking No', 'Customer', 'WhatsApp', 'Event Date', 'Location', 'Package', 'Status']));
            foreach ($data['bookings'] as $b) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $b->booking_number,
                    $b->customer_name,
                    $b->customer_whatsapp,
                    $b->event_date?->toDateString(),
                    $b->location,
                    $b->package?->name,
                    $b->status,
                ]));
            }
            $writer->close();
        }, 'wedding_report.xlsx');
    }

    public function cctv(Request $request)
    {
        $data = $this->buildCctvData($request);
        return view('reports.cctv', $data);
    }

    public function cctvPdf(Request $request)
    {
        $data = $this->buildCctvData($request);
        return Pdf::loadView('reports.pdf.cctv', $data)->setPaper('a4', 'portrait')->download('cctv_report.pdf');
    }

    public function cctvExcel(Request $request)
    {
        $data = $this->buildCctvData($request);

        return response()->streamDownload(function () use ($data) {
            $writer = new \OpenSpout\Writer\XLSX\Writer;
            $writer->openToFile('php://output');
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['CCTV Report']));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['From', $data['from'], 'To', $data['to']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Total Booking', $data['summary']['total_booking'], 'Revenue', $data['summary']['revenue'], 'Pending Payment', $data['summary']['pending_payment']]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([]));
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(['Booking No', 'Customer', 'WhatsApp', 'Address', 'Package', 'Status']));
            foreach ($data['bookings'] as $b) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    $b->booking_number,
                    $b->customer_name,
                    $b->customer_whatsapp,
                    $b->address,
                    $b->package?->name,
                    $b->status,
                ]));
            }
            $writer->close();
        }, 'cctv_report.xlsx');
    }

    private function buildNocData(Request $request): array
    {
        $date = $request->query('date', now()->format('Y-m-d'));
        $from = Carbon::parse($date)->startOfDay();
        $to = Carbon::parse($date)->endOfDay();

        $snapshots = NocMetricSnapshot::query()
            ->whereBetween('captured_at', [$from, $to])
            ->orderBy('captured_at')
            ->get();

        return [
            'date' => $date,
            'snapshots' => $snapshots,
        ];
    }

    private function buildWhatsAppData(Request $request): array
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()->toDateString().' 00:00:00'));
        $to = Carbon::parse($request->query('to', now()->endOfDay()->toDateTimeString()));

        $incoming = WhatsAppAnalyticsEvent::query()->whereBetween('occurred_at', [$from, $to])->where('direction', 'incoming')->count();
        $outgoing = WhatsAppAnalyticsEvent::query()->whereBetween('occurred_at', [$from, $to])->where('direction', 'outgoing')->count();
        $ai = WhatsAppAnalyticsEvent::query()->whereBetween('occurred_at', [$from, $to])->where('used_ai', true)->count();

        $top = WhatsAppAnalyticsEvent::query()
            ->selectRaw('intent, COUNT(*) as total')
            ->whereBetween('occurred_at', [$from, $to])
            ->whereNotNull('intent')
            ->groupBy('intent')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['intent' => $r->intent, 'total' => (int) $r->total])
            ->all();

        return [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'summary' => [
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'ai_usage' => $ai,
            ],
            'topIntents' => $top,
        ];
    }

    private function buildSlaData(Request $request): array
    {
        $from = Carbon::parse($request->query('from', now()->subDays(30)->startOfDay()->toDateTimeString()));
        $to = Carbon::parse($request->query('to', now()->endOfDay()->toDateTimeString()));

        $totalClosed = Ticket::query()->whereBetween('closed_at', [$from, $to])->count();
        $closedWithBreach = Ticket::query()
            ->whereBetween('closed_at', [$from, $to])
            ->whereExists(function ($q) {
                $q->selectRaw(1)->from('sla_breaches')->whereColumn('sla_breaches.ticket_id', 'tickets.id');
            })->count();

        $compliance = $totalClosed > 0 ? (int) round((($totalClosed - $closedWithBreach) / $totalClosed) * 100) : 100;
        $breachPercent = $totalClosed > 0 ? (int) round(($closedWithBreach / $totalClosed) * 100) : 0;

        $criticalTickets = Ticket::query()
            ->whereNotIn('status', ['closed', 'solved'])
            ->whereIn('sla_status', ['critical', 'breached'])
            ->latest('created_at')
            ->limit(200)
            ->get();

        $breaches = SlaBreach::query()->whereBetween('breached_at', [$from, $to])->count();

        return [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'summary' => [
                'compliance_percent' => $compliance,
                'breach_percent' => $breachPercent,
                'breaches' => $breaches,
            ],
            'criticalTickets' => $criticalTickets,
        ];
    }

    private function buildWeddingData(Request $request): array
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()->startOfDay()->toDateTimeString()));
        $to = Carbon::parse($request->query('to', now()->endOfMonth()->endOfDay()->toDateTimeString()));

        $bookings = WeddingBooking::query()
            ->with('package')
            ->whereBetween('event_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('event_date')
            ->limit(1000)
            ->get();

        $revenue = WeddingPayment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $pendingPayment = WeddingPayment::query()->where('status', 'pending')->count();

        $topPackages = WeddingBooking::query()
            ->selectRaw('wedding_package_id, COUNT(*) as total')
            ->whereBetween('event_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('wedding_package_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $name = \App\Models\WeddingPackage::whereKey($row->wedding_package_id)->value('name');
                return ['package' => $name, 'total' => (int) $row->total];
            })
            ->all();

        return [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'summary' => [
                'total_booking' => $bookings->count(),
                'revenue' => (int) $revenue,
                'pending_payment' => (int) $pendingPayment,
            ],
            'topPackages' => $topPackages,
            'bookings' => $bookings,
        ];
    }

    private function buildCctvData(Request $request): array
    {
        $from = Carbon::parse($request->query('from', now()->startOfMonth()->startOfDay()->toDateTimeString()));
        $to = Carbon::parse($request->query('to', now()->endOfMonth()->endOfDay()->toDateTimeString()));

        $bookings = CctvBooking::query()
            ->with('package')
            ->whereBetween('created_at', [$from, $to])
            ->orderBy('created_at')
            ->limit(1000)
            ->get();

        $revenue = CctvPayment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$from, $to])
            ->sum('amount');

        $pendingPayment = CctvPayment::query()->where('status', 'pending')->count();

        $topPackages = CctvBooking::query()
            ->selectRaw('cctv_package_id, COUNT(*) as total')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('cctv_package_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $name = \App\Models\CctvPackage::whereKey($row->cctv_package_id)->value('name');
                return ['package' => $name, 'total' => (int) $row->total];
            })
            ->all();

        return [
            'from' => $from->toDateTimeString(),
            'to' => $to->toDateTimeString(),
            'summary' => [
                'total_booking' => $bookings->count(),
                'revenue' => (int) $revenue,
                'pending_payment' => (int) $pendingPayment,
            ],
            'topPackages' => $topPackages,
            'bookings' => $bookings,
        ];
    }
}
