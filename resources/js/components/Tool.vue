<script setup lang="ts">
defineProps<{
    active?: boolean;
    imagePath?: string;
    label: string;
}>();
</script>

<template>
    <button
        type="button"
        class="inline-flex cursor-pointer items-center justify-center rounded-2xl border p-2.5 transition active:scale-95"
        :class="
            active
                ? 'border-blue-300 bg-blue-50 text-blue-700 shadow-[0_10px_20px_rgba(37,99,235,0.18)]'
                : 'border-transparent bg-white/80 text-slate-600 hover:border-blue-200 hover:bg-blue-50/70'
        "
        :title="label"
        :aria-label="label"
    >
        <slot>
            <img
                v-if="imagePath"
                :src="imagePath"
                class="h-11 w-11 object-contain transition hover:scale-105"
                :alt="label"
            />

            <span v-else class="text-xs font-semibold tracking-[0.2em] uppercase">
                {{ label }}
            </span>
        </slot>
    </button>
</template>
