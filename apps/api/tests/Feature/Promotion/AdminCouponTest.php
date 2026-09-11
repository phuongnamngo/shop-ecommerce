<?php

use App\Models\Coupon;
use App\Models\Discount;
use App\Support\ErrorCode;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('allows staff to list coupons', function () {
    $discount = Discount::factory()->create();
    Coupon::factory()->create(['discount_id' => $discount->id, 'code' => 'LISTME']);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/promotions/coupons')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page']]);
});

it('forbids staff from creating a coupon', function () {
    $discount = Discount::factory()->create();

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->postJson('/api/v1/admin/promotions/coupons', [
            'code' => 'STAFF',
            'discount_id' => $discount->id,
        ])
        ->assertForbidden();
});

it('creates a coupon linked to a discount', function () {
    $discount = Discount::factory()->create();

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/coupons', [
            'code' => 'SAVE10',
            'discount_id' => $discount->id,
            'max_uses' => 100,
            'max_uses_per_customer' => 1,
            'status' => 'active',
        ])
        ->assertCreated()
        ->assertJsonPath('data.code', 'SAVE10')
        ->assertJsonPath('data.discount_id', $discount->id)
        ->assertJsonPath('data.used_count', 0)
        ->assertJsonPath('data.discount.id', $discount->id)
        ->assertJsonStructure(['data' => [
            'id', 'code', 'discount_id', 'discount', 'max_uses', 'max_uses_per_customer',
            'used_count', 'starts_at', 'ends_at', 'status', 'created_at', 'updated_at',
        ]]);
});

it('rejects duplicate coupon codes', function () {
    $discount = Discount::factory()->create();
    Coupon::factory()->create(['code' => 'DUP', 'discount_id' => $discount->id]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/coupons', [
            'code' => 'DUP',
            'discount_id' => $discount->id,
        ])
        ->assertUnprocessable();
});

it('ignores client used_count on create and update', function () {
    $discount = Discount::factory()->create();

    $created = $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/coupons', [
            'code' => 'READONLY',
            'discount_id' => $discount->id,
            'used_count' => 99,
        ])
        ->assertCreated()
        ->assertJsonPath('data.used_count', 0);

    $id = $created->json('data.id');
    Coupon::query()->whereKey($id)->update(['used_count' => 3]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->patchJson('/api/v1/admin/promotions/coupons/'.$id, [
            'used_count' => 0,
            'max_uses' => 50,
        ])
        ->assertOk()
        ->assertJsonPath('data.used_count', 3)
        ->assertJsonPath('data.max_uses', 50);
});

it('returns PROMOTION_NOT_FOUND for missing coupon', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->getJson('/api/v1/admin/promotions/coupons/999999')
        ->assertNotFound()
        ->assertJsonFragment(['code' => ErrorCode::PROMOTION_NOT_FOUND]);
});

it('soft deletes a coupon even with redemptions history unused', function () {
    $discount = Discount::factory()->create();
    $coupon = Coupon::factory()->create(['discount_id' => $discount->id, 'used_count' => 2]);

    $this->actingAs(catalogAdmin(), 'admin')
        ->deleteJson('/api/v1/admin/promotions/coupons/'.$coupon->id)
        ->assertOk();

    $this->assertSoftDeleted('coupons', ['id' => $coupon->id]);
});

it('filters coupons by discount_id and q', function () {
    $a = Discount::factory()->create();
    $b = Discount::factory()->create();
    Coupon::factory()->create(['discount_id' => $a->id, 'code' => 'AAA']);
    Coupon::factory()->create(['discount_id' => $b->id, 'code' => 'BBB']);

    $this->actingAs(catalogAdmin('staff'), 'admin')
        ->getJson('/api/v1/admin/promotions/coupons?discount_id='.$a->id.'&q=AA')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.code', 'AAA');
});

it('rejects coupon for missing discount_id', function () {
    $this->actingAs(catalogAdmin(), 'admin')
        ->postJson('/api/v1/admin/promotions/coupons', [
            'code' => 'NODISC',
            'discount_id' => 999999,
        ])
        ->assertUnprocessable();
});
