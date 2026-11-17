<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    FolderGit2,
    LayoutGrid,
    Receipt,
    ShieldCheck,
    Tags,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as budgetsIndex } from '@/routes/budgets';
import { index as categoriesIndex } from '@/routes/categories';
import { index as expensesIndex } from '@/routes/expenses';
import type { NavItem, User } from '@/types';

const page = usePage<{ auth: { user: User } }>();
const isAdmin = computed(() => page.props.auth.user.roles.includes('admin'));

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Inicio',
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(isAdmin.value
        ? [
              {
                  title: 'Admin',
                  href: adminDashboard(),
                  icon: ShieldCheck,
              },
          ]
        : []),
    {
        title: 'Categorías',
        href: categoriesIndex(),
        icon: Tags,
    },
    {
        title: 'Presupuestos',
        href: budgetsIndex(),
        icon: Wallet,
    },
    {
        title: 'Gastos',
        href: expensesIndex(),
        icon: Receipt,
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repositorio',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentación',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
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
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
