<script setup>
defineProps({
    value: String,
    gradient: String,
});

defineEmits(['select']);
</script>

<template>
    <div
        class="relative h-10 w-10 cursor-pointer overflow-hidden rounded-full border-4 border-white shadow-[0_5px_15px_rgba(0,0,0,0.3)] transition hover:scale-125 active:scale-110"
        :style="{ background: gradient }"
        @click="$emit('select', value)"
    >
        <div
            class="absolute top-1 left-1 h-2 w-2 rounded-full bg-white opacity-50"
        ></div>
    </div>
</template>
