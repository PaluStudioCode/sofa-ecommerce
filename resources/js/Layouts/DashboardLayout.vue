<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Boxes,
    ChartNoAxesCombined,
    ChevronDown,
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
const openGroups = ref({});

const groups = computed(() => props.navigationGroups.length ? props.navigationGroups : page.props.navigationGroups || []);
const dashboardItem = computed(() => {
    for (const group of groups.value) {
        const item = group.items?.find((item) => item.href === '/dashboard');

        if (item) {
            return item;
        }
    }

    return null;
});
const menuGroups = computed(() => groups.value
    .map((group) => ({
        ...group,
        items: group.items?.filter((item) => item.href !== '/dashboard') || [],
    }))
    .filter((group) => group.items.length > 0)
);

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

function groupKey(group) {
    return group.label;
}

function groupHasActiveItem(group) {
    return group.items?.some((item) => isActive(item));
}

function isGroupOpen(group, index) {
    const key = groupKey(group);

    if (Object.prototype.hasOwnProperty.call(openGroups.value, key)) {
        return openGroups.value[key];
    }

    return groupHasActiveItem(group) || index === 0;
}

function toggleGroup(group, index) {
    const key = groupKey(group);
    openGroups.value = {
        ...openGroups.value,
        [key]: !isGroupOpen(group, index),
    };
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
                <Link
                    v-if="dashboardItem"
                    :href="dashboardItem.href"
                    class="mb-3 flex min-h-10 items-center gap-3 rounded-md px-3 text-sm font-semibold"
                    :class="isActive(dashboardItem) ? 'bg-primary-soft text-neutral-text' : 'text-neutral-muted hover:bg-neutral-light hover:text-neutral-text'"
                >
                    <component :is="iconFor(dashboardItem.icon)" class="h-4 w-4 shrink-0" />
                    <span class="truncate">{{ dashboardItem.label }}</span>
                </Link>

                <div v-for="(group, index) in menuGroups" :key="group.label" class="mb-3">
                    <button
                        type="button"
                        class="flex min-h-10 w-full items-center justify-between gap-3 rounded-md px-3 text-left text-xs font-semibold uppercase tracking-normal text-neutral-muted hover:bg-neutral-light hover:text-neutral-text"
                        :aria-expanded="isGroupOpen(group, index)"
                        @click="toggleGroup(group, index)"
                    >
                        <span class="truncate">{{ group.label }}</span>
                        <ChevronDown
                            class="h-4 w-4 shrink-0 transition"
                            :class="isGroupOpen(group, index) ? 'rotate-180' : ''"
                        />
                    </button>

                    <div v-show="isGroupOpen(group, index)" class="mt-1 grid gap-1 border-l border-neutral-border/80 pl-4">
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
                        <div class="min-w-0">
                            <h1 class="text-lg font-semibold text-neutral-text">{{ title }}</h1>
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
                <div v-if="breadcrumbs.length" class="mb-5">
                    <Breadcrumbs :items="breadcrumbs" />
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
