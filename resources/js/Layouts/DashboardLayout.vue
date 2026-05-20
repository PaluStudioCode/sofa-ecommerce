<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Boxes,
    ChartNoAxesCombined,
    Circle,
    CreditCard,
    Images,
    LayoutDashboard,
    Map,
    MapPinned,
    Menu,
    PackageCheck,
    PanelTop,
    ShieldCheck,
    ShoppingBag,
    Sofa,
    Tags,
    TicketPercent,
    Truck,
    Users,
    X,
} from '@lucide/vue';
import Breadcrumbs from '@/Components/UI/Breadcrumbs.vue';

const props = defineProps({
    navigationGroups: { type: Array, default: () => [] },
    breadcrumbs: { type: Array, default: () => [] },
    title: { type: String, default: 'Dashboard' },
});

const page = usePage();
const sidebarOpen = ref(false);

const groups = computed(() => props.navigationGroups.length ? props.navigationGroups : page.props.navigationGroups || []);

const iconMap = {
    Boxes,
    ChartNoAxesCombined,
    Circle,
    CreditCard,
    Images,
    LayoutDashboard,
    Map,
    MapPinned,
    Menu,
    PackageCheck,
    PanelTop,
    ShieldCheck,
    ShoppingBag,
    Sofa,
    Tags,
    TicketPercent,
    Truck,
    Users,
    X,
};

function iconFor(name) {
    return iconMap[name] || Circle;
}

function isActive(item) {
    const current = page.url.split('?')[0];
    return current === item.href || (item.href !== '/dashboard' && current.startsWith(item.href));
}
</script>

<template>
    <div class="min-h-screen bg-neutral-light text-neutral-text">
        <aside class="fixed inset-y-0 left-0 z-50 w-72 transform border-r border-neutral-border bg-white transition lg:translate-x-0" :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-16 items-center justify-between border-b border-neutral-border px-4">
                <Link href="/dashboard" class="font-bold">SofaStore</Link>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-md hover:bg-neutral-light lg:hidden" @click="sidebarOpen = false">
                    <X class="h-5 w-5" />
                    <span class="sr-only">Tutup</span>
                </button>
            </div>

            <nav class="h-[calc(100vh-4rem)] overflow-y-auto p-3">
                <div v-for="group in groups" :key="group.label" class="mb-4">
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-normal text-neutral-muted">{{ group.label }}</p>
                    <div class="grid gap-1">
                        <Link
                            v-for="item in group.items"
                            :key="item.label"
                            :href="item.href"
                            class="flex min-h-10 items-center gap-3 rounded-md px-3 text-sm font-semibold"
                            :class="isActive(item) ? 'bg-primary-soft text-neutral-text' : 'text-neutral-muted hover:bg-neutral-light hover:text-neutral-text'"
                        >
                            <component :is="iconFor(item.icon)" class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ item.label }}</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </aside>

        <div v-if="sidebarOpen" class="fixed inset-0 z-40 bg-black/30 lg:hidden" @click="sidebarOpen = false" />

        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-neutral-border bg-white">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <button type="button" class="grid h-10 w-10 place-items-center rounded-md border border-neutral-border lg:hidden" @click="sidebarOpen = true">
                            <Menu class="h-5 w-5" />
                            <span class="sr-only">Menu</span>
                        </button>
                        <div>
                            <h1 class="text-lg font-semibold text-neutral-text">{{ title }}</h1>
                            <Breadcrumbs :items="breadcrumbs" class="hidden sm:block" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-semibold text-neutral-text">{{ page.props.auth.user.name }}</p>
                            <p class="text-xs capitalize text-neutral-muted">{{ page.props.auth.user.role }}</p>
                        </div>
                        <Link :href="route('logout')" method="post" as="button" class="rounded-md border border-neutral-border px-3 py-2 text-sm font-semibold text-neutral-muted hover:bg-neutral-light">
                            Logout
                        </Link>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:p-6">
                <slot />
            </main>
        </div>
    </div>
</template>
