export type EnvironmentMode = 'demo' | 'staging' | 'production';

/**
 * Shared on every Inertia page by HandleInertiaRequests. Display only:
 * the server is the enforcement layer for the real-money launch gate.
 */
export type LuxBetSharedProps = {
    /** Session reminder interval in minutes; null when switched off. */
    reminderMinutes: number | null;
    environmentMode: EnvironmentMode;
    realMoneyEnabled: boolean;
    demoNotice: {
        title: string;
        body: string;
    };
};

export type GameCategory =
    | 'slots'
    | 'live_casino'
    | 'blackjack'
    | 'baccarat'
    | 'poker'
    | 'crash';

export type CatalogueCategory = {
    id: GameCategory;
    name: string;
    /** True only when at least one game in it can be launched in demo mode. */
    available: boolean;
    playable_games: number;
};

export type CatalogueGameStatus =
    | 'playable'
    | 'demo_not_offered'
    | 'coming_soon';

export type CatalogueGame = {
    slug: string;
    name: string;
    category: GameCategory;
    category_label: string;
    description: string | null;
    tile_title: string;
    /** CSS background for the tile artwork. */
    background: string;
    border: string;
    status: CatalogueGameStatus;
    playable: boolean;
    demo_only: true;
    provider: { name: string; is_simulator: boolean };
};
