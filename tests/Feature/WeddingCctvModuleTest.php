<?php

namespace Tests\Feature;

use App\Models\CctvBooking;
use App\Models\CctvPackage;
use App\Models\CctvPayment;
use App\Models\PaymentTransaction;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WeddingBooking;
use App\Models\WeddingPackage;
use App\Models\WeddingPayment;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeddingCctvModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_wedding_payment_callback_marks_paid_and_creates_finance_transaction(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $package = WeddingPackage::create([
            'name' => 'Wedding Bronze',
            'price' => 10000000,
            'is_active' => true,
        ]);

        $booking = WeddingBooking::create([
            'customer_name' => 'Budi',
            'customer_whatsapp' => '6281234567890',
            'event_date' => now()->addDays(30)->toDateString(),
            'location' => 'Bandung',
            'wedding_package_id' => $package->id,
            'status' => 'pending',
        ]);

        $payment = WeddingPayment::create([
            'wedding_booking_id' => $booking->id,
            'type' => 'dp',
            'amount' => 3000000,
            'status' => 'pending',
        ]);

        $transaction = PaymentTransaction::create([
            'reference_id' => 'PAY-TEST-1',
            'paymentable_type' => WeddingPayment::class,
            'paymentable_id' => $payment->id,
            'customer_name' => 'Budi',
            'phone_number' => '6281234567890',
            'email' => null,
            'amount' => 3000000,
            'payment_type' => 'QRIS',
            'payment_gateway' => 'duitku',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        
        // Mock PaymentManager and DuitkuGateway
        $mockGateway = $this->mock(\App\Services\Payment\DuitkuGateway::class);
        $mockGateway->shouldReceive('handleNotification')
            ->andReturn([
                'merchantOrderId' => $transaction->reference_id,
                'resultCode' => '00',
                'merchantCode' => config('services.duitku.merchant_code'),
            ]);
            
        $this->mock(\App\Services\Payment\PaymentManager::class, function ($mock) use ($mockGateway) {
            $mock->shouldReceive('gateway')->with('duitku')->andReturn($mockGateway);
        });

        app(PaymentService::class)->processCallback([
            'merchantOrderId' => $transaction->reference_id,
            'statusCode' => '00',
            'reference' => 'GW-1',
        ]);

        $payment->refresh();
        $booking->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('dp', $booking->status);

        $this->assertDatabaseHas('transactions', [
            'reference_number' => 'WEDPAY-'.$transaction->id,
            'type' => 'income',
        ]);
    }

    public function test_cctv_payment_callback_marks_paid_and_creates_finance_transaction(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $package = CctvPackage::create([
            'name' => '4 Camera',
            'camera_count' => 4,
            'price' => 5000000,
            'warranty_months' => 12,
            'is_active' => true,
        ]);

        $booking = CctvBooking::create([
            'customer_name' => 'Sari',
            'customer_whatsapp' => '6281111111111',
            'address' => 'Jakarta',
            'cctv_package_id' => $package->id,
            'status' => 'pending',
        ]);

        $payment = CctvPayment::create([
            'cctv_booking_id' => $booking->id,
            'type' => 'dp',
            'amount' => 1000000,
            'status' => 'pending',
        ]);

        $transaction = PaymentTransaction::create([
            'reference_id' => 'PAY-TEST-2',
            'paymentable_type' => CctvPayment::class,
            'paymentable_id' => $payment->id,
            'customer_name' => 'Sari',
            'phone_number' => '6281111111111',
            'email' => null,
            'amount' => 1000000,
            'payment_type' => 'QRIS',
            'payment_gateway' => 'duitku',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);

        // Mock PaymentManager and DuitkuGateway
        $mockGateway = $this->mock(\App\Services\Payment\DuitkuGateway::class);
        $mockGateway->shouldReceive('handleNotification')
            ->andReturn([
                'merchantOrderId' => $transaction->reference_id,
                'resultCode' => '00',
                'merchantCode' => config('services.duitku.merchant_code'),
            ]);
            
        $this->mock(\App\Services\Payment\PaymentManager::class, function ($mock) use ($mockGateway) {
            $mock->shouldReceive('gateway')->with('duitku')->andReturn($mockGateway);
        });

        app(PaymentService::class)->processCallback([
            'merchantOrderId' => $transaction->reference_id,
            'statusCode' => '00',
            'reference' => 'GW-2',
        ]);

        $payment->refresh();
        $booking->refresh();

        $this->assertSame('paid', $payment->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertSame('dp', $booking->status);

        $this->assertDatabaseHas('transactions', [
            'reference_number' => 'CCTVPAY-'.$transaction->id,
            'type' => 'income',
        ]);
    }

    public function test_reporting_center_has_wedding_and_cctv_routes(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['label' => 'Administrator']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);

        $this->actingAs($admin)
            ->get(route('reports.wedding'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('reports.cctv'))
            ->assertOk();
    }
}

