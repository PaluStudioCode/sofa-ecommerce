<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Clock3, Mail, MapPin, Menu, Phone, ShoppingCart, User, X } from '@lucide/vue';
import AppButton from '@/Components/UI/AppButton.vue';

const page = usePage();
const open = ref(false);
const currentPath = computed(() => page.url.split('?')[0]);
const cartItemsCount = computed(() => Number(page.props.cart?.items_count || 0));
const cartBadgeLabel = computed(() => cartItemsCount.value > 99 ? '99+' : String(cartItemsCount.value));

function isNavActive(paths) {
    const pathList = Array.isArray(paths) ? paths : [paths];

    return pathList.some((path) => {
        if (path === '/') {
            return currentPath.value === '/';
        }

        return currentPath.value === path || currentPath.value.startsWith(`${path}/`);
    });
}

function desktopNavClass(paths) {
    return [
        'inline-flex min-h-9 items-center gap-2 rounded-md px-3 font-semibold transition',
        isNavActive(paths) ? 'bg-primary-soft text-neutral-text' : 'text-neutral-muted hover:bg-neutral-light hover:text-neutral-text',
    ].join(' ');
}

function mobileNavClass(paths) {
    return [
        'rounded-md px-3 py-2 font-semibold transition',
        isNavActive(paths) ? 'bg-primary-soft text-neutral-text' : 'text-neutral-muted hover:bg-neutral-light hover:text-neutral-text',
    ].join(' ');
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-neutral-text">
        <header class="sticky top-0 z-40 border-b border-neutral-border bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-lg font-bold text-neutral-text">{{ page.props.storeContact?.name || 'SofaStore' }}</Link>

                <nav class="hidden items-center gap-2 text-sm font-medium md:flex">
                    <Link href="/" :class="desktopNavClass('/')">Home</Link>
                    <Link href="/catalog" :class="desktopNavClass(['/catalog', '/products'])">Katalog</Link>
                    <Link v-if="!page.props.auth.user || page.props.auth.user.role === 'customer'" href="/cart" :class="desktopNavClass('/cart')">
                        <span class="relative inline-flex">
                            <ShoppingCart class="h-4 w-4" />
                            <span v-if="cartItemsCount" class="absolute -right-2 -top-2 inline-flex min-w-4 justify-center rounded-full bg-danger px-1 text-[10px] font-bold leading-4 text-white">
                                {{ cartBadgeLabel }}
                            </span>
                        </span>
                        <span>Keranjang</span>
                    </Link>
                    <Link v-if="page.props.auth.user?.role === 'customer'" href="/address" :class="desktopNavClass('/address')">
                        <MapPin class="h-4 w-4" />
                        <span>Alamat</span>
                    </Link>
                    <Link v-if="page.props.auth.user?.role === 'customer'" href="/orders" :class="desktopNavClass('/orders')">Riwayat Pesanan</Link>
                </nav>

                <div class="hidden items-center gap-2 md:flex">
                    <template v-if="page.props.auth.user">
                        <Link href="/profile" :class="desktopNavClass('/profile')">
                            <User class="h-4 w-4" />
                            {{ page.props.auth.user.name }}
                        </Link>
                        <Link :href="route('logout')" method="post" as="button" class="rounded-md px-3 py-2 text-sm font-semibold text-neutral-muted hover:bg-neutral-light hover:text-neutral-text">
                            Logout
                        </Link>
                    </template>
                    <template v-else>
                        <AppButton :href="route('login')" variant="ghost">Login</AppButton>
                        <AppButton :href="route('register')">Register</AppButton>
                    </template>
                </div>

                <button type="button" class="grid h-10 w-10 place-items-center rounded-md border border-neutral-border md:hidden" @click="open = !open">
                    <component :is="open ? X : Menu" class="h-5 w-5" />
                    <span class="sr-only">Menu</span>
                </button>
            </div>

            <div v-if="open" class="border-t border-neutral-border bg-white px-4 py-4 md:hidden">
                <nav class="grid gap-2 text-sm font-medium text-neutral-muted">
                    <Link href="/" :class="mobileNavClass('/')">Home</Link>
                    <Link href="/catalog" :class="mobileNavClass(['/catalog', '/products'])">Katalog</Link>
                    <Link v-if="!page.props.auth.user || page.props.auth.user.role === 'customer'" href="/cart" :class="[mobileNavClass('/cart'), 'flex items-center justify-between']">
                        <span>Keranjang</span>
                        <span v-if="cartItemsCount" class="inline-flex min-w-5 justify-center rounded-full bg-danger px-1.5 text-xs font-bold leading-5 text-white">
                            {{ cartBadgeLabel }}
                        </span>
                    </Link>
                    <Link v-if="page.props.auth.user?.role === 'customer'" href="/address" :class="mobileNavClass('/address')">Alamat</Link>
                    <Link v-if="page.props.auth.user?.role === 'customer'" href="/orders" :class="mobileNavClass('/orders')">Riwayat Pesanan</Link>
                    <Link v-if="page.props.auth.user" href="/profile" :class="mobileNavClass('/profile')">Akun</Link>
                    <Link v-if="page.props.auth.user" :href="route('logout')" method="post" as="button" class="rounded-md px-3 py-2 text-left hover:bg-neutral-light">Logout</Link>
                    <Link v-if="!page.props.auth.user" :href="route('login')" class="rounded-md px-3 py-2 hover:bg-neutral-light">Login</Link>
                    <Link v-if="!page.props.auth.user" :href="route('register')" class="rounded-md px-3 py-2 hover:bg-neutral-light">Register</Link>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <slot />
        </main>

        <footer class="border-t border-neutral-border bg-neutral-text text-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 md:grid-cols-[1fr_1.3fr] lg:px-8">
                <div>
                    <p class="text-lg font-bold">{{ page.props.storeContact?.name || 'SofaStore' }}</p>
                    <p class="mt-3 max-w-md text-sm leading-6 text-white/70">
                        Sofa pilihan untuk ruang keluarga, pemesanan online, dan pengiriman internal toko.
                    </p>
                </div>

                <div class="grid gap-4 text-sm sm:grid-cols-2">
                    <div class="flex gap-3">
                        <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <span class="text-white/75">{{ page.props.storeContact?.address || 'Palu, Sulawesi Tengah' }}</span>
                    </div>
                    <a :href="`mailto:${page.props.storeContact?.email || 'hello@sofastore.test'}`" class="flex gap-3 text-white/75 hover:text-white">
                        <Mail class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <span>{{ page.props.storeContact?.email || 'hello@sofastore.test' }}</span>
                    </a>
                    <a :href="`https://wa.me/${(page.props.storeContact?.whatsapp || '081234567890').replace(/\D/g, '').replace(/^0/, '62')}`" target="_blank" rel="noreferrer" class="flex gap-3 text-white/75 hover:text-white">
                        <Phone class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <span>{{ page.props.storeContact?.whatsapp || '081234567890' }}</span>
                    </a>
                    <div class="flex gap-3">
                        <Clock3 class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <span class="text-white/75">{{ page.props.storeContact?.hours || 'Senin-Sabtu, 09.00-18.00 WITA' }}</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
