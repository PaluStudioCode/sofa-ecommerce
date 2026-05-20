<script setup>
defineProps({
    id: { type: String, required: true },
    label: { type: String, required: true },
    modelValue: { type: [String, Number], default: '' },
    type: { type: String, default: 'text' },
    error: { type: String, default: '' },
    required: { type: Boolean, default: false },
    placeholder: { type: String, default: '' },
});

defineEmits(['update:modelValue']);
</script>

<template>
    <label class="block" :for="id">
        <span class="text-sm font-medium text-neutral-text">
            {{ label }}<span v-if="required" class="text-danger"> *</span>
        </span>
        <input
            :id="id"
            :type="type"
            :value="modelValue"
            :required="required"
            :placeholder="placeholder"
            class="mt-1 block min-h-10 w-full rounded-md border-neutral-border text-sm text-neutral-text shadow-sm focus:border-primary-hover focus:ring-primary"
            :aria-invalid="Boolean(error)"
            :aria-describedby="error ? `${id}-error` : null"
            @input="$emit('update:modelValue', $event.target.value)"
        />
        <p v-if="error" :id="`${id}-error`" class="mt-1 text-sm text-danger">{{ error }}</p>
    </label>
</template>
