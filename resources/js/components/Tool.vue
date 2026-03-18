<script setup>
defineProps(['imagePath']);
</script>

<template>
    <button
        @click="$emit('click')"
        class="inline-flex cursor-pointer items-center justify-center rounded-xl p-2 transition hover:bg-blue-100/30 active:scale-95"
    >
        <img
            :src="imagePath"
            class="h-17 w-17 object-contain transition hover:scale-110"
            alt="Tool"
        />
    </button>
</template>
