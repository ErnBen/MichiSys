<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\ReceivablePayment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalePaymentsAndReceivablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_screen_renders_touch_controls_and_payment_selection(): void
    {
        [$user] = $this->makeSellable();

        $this->actingAs($user)->get(route('sales.create'))
            ->assertOk()
            ->assertSee('Agregar al carrito')
            ->assertSee('data-step="1"', false)
            ->assertSee('Método de pago')
            ->assertSee('Dinero recibido (Gs.)')
            ->assertSee('Confirmar venta');
    }

    public function test_cash_received_must_cover_the_total_before_stock_is_decremented(): void
    {
        [$user, $product] = $this->makeSellable();

        $this->actingAs($user)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'payment_method' => 'cash',
            'cash_received' => 3999,
        ])->assertSessionHasErrors('cash_received');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_explicit_cash_payment_requires_the_received_amount(): void
    {
        [$user, $product] = $this->makeSellable();

        $this->actingAs($user)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('cash_received');

        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_piece_identifier_keeps_its_conversion_and_displays_as_units(): void
    {
        [$user, $product] = $this->makeSellable();

        $this->assertSame('piece', $product->unit);
        $this->assertSame('Unidades', $product->unit_label);
        $this->assertSame(['piece' => 1], $product->purchaseUnitFactors());

        $this->actingAs($user)->get(route('products.create'))
            ->assertOk()
            ->assertSee('Unidades (unid.)');
    }

    public function test_cash_sale_charges_final_price_without_tax_and_calculates_change(): void
    {
        [$user, $product] = $this->makeSellable();

        $this->actingAs($user)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'payment_method' => 'cash',
            'cash_received' => 5000,
        ])->assertRedirect(route('sales.index'));

        $sale = Sale::firstOrFail();
        $this->assertSame('4000.00', $sale->total);
        $this->assertSame('0.00', $sale->tax);
        $this->assertSame('5000.00', $sale->cash_received);
        $this->assertSame('1000.00', $sale->change_due);
        $this->assertSame(9, $product->fresh()->stock);
    }

    public function test_payment_reference_and_duplicate_sale_submission_are_recorded_once(): void
    {
        [$user, $product] = $this->makeSellable();
        $key = (string) Str::uuid();
        $saleData = [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'TRX-7788',
            'payment_entity' => 'Banco de prueba',
            'payment_paid_at' => '2026-11-10T09:30',
            'payment_notes' => 'Transferencia verificada',
            'idempotency_key' => $key,
        ];

        $this->actingAs($user)->post(route('sales.store'), $saleData)->assertRedirect(route('sales.index'));
        $this->post(route('sales.store'), $saleData)->assertRedirect(route('sales.index'));

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseHas('sales', [
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'TRX-7788',
            'payment_entity' => 'Banco de prueba',
            'total' => 4000,
        ]);
        $this->assertSame(9, $product->fresh()->stock);
        $sale = Sale::firstOrFail();
        $this->get(route('sales.receipt', $sale))
            ->assertOk()
            ->assertSeeText('Transferencia bancaria')
            ->assertSeeText('TRX-7788');
    }

    public function test_credit_sale_requires_a_client_and_creates_a_scoped_receivable(): void
    {
        [$user, $product] = $this->makeSellable();
        $client = Client::create(['name' => 'Cliente a crédito']);
        $payload = [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'payment_method' => 'credit',
            'due_date' => today()->addDays(10)->toDateString(),
            'receivable_observations' => 'Paga a fin de mes',
        ];

        $this->actingAs($user)->post(route('sales.store'), $payload)
            ->assertSessionHasErrors('client_id');
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame(10, $product->fresh()->stock);

        $this->post(route('sales.store'), $payload + ['client_id' => $client->id])
            ->assertRedirect(route('sales.index'));

        $receivable = Receivable::firstOrFail();
        $this->assertSame('4000.00', $receivable->original_amount);
        $this->assertSame('0.00', $receivable->paid_amount);
        $this->assertSame('4000.00', $receivable->balance);
        $this->assertSame('pending', $receivable->status);
        $this->assertSame('Paga a fin de mes', $receivable->observations);
        $this->get(route('receivables.index'))
            ->assertOk()
            ->assertSeeText('Saldo pendiente del negocio')
            ->assertSeeText('Cliente a crédito');
        $this->get(route('receivables.show', $receivable))
            ->assertOk()
            ->assertSeeText('Historial de cuentas del cliente')
            ->assertSeeText('Registrar pago');
        $this->delete(route('clients.destroy', $client))->assertSessionHasErrors('client');
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_due_date_sets_overdue_status_and_status_filter_finds_it(): void
    {
        [$user, $product] = $this->makeSellable();
        $client = Client::create(['name' => 'Cliente vencido']);
        $this->actingAs($user)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'client_id' => $client->id,
            'payment_method' => 'credit',
            'due_date' => today()->subDay()->toDateString(),
        ]);
        $receivable = Receivable::firstOrFail();

        $this->assertSame('overdue', $receivable->current_status);
        $this->get(route('receivables.index', ['status' => 'overdue']))
            ->assertOk()
            ->assertSeeText('Cliente vencido')
            ->assertSeeText('Vencida');
    }

    public function test_partial_and_full_payments_update_balance_and_keep_payment_history(): void
    {
        [$owner, $product] = $this->makeSellable();
        $client = Client::create(['name' => 'Cliente de pagos']);
        $this->actingAs($owner)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'client_id' => $client->id,
            'payment_method' => 'credit',
        ])->assertRedirect();
        $receivable = Receivable::firstOrFail();

        $this->post(route('receivables.payments.store', $receivable), [
            'amount' => 1500,
            'payment_method' => 'qr',
            'reference' => 'QR-001',
            'entity' => 'Billetera',
            'paid_at' => now()->format('Y-m-d\TH:i'),
            'idempotency_key' => (string) Str::uuid(),
        ])->assertRedirect(route('receivables.show', $receivable));

        $this->assertSame('1500.00', $receivable->fresh()->paid_amount);
        $this->assertSame('2500.00', $receivable->fresh()->balance);
        $this->assertSame('partially_paid', $receivable->fresh()->status);
        $this->assertDatabaseHas('receivable_payments', ['reference' => 'QR-001', 'amount' => 1500]);

        $this->post(route('receivables.payments.store', $receivable), [
            'amount' => 2500,
            'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d\TH:i'),
        ])->assertRedirect(route('receivables.show', $receivable));

        $this->assertSame('4000.00', $receivable->fresh()->paid_amount);
        $this->assertSame('0.00', $receivable->fresh()->balance);
        $this->assertSame('paid', $receivable->fresh()->status);
        $this->assertDatabaseCount('receivable_payments', 2);
        $this->get(route('receivables.show', $receivable))
            ->assertOk()
            ->assertSeeText('QR-001')
            ->assertSeeText('Historial de cuentas del cliente');
    }

    public function test_payment_cannot_exceed_balance_and_duplicate_submission_is_idempotent(): void
    {
        [$owner, $product] = $this->makeSellable();
        $client = Client::create(['name' => 'Cliente saldo']);
        $this->actingAs($owner)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'client_id' => $client->id,
            'payment_method' => 'credit',
        ]);
        $receivable = Receivable::firstOrFail();
        $key = (string) Str::uuid();
        $payment = [
            'amount' => 1000,
            'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d\TH:i'),
            'idempotency_key' => $key,
        ];

        $this->post(route('receivables.payments.store', $receivable), $payment);
        $this->post(route('receivables.payments.store', $receivable), $payment);
        $this->assertSame('1000.00', $receivable->fresh()->paid_amount);
        $this->assertDatabaseCount('receivable_payments', 1);

        $this->post(route('receivables.payments.store', $receivable), [
            ...$payment,
            'amount' => 4000,
            'idempotency_key' => (string) Str::uuid(),
        ])->assertSessionHasErrors('amount');
        $this->assertSame('3000.00', $receivable->fresh()->balance);
    }

    public function test_only_business_admin_can_annul_a_payment_and_the_audit_record_is_retained(): void
    {
        [$owner, $product] = $this->makeSellable();
        $client = Client::create(['name' => 'Cliente anulación']);
        $this->actingAs($owner)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'client_id' => $client->id,
            'payment_method' => 'credit',
        ]);
        $receivable = Receivable::firstOrFail();
        $this->post(route('receivables.payments.store', $receivable), [
            'amount' => 1000,
            'payment_method' => 'cash',
            'paid_at' => now()->format('Y-m-d\TH:i'),
        ]);
        $payment = ReceivablePayment::firstOrFail();
        $cashier = User::factory()->create(['role' => 'cajero', 'business_id' => $owner->business_id]);

        $this->actingAs($cashier)->delete(route('receivables.payments.reverse', [$receivable, $payment]), [
            'reversal_reason' => 'Cajero no autorizado',
        ])->assertForbidden();

        $this->get(route('receivables.show', $receivable))->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin', 'business_id' => $owner->business_id]);
        $this->actingAs($admin)->delete(route('receivables.payments.reverse', [$receivable, $payment]), [
            'reversal_reason' => 'Corrección de registro',
        ])->assertRedirect(route('receivables.show', $receivable));

        $payment->refresh();
        $this->assertNotNull($payment->reversed_at);
        $this->assertSame('Corrección de registro', $payment->reversal_reason);
        $this->assertSame('0.00', $receivable->fresh()->paid_amount);
        $this->assertSame('4000.00', $receivable->fresh()->balance);
        $this->assertDatabaseCount('receivable_payments', 1);
    }

    public function test_receivables_are_not_accessible_across_businesses(): void
    {
        [$owner, $product] = $this->makeSellable();
        $client = Client::create(['name' => 'Cliente privado']);
        $this->actingAs($owner)->post(route('sales.store'), [
            'product_ids' => [$product->id],
            'product_quantities' => [$product->id => 1],
            'client_id' => $client->id,
            'payment_method' => 'credit',
        ]);
        $receivable = Receivable::firstOrFail();

        $otherBusiness = Business::create(['name' => 'Otro negocio', 'owner_name' => 'Otro dueño']);
        $otherUser = User::factory()->create(['business_id' => $otherBusiness->id, 'role' => 'admin']);
        $this->actingAs($otherUser)
            ->get(route('receivables.show', $receivable->id))
            ->assertNotFound();
    }

    private function makeSellable(string $role = 'admin'): array
    {
        $business = Business::create(['name' => 'Negocio de prueba', 'owner_name' => 'Propietario']);
        $user = User::factory()->create(['business_id' => $business->id, 'role' => $role]);
        $this->actingAs($user);
        $category = Category::create(['name' => 'Alimentos', 'slug' => 'alimentos', 'active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Producto de prueba',
            'sku' => 'PRUEBA-01',
            'price' => 4000,
            'cost' => 2000,
            'stock' => 10,
            'stock_minimo' => 1,
            'active' => true,
        ]);

        return [$user, $product];
    }
}