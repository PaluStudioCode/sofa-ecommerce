<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Menu, ShoppingCart, User, X } from '@lucide/vue';
import AppButton from '@/Components/UI/AppButton.vue';

const page = usePage();
const open = ref(false);
</script>

<template>
    <div class="min-h-screen bg-white text-neutral-text">
        <header class="sticky top-0 z-40 border-b border-neutral-border bg-white/95 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <Link href="/" class="text-lg font-bold text-neutral-text">SofaStore</Link>

                <nav class="hidden items-center gap-6 text-sm font-medium text-neutral-muted md:flex">
                    <Link href="/catalog" class="hover:text-neutral-text">Katalog</Link>
                    <Link v-if="!page.props.auth.user || page.props.auth.user.role === 'customer'" href="/cart" class="inline-flex items-center gap-2 hover:text-neutral-text">
                        <ShoppingCart class="h-4 w-4" />
                        <span>Keranjang</span>
                    </Link>
                    <Link v-if="page.props.auth.user?.role === 'customer'" href="/orders" class="hover:text-neutral-text">Riwayat Pesanan</Link>
                </nav>

                <div class="hidden items-center gap-2 md:flex">
                    <template v-if="page.props.auth.user">
                        <AppButton href="/profile" variant="ghost">
                            <User class="h-4 w-4" />
                            {{ page.props.auth.user.name }}
                        </AppButton>
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
                    <Link href="/catalog" class="rounded-md px-3 py-2 hover:bg-neutral-light">Katalog</Link>
                    <Link v-if="!page.props.auth.user || page.props.auth.user.role === 'customer'" href="/cart" class="rounded-md px-3 py-2 hover:bg-neutral-light">Keranjang</Link>
                    <Link v-if="page.props.auth.user?.role === 'customer'" href="/orders" class="rounded-md px-3 py-2 hover:bg-neutral-light">Riwayat Pesanan</Link>
                    <Link v-if="page.props.auth.user" href="/profile" class="rounded-md px-3 py-2 hover:bg-neutral-light">Akun</Link>
                    <Link v-if="page.props.auth.user" :href="route('logout')" method="post" as="button" class="rounded-md px-3 py-2 text-left hover:bg-neutral-light">Logout</Link>
                    <Link v-if="!page.props.auth.user" :href="route('login')" class="rounded-md px-3 py-2 hover:bg-neutral-light">Login</Link>
                    <Link v-if="!page.props.auth.user" :href="route('register')" class="rounded-md px-3 py-2 hover:bg-neutral-light">Register</Link>
                </nav>
            </div>
        </header>

        <main>
            <slot />
        </main>
    </div>
</template>
