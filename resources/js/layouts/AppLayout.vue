<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="py8 min-h-screen bg-linear-to-br from-purple-100 via-blue-100 to-pink-50 px-4"
    >
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
