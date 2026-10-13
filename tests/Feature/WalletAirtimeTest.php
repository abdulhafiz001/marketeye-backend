<?php

namespace Tests\Feature;

use App\Models\AirtimeClaim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletAirtimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_below_200_balance_cannot_claim_airtime(): void
    {
        $user = User::factory()->create([
            'wallet_balance' => 150,
            'verified' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/wallet/claim', [
            'phone' => '08012345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['wallet']);
    }

    public function test_user_can_claim_200_airtime_when_balance_suffices(): void
    {
        $user = User::factory()->create([
            'wallet_balance' => 500,
            'verified' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/wallet/claim', [
            'phone' => '08012345678',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.claim.amount', 200)
            ->assertJsonPath('data.wallet.balance', 300);

        $this->assertDatabaseHas('airtime_claims', [
            'user_id' => $user->id,
            'phone' => '08012345678',
            'amount' => 200,
            'status' => 'pending',
        ]);

        $this->assertEquals(300, $user->fresh()->wallet_balance);
    }

    public function test_user_cannot_exceed_200_monthly_limit(): void
    {
        $user = User::factory()->create([
            'wallet_balance' => 600,
            'verified' => 1,
        ]);

        // Existing paid claim this month for 200
        AirtimeClaim::create([
            'user_id' => $user->id,
            'amount' => 200,
            'phone' => '08012345678',
            'status' => 'paid',
            'claimed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/wallet/claim', [
            'phone' => '08012345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['wallet']);
    }

    public function test_wallet_show_returns_monthly_limit_data(): void
    {
        $user = User::factory()->create([
            'wallet_balance' => 250,
            'verified' => 1,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/wallet');

        $response->assertOk()
            ->assertJsonPath('data.wallet.monthly_limit', 200)
            ->assertJsonPath('data.wallet.claim_amount', 200)
            ->assertJsonPath('data.wallet.monthly_remaining', 200)
            ->assertJsonPath('data.wallet.has_reached_monthly_limit', false)
            ->assertJsonPath('data.wallet.can_claim', true);
    }
}
