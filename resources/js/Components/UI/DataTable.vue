<script setup>
defineProps({
    columns: { type: Array, default: () => [] },
    rows: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="overflow-x-auto border border-neutral-border bg-white">
        <table class="min-w-full divide-y divide-neutral-border text-sm">
            <thead class="bg-neutral-light text-left text-xs font-semibold uppercase tracking-normal text-neutral-muted">
                <tr>
                    <th v-for="column in columns" :key="column.key" class="px-4 py-3">
                        {{ column.label }}
                    </th>
                    <th v-if="$slots.actions" class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-border text-neutral-text">
                <tr v-for="row in rows" :key="row.id || JSON.stringify(row)" class="hover:bg-neutral-light">
                    <td v-for="column in columns" :key="column.key" class="whitespace-nowrap px-4 py-3">
                        <slot :name="`cell-${column.key}`" :row="row" :value="row[column.key]">
                            {{ row[column.key] }}
                        </slot>
                    </td>
                    <td v-if="$slots.actions" class="whitespace-nowrap px-4 py-3 text-right">
                        <slot name="actions" :row="row" />
                    </td>
                </tr>
            </tbody>
        </table>
        <div v-if="rows.length === 0" class="p-8">
            <slot name="empty" />
        </div>
    </div>
</template>
