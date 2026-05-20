<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: { type: String, default: null },
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'md' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

const classes = computed(() => {
    const variants = {
        primary: 'border-primary bg-primary text-neutral-text hover:bg-primary-hover focus:ring-primary',
        secondary: 'border-neutral-border bg-white text-neutral-text hover:bg-neutral-light focus:ring-primary',
        danger: 'border-danger bg-danger text-white hover:bg-red-700 focus:ring-danger',
        ghost: 'border-transparent bg-transparent text-neutral-text hover:bg-neutral-light focus:ring-primary',
    };
    const sizes = {
        sm: 'min-h-9 px-3 text-sm',
        md: 'min-h-10 px-4 text-sm',
        lg: 'min-h-11 px-5 text-base',
        icon: 'h-10 w-10 justify-center p-0',
    };

    return [
        'inline-flex items-center justify-center gap-2 rounded-md border font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2',
        variants[props.variant] || variants.primary,
        sizes[props.size] || sizes.md,
        (props.disabled || props.loading) ? 'cursor-not-allowed opacity-60' : '',
    ].join(' ');
});
</script>

<template>
    <Link v-if="href" :href="href" :class="classes" :aria-disabled="disabled || loading">
        <slot />
    </Link>
    <button v-else :type="type" :disabled="disabled || loading" :class="classes">
        <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
        <slot />
    </button>
</template>
