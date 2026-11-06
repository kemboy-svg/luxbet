<?php

namespace Tests\Feature\Games;

use Database\Seeders\LocalSimulatorCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GameApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LocalSimulatorCatalogueSeeder::class);
    }

    public function test_games_are_listed_with_pagination_in_the_standard_envelope(): void
    {
        $this->getJson('/api/v1/games?per_page=4')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.pagination.total', 10)
            ->assertJsonPath('meta.pagination.last_page', 3)
            ->assertJsonStructure(['data' => [['slug', 'name', 'category', 'status', 'playable', 'demo_only']], 'meta' => ['request_id']]);
    }

    public function test_games_can_be_filtered_by_category_and_search(): void
    {
        $this->getJson('/api/v1/games?category=baccarat')
            ->assertJsonPath('meta.pagination.total', 2);

        $this->getJson('/api/v1/games?search=orchard')
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.slug', 'night-orchard');
    }

    public function test_invalid_filters_return_a_validation_error(): void
    {
        $this->getJson('/api/v1/games?category=roulette&per_page=500')
            ->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR')
            ->assertJsonStructure(['error' => ['details' => ['category', 'per_page']]]);
    }

    public function test_categories_report_availability(): void
    {
        $this->getJson('/api/v1/games/categories')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('data.0.id', 'slots')
            ->assertJsonPath('data.0.available', true)
            ->assertJsonPath('data.1.id', 'live_casino')
            ->assertJsonPath('data.1.available', false);
    }

    public function test_a_single_game_can_be_fetched(): void
    {
        $this->getJson('/api/v1/games/royal-21')
            ->assertOk()
            ->assertJsonPath('data.name', 'Royal 21')
            ->assertJsonPath('data.status', 'playable');
    }

    public function test_unknown_and_disabled_games_are_not_found(): void
    {
        DB::table('games')->where('slug', 'royal-21')->update(['is_enabled' => false]);

        $this->getJson('/api/v1/games/royal-21')->assertNotFound()->assertJsonPath('error.code', 'NOT_FOUND');
        $this->getJson('/api/v1/games/does-not-exist')->assertNotFound();
    }

    public function test_responses_never_contain_provider_secrets_or_internal_codes(): void
    {
        DB::table('game_providers')->update([
            'credentials_secret_reference' => 'vault://luxbet/simulator/credentials',
            'webhook_signing_key_reference' => 'vault://luxbet/simulator/webhook',
            'base_url' => 'https://sandbox.simulator.example.test',
        ]);

        foreach (['/api/v1/games', '/api/v1/games/categories', '/api/v1/games/candy-crown'] as $uri) {
            $body = (string) $this->getJson($uri)->getContent();

            $this->assertStringNotContainsString('vault://', $body);
            $this->assertStringNotContainsString('example.test', $body);
            $this->assertStringNotContainsString('sim-candy-crown', $body);
            $this->assertStringNotContainsString('provider_id', $body);
        }
    }
}
