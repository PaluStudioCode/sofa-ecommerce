<template>
    <div class="flex flex-col gap-3 border-b border-neutral-border bg-white p-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-end">
            <slot />
        </div>
        <div v-if="$slots.actions" class="flex shrink-0 items-center gap-2">
            <slot name="actions" />
        </div>
    </div>
</template>
