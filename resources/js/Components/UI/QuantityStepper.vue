<script setup>
import { Minus, Plus } from '@lucide/vue';

const props = defineProps({
    modelValue: { type: Number, default: 1 },
    min: { type: Number, default: 1 },
    max: { type: Number, default: 99 },
});

const emit = defineEmits(['update:modelValue']);

function update(value) {
    emit('update:modelValue', Math.min(props.max, Math.max(props.min, value)));
}
</script>

<template>
    <div class="inline-grid h-10 grid-cols-[2.5rem_3.5rem_2.5rem] rounded-md border border-neutral-border bg-white">
        <button type="button" class="grid place-items-center text-neutral-muted hover:text-neutral-text" @click="update(modelValue - 1)">
            <Minus class="h-4 w-4" />
            <span class="sr-only">Kurangi</span>
        </button>
        <input
            class="h-full border-x border-neutral-border p-0 text-center text-sm font-semibold text-neutral-text focus:border-primary-hover focus:ring-primary"
            type="number"
            :min="min"
            :max="max"
            :value="modelValue"
            @input="update(Number($event.target.value))"
        />
        <button type="button" class="grid place-items-center text-neutral-muted hover:text-neutral-text" @click="update(modelValue + 1)">
            <Plus class="h-4 w-4" />
            <span class="sr-only">Tambah</span>
        </button>
    </div>
</template>
