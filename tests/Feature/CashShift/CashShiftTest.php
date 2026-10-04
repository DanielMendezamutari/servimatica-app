<?php

namespace Tests\Feature\CashShift;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashShiftTest extends TestCase
{
    use RefreshDatabase;

    private function ownerHeaders(): array
    {
        $this->seed();
        $token = $this->postJson('/api/auth/login', ['login' => 'admin', 'pin' => '1234'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    private function sellerHeaders(): array
    {
        $headers = $this->ownerHeaders();
        $this->postJson('/api/users', [
            'name' => 'Cajero Test',
            'username' => 'cajero1',
            'email' => 'cajero1@servimatica.com',
            'password' => 'password123',
            'pin' => '8888',
            'role' => 'vendedor',
        ], $headers);

        $token = $this->postJson('/api/auth/login', ['login' => 'cajero1', 'pin' => '8888'])->json('accessToken');
        return ['Authorization' => "Bearer $token"];
    }

    public function test_cash_shift_lifecycle(): void
    {
        $headers = $this->sellerHeaders();

        // 1. Initially no open shift
        $res = $this->getJson('/api/cash-shifts/current', $headers)->assertOk();
        $this->assertFalse($res->json('is_open'));
        $this->assertNull($res->json('data'));

        // 2. Open shift with Bs. 150.00
        $openRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 150.00,
            'notes' => 'Fondo inicial billetes y monedas',
        ], $headers)->assertStatus(201);

        $this->assertEquals('150.00', $openRes->json('data.opening_amount'));
        $this->assertEquals('open', $openRes->json('data.status'));

        // 3. Second open shift attempt must fail
        $duplicateRes = $this->postJson('/api/cash-shifts/open', [
            'opening_amount' => 200.00,
        ], $headers)->assertStatus(422);

        $this->assertStringContainsString('Ya tiene un turno', $duplicateRes->json('message'));

        // 4. Current shift check returns open
        $currentRes = $this->getJson('/api/cash-shifts/current', $headers)->assertOk();
        $this->assertTrue($currentRes->json('is_open'));
        $this->assertEquals('150.00', $currentRes->json('data.opening_amount'));

        // 5. Close shift with physical cash count Bs. 150.00 (expected: 150.00, diff: 0.00)
        $closeRes = $this->postJson('/api/cash-shifts/close', [
            'closing_amount' => 150.00,
            'notes' => 'Cierre de turno sin novedades',
        ], $headers)->assertOk();

        $this->assertEquals('closed', $closeRes->json('data.status'));
        $this->assertEquals('150.00', $closeRes->json('data.closing_amount'));
        $this->assertEquals('150.00', $closeRes->json('data.expected_amount'));
        $this->assertEquals('0.00', $closeRes->json('data.difference'));

        // 6. Current shift should now be closed/null
        $afterClose = $this->getJson('/api/cash-shifts/current', $headers)->assertOk();
        $this->assertFalse($afterClose->json('is_open'));

        // 7. Paginated history
        $listRes = $this->getJson('/api/cash-shifts', $headers)->assertOk();
        $this->assertCount(1, $listRes->json('data'));
    }
}
