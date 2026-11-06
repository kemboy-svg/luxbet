<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Gamepad2,
    Heart,
    History,
    LayoutGrid,
    LockKeyhole,
    Server,
    ShieldCheck,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Games',
        href: '/games',
        icon: Gamepad2,
    },
    {
        title: 'Favourites',
        href: '/favourites',
        icon: Heart,
    },
    {
        title: 'Play history',
        href: '/play-history',
        icon: History,
    },
    {
        title: 'Demo wallet',
        href: '/demo-wallet',
        icon: Wallet,
    },
];

const page = usePage();

// Admin links are only rendered for staff. The server enforces access on
// every admin route regardless of what is shown here.
const adminNavItems = computed<NavItem[]>(() => {
    const permissions = page.props.auth.permissions ?? [];

    if (!permissions.includes('admin.access')) {
        return [];
    }

    const items: NavItem[] = [
        { title: 'Admin overview', href: '/admin', icon: ShieldCheck },
    ];

    if (permissions.includes('players.view')) {
        items.push({ title: 'Players', href: '/admin/players', icon: Users });
    }

    if (permissions.includes('games.view')) {
        items.push({ title: 'Games', href: '/admin/games', icon: Gamepad2 });
    }

    if (permissions.includes('providers.view')) {
        items.push({
            title: 'Providers',
            href: '/admin/providers',
            icon: Server,
        });
    }

    if (permissions.includes('launch_gate.view')) {
        items.push({
            title: 'Launch gate',
            href: '/admin/launch-control',
            icon: LockKeyhole,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain
                v-if="adminNavItems.length > 0"
                :items="adminNavItems"
                label="Administration"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
