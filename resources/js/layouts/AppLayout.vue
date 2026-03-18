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
    background-image: url('/static/img/bg-pattern.png');
    background-position: bottom center;
    background-size: cover;
    background-repeat: no-repeat;
    background-color: #fafafa;
}
</style>
