import type { ShowcaseCategory, ShowcaseGame } from '@/types/luxbet';

// Static showcase content for public pages. Replaced by the real game
// catalogue in Milestone 4. Game names are original to LuxBet; all games
// run on the local simulator until a provider integration is approved.

export const showcaseCategories: ShowcaseCategory[] = [
    { id: 'slots', name: 'Slots', available: true },
    { id: 'live_casino', name: 'Live Casino', available: false },
    { id: 'blackjack', name: 'Blackjack', available: true },
    { id: 'baccarat', name: 'Baccarat', available: true },
    { id: 'poker', name: 'Poker', available: false },
    { id: 'crash', name: 'Crash Games', available: false },
];

export const showcaseGames: ShowcaseGame[] = [
    {
        title: 'Candy\nCrown',
        subtitle: 'Slots · Simulator',
        category: 'slots',
        available: true,
        background:
            'radial-gradient(circle at 50% 35%, #FF6FB5 0%, #9B2FD1 55%, #3A0F6B 100%)',
        border: '#FF9AD5',
    },
    {
        title: 'Island\nTreasure',
        subtitle: 'Slots · Simulator',
        category: 'slots',
        available: true,
        background:
            'radial-gradient(circle at 50% 35%, #2FD1C4 0%, #136E9B 55%, #0C2550 100%)',
        border: '#7FE8DE',
    },
    {
        title: 'Royal\n21',
        subtitle: 'Blackjack · Simulator',
        category: 'blackjack',
        available: true,
        background:
            'radial-gradient(circle at 50% 35%, #2E8C5A 0%, #12503A 60%, #0A2A1F 100%)',
        border: '#E5A823',
    },
    {
        title: 'Velvet\nBaccarat',
        subtitle: 'Baccarat · Simulator',
        category: 'baccarat',
        available: true,
        background:
            'radial-gradient(circle at 50% 35%, #D8364F 0%, #7A1430 60%, #3A0818 100%)',
        border: '#E5A823',
    },
    {
        title: 'Dodo\nRocket',
        subtitle: 'Crash · Pending approval',
        category: 'crash',
        available: false,
        background:
            'radial-gradient(circle at 50% 35%, #4F7BFF 0%, #2B2FA8 55%, #140F4A 100%)',
        border: '#8FA8FF',
    },
];
