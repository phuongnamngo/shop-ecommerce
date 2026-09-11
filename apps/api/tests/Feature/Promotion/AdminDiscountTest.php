<?php

use App\Models\Coupon;
use App\Models\Discount;
use App\Models\DiscountRule;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('allows staff to list discounts', function () {
    Discount::factory()->create(['name' => 'Staff Visible']);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/promotions/discounts')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
});

it('forbids staff from creating a discount', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->postJson('/api/v1/admin/promotions/discounts', [
            'name' => 'Nope',
            'type' => 'percentage',
            'value' => 10,
        ])
        ->assertForbidden();
});

it('creates a percentage discount with a min_subtotal rule', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/discounts', [
            'name' => 'Summer 10%',
            'type' => 'percentage',
            'value' => 10,
            'status' => 'active',
            'rule' => ['conditions' => ['min_subtotal' => 500000]],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Summer 10%')
        ->assertJsonPath('data.type', 'percentage')
        ->assertJsonPath('data.value', '10.00')
        ->assertJsonPath('data.status', 'active')
        ->assertJsonPath('data.rule.conditions.min_subtotal', 500000)
        ->assertJsonStructure(['data' => ['id', 'code', 'name', 'type', 'value', 'starts_at', 'ends_at', 'status', 'rule', 'created_at', 'updated_at']]);

    $this->assertDatabaseCount('discount_rules', 1);
});

it('rejects percentage value above 100', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/discounts', [
            'name' => 'Bad',
            'type' => 'percentage',
            'value' => 101,
        ])
        ->assertUnprocessable();
});

it('rejects unsupported rule condition keys', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/discounts', [
            'name' => 'Bad rule',
            'type' => 'fixed',
            'value' => 10000,
            'rule' => ['conditions' => ['category_id' => 1]],
        ])
        ->assertUnprocessable();
});

it('returns PROMOTION_NOT_FOUND for missing discount', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/promotions/discounts/999999')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::PROMOTION_NOT_FOUND]);
});

it('blocks deleting a discount that still has coupons', function () {
    $discount = Discount::factory()->create();
    Coupon::factory()->create(['discount_id' => $discount->id]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/promotions/discounts/'.$discount->id)
        ->assertUnprocessable()
        ->assertJsonFragment(['code' => ErrorCode::PROMOTION_DISCOUNT_IN_USE]);
});

it('soft deletes a discount without coupons and clears its rule', function () {
    $discount = Discount::factory()->create();
    DiscountRule::query()->create([
        'discount_id' => $discount->id,
        'conditions' => ['min_subtotal' => 1000],
    ]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/promotions/discounts/'.$discount->id)
        ->assertOk();

    $this->assertSoftDeleted('discounts', ['id' => $discount->id]);
});

it('updates discount and clears rule when rule is null', function () {
    $discount = Discount::factory()->create(['name' => 'Old']);
    DiscountRule::query()->create([
        'discount_id' => $discount->id,
        'conditions' => ['min_subtotal' => 1000],
    ]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/promotions/discounts/'.$discount->id, [
            'name' => 'New',
            'rule' => null,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New')
        ->assertJsonPath('data.rule', null);

    $this->assertDatabaseMissing('discount_rules', ['discount_id' => $discount->id]);
});

it('filters discounts by status and q', function () {
    Discount::factory()->create(['name' => 'Alpha Sale', 'status' => 'active']);
    Discount::factory()->create(['name' => 'Beta Sale', 'status' => 'inactive']);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/promotions/discounts?status=active&q=Alpha')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.name', 'Alpha Sale');
});

it('clamps discount per_page to 100', function () {
    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/promotions/discounts?per_page=200')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 100);
});

it('auto generates ulid code when omitted', function () {
    $response = $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/discounts', [
            'name' => 'Auto code',
            'type' => 'fixed',
            'value' => 5000,
        ])
        ->assertCreated();

    expect(Str::isUlid($response->json('data.code')))->toBeTrue();
});
