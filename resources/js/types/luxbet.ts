export type EnvironmentMode = 'demo' | 'staging' | 'production';

/**
 * Shared on every Inertia page by HandleInertiaRequests. Display only:
 * the server is the enforcement layer for the real-money launch gate.
 */
export type LuxBetSharedProps = {
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

export type ShowcaseCategory = {
    id: GameCategory;
    name: string;
    /** False until a provider integration for the category is tested. */
    available: boolean;
};

export type ShowcaseGame = {
    title: string;
    subtitle: string;
    category: GameCategory;
    available: boolean;
    /** CSS background for the tile artwork. */
    background: string;
    border: string;
};
