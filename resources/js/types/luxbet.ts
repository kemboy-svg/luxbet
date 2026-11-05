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
