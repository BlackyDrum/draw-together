<script setup lang="ts">
defineProps({
    value: String,
    gradient: String,
    selected: Boolean,
});

defineEmits(['select']);
</script>

<template>
    <button
        type="button"
        class="relative h-10 w-10 cursor-pointer overflow-hidden rounded-full border-4 border-white shadow-[0_5px_15px_rgba(0,0,0,0.3)] transition hover:scale-125 active:scale-110"
        :class="selected && 'ring-3 ring-blue-300 ring-offset-2 ring-offset-white'"
        :style="{ background: gradient }"
        @click="$emit('select', value)"
    >
        <div
            class="absolute top-1 left-1 h-2 w-2 rounded-full bg-white opacity-50"
        ></div>
    </button>
</template>
