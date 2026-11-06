<?php

namespace Database\Seeders;

use App\Domain\Games\Models\Game;
use App\Domain\Games\Models\GameProvider;
use Illuminate\Database\Seeder;

/**
 * The local simulator provider and its fictional games. These are for
 * development and demo UI only: they are not certified casino games and are
 * always labelled "Local simulator". Safe to run repeatedly.
 */
class LocalSimulatorCatalogueSeeder extends Seeder
{
    public const PROVIDER_SLUG = 'local-simulator';

    public function run(): void
    {
        $provider = GameProvider::query()->firstOrNew(['slug' => self::PROVIDER_SLUG]);
        $provider->forceFill([
            'name' => 'Local simulator',
            'slug' => self::PROVIDER_SLUG,
            'environment' => 'sandbox',
            'status' => 'sandbox',
            'supports_demo' => true,
            'is_simulator' => true,
        ])->save();

        foreach ($this->games() as $order => $game) {
            $model = Game::query()->firstOrNew(['provider_id' => $provider->id, 'provider_game_code' => $game['code']]);
            $model->forceFill([
                'provider_id' => $provider->id,
                'provider_game_code' => $game['code'],
                'name' => $game['name'],
                'slug' => $game['slug'],
                'category' => $game['category'],
                'description' => $game['description'],
                'supports_demo' => $game['supports_demo'] ?? true,
                'is_enabled' => $game['enabled'] ?? true,
                'sort_order' => ($order + 1) * 10,
                'metadata' => [
                    'tile' => [
                        'title' => $game['tile_title'],
                        'background' => $game['background'],
                        'border' => $game['border'],
                    ],
                    'featured' => $game['featured'] ?? false,
                ],
            ])->save();
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function games(): array
    {
        return [
            [
                'code' => 'sim-candy-crown', 'slug' => 'candy-crown', 'name' => 'Candy Crown', 'tile_title' => "Candy\nCrown",
                'category' => 'slots', 'featured' => true,
                'description' => 'A sweet five-reel test slot for exploring the game flow.',
                'background' => 'radial-gradient(circle at 50% 35%, #FF6FB5 0%, #9B2FD1 55%, #3A0F6B 100%)', 'border' => '#FF9AD5',
            ],
            [
                'code' => 'sim-island-treasure', 'slug' => 'island-treasure', 'name' => 'Island Treasure', 'tile_title' => "Island\nTreasure",
                'category' => 'slots', 'featured' => true,
                'description' => 'Lagoon-blue reels inspired by Mauritian shores.',
                'background' => 'radial-gradient(circle at 50% 35%, #2FD1C4 0%, #136E9B 55%, #0C2550 100%)', 'border' => '#7FE8DE',
            ],
            [
                'code' => 'sim-night-orchard', 'slug' => 'night-orchard', 'name' => 'Night Orchard', 'tile_title' => "Night\nOrchard",
                'category' => 'slots',
                'description' => 'A moonlit fruit slot with classic symbols.',
                'background' => 'radial-gradient(circle at 50% 35%, #6A6FFF 0%, #2B2F55 60%, #14112E 100%)', 'border' => '#9EA2FF',
            ],
            [
                'code' => 'sim-copper-comet', 'slug' => 'copper-comet', 'name' => 'Copper Comet', 'tile_title' => "Copper\nComet",
                'category' => 'slots',
                'description' => 'Warm metallic reels with a shooting-star bonus.',
                'background' => 'radial-gradient(circle at 50% 35%, #E08A3C 0%, #7A3E14 60%, #2E1608 100%)', 'border' => '#F2B36B',
            ],
            [
                'code' => 'sim-harbour-lights', 'slug' => 'harbour-lights', 'name' => 'Harbour Lights', 'tile_title' => "Harbour\nLights",
                'category' => 'slots', 'supports_demo' => false,
                'description' => 'Listed to show how games without demo access appear.',
                'background' => 'radial-gradient(circle at 50% 35%, #3B7FA8 0%, #173444 60%, #0B1A22 100%)', 'border' => '#7FB6D6',
            ],
            [
                'code' => 'sim-royal-21', 'slug' => 'royal-21', 'name' => 'Royal 21', 'tile_title' => "Royal\n21",
                'category' => 'blackjack', 'featured' => true,
                'description' => 'Single-deck blackjack against a simulated dealer.',
                'background' => 'radial-gradient(circle at 50% 35%, #2E8C5A 0%, #12503A 60%, #0A2A1F 100%)', 'border' => '#E5A823',
            ],
            [
                'code' => 'sim-double-deck', 'slug' => 'double-deck-classic', 'name' => 'Double Deck Classic', 'tile_title' => "Double\nDeck",
                'category' => 'blackjack',
                'description' => 'Two-deck blackjack with standard table rules.',
                'background' => 'radial-gradient(circle at 50% 35%, #2C7A4A 0%, #163A26 60%, #0A1F14 100%)', 'border' => '#E5A823',
            ],
            [
                'code' => 'sim-velvet-baccarat', 'slug' => 'velvet-baccarat', 'name' => 'Velvet Baccarat', 'tile_title' => "Velvet\nBaccarat",
                'category' => 'baccarat', 'featured' => true,
                'description' => 'Punto banco on a velvet table.',
                'background' => 'radial-gradient(circle at 50% 35%, #D8364F 0%, #7A1430 60%, #3A0818 100%)', 'border' => '#E5A823',
            ],
            [
                'code' => 'sim-midnight-banco', 'slug' => 'midnight-banco', 'name' => 'Midnight Banco', 'tile_title' => "Midnight\nBanco",
                'category' => 'baccarat',
                'description' => 'Late-night baccarat with a plum and gold table.',
                'background' => 'radial-gradient(circle at 50% 35%, #A23C7A 0%, #3A1A2E 60%, #1C0A16 100%)', 'border' => '#E5A823',
            ],
            [
                'code' => 'sim-dodo-rocket', 'slug' => 'dodo-rocket', 'name' => 'Dodo Rocket', 'tile_title' => "Dodo\nRocket",
                'category' => 'crash', 'supports_demo' => false, 'featured' => true,
                'description' => 'Crash games need separate approval before they can be offered.',
                'background' => 'radial-gradient(circle at 50% 35%, #4F7BFF 0%, #2B2FA8 55%, #140F4A 100%)', 'border' => '#8FA8FF',
            ],
        ];
    }
}
