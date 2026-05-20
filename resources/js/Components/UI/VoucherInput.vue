<script setup>
import AppButton from './AppButton.vue';

defineProps({
    modelValue: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
});

defineEmits(['update:modelValue', 'apply']);
</script>

<template>
    <div>
        <label class="text-sm font-medium text-neutral-text" for="voucher-code">Voucher</label>
        <div class="mt-1 flex gap-2">
            <input
                id="voucher-code"
                :value="modelValue"
                class="min-h-10 flex-1 rounded-md border-neutral-border text-sm uppercase tracking-normal text-neutral-text focus:border-primary-hover focus:ring-primary"
                type="text"
                @input="$emit('update:modelValue', $event.target.value.toUpperCase())"
            />
            <AppButton :loading="loading" variant="secondary" @click="$emit('apply')">Pakai</AppButton>
        </div>
        <p v-if="error" class="mt-1 text-sm text-danger">{{ error }}</p>
    </div>
</template>
