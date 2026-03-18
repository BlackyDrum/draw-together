<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="bg-drawing-pattern min-h-screen px-4">
        <!-- Header -->
        <header class="w-64 max-md:mx-auto">
            <Link :href="'/'">
                <img
                    src="/static/img/logo.png"
                    class="transition duration-200 hover:scale-115"
                    alt="Draw Together Logo"
                />
            </Link>
        </header>

        <slot />
    </div>
</template>

<style scoped>
.bg-drawing-pattern {
    position: relative;
    min-height: 100vh;
    background:
        linear-gradient(rgba(255, 255, 255, 0.8), rgba(255, 255, 255, 0.1)),
        url('/static/img/bg-pattern.png') bottom center / cover no-repeat;
}
</style>
