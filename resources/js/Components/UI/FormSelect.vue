<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: [String, Number, Boolean], default: '' },
    options: { type: Array, default: () => [] },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block" :for="id">
        <span class="text-sm font-medium text-neutral-text">
            {{ label }}<span v-if="required" class="text-danger"> *</span>
        </span>
        <select
            :id="id"
            :value="modelValue"
            :required="required"
            class="mt-1 block min-h-10 w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary"
            :aria-invalid="Boolean(error)"
            @change="$emit('update:modelValue', $event.target.value)"
        >
            <option v-for="option in options" :key="option.value" :value="option.value">
                {{ option.label }}
            </option>
        </select>
        <p v-if="error" class="mt-1 text-sm text-danger">{{ error }}</p>
    </label>
</template>
