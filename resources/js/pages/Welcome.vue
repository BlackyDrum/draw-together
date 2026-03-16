<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-linear-to-br from-purple-100 via-blue-100 to-pink-50 px-4 py-8"
    >
        <!-- Header -->
        <header class="">
            <img src="static/img/logo.png" alt="Draw Together Logo" />
        </header>

        <!-- Welcome -->
        <div class="mb-12 text-center">
            <h1 class="mb-2 text-4xl font-bold text-blue-900 md:text-5xl">
                Welcome <span class="text-blue-400">to Draw</span> Together!
            </h1>
            <p class="text-lg font-bold text-blue-400 md:text-xl">
                Join or create a drawing room
            </p>
        </div>

        <!-- Cards -->
        <div class="grid w-full max-w-5xl grid-cols-1 gap-10 md:grid-cols-2">
            <!-- Join Card -->
            <div
                class="relative flex flex-col items-center rounded-3xl bg-white p-10 shadow-2xl"
            >
                <div class="mb-6">
                    <img src="static/img/join.png" alt="join icon" />
                </div>
                <input
                    v-model="joinCode"
                    type="text"
                    placeholder="Enter Room Code"
                    class="mb-6 w-full rounded-xl border border-gray-200 px-5 py-3 transition focus:ring-2 focus:ring-purple-200 focus:outline-none"
                />
                <button
                    @click="joinRoom"
                    class="w-full rounded-xl bg-linear-to-r from-purple-400 to-purple-500 py-3 font-semibold text-white shadow transition hover:cursor-pointer hover:from-purple-500 hover:to-purple-600"
                >
                    Join Room
                </button>
                <div class="mt-6 flex items-center -space-x-3">
                    <img
                        class="h-12 w-12 rounded-full border-2 border-white"
                        src="https://randomuser.me/api/portraits/women/44.jpg"
                    />
                    <img
                        class="h-12 w-12 rounded-full border-2 border-white"
                        src="https://randomuser.me/api/portraits/women/68.jpg"
                    />
                    <img
                        class="h-12 w-12 rounded-full border-2 border-white"
                        src="https://randomuser.me/api/portraits/women/72.jpg"
                    />
                </div>
                <p class="mt-2 text-sm text-gray-500">1,254 Rooms Live</p>
            </div>

            <!-- Create Card -->
            <div
                class="relative flex flex-col items-center rounded-3xl bg-white p-10 shadow-2xl"
            >
                <div class="mb-6">
                    <img src="static/img/create.png" alt="create icon" />
                </div>
                <input
                    v-model="createName"
                    type="text"
                    placeholder="Room Name"
                    class="mb-6 w-full rounded-xl border border-gray-200 px-5 py-3 transition focus:ring-2 focus:ring-teal-200 focus:outline-none"
                />
                <button
                    @click="createRoom"
                    class="w-full rounded-xl bg-linear-to-r from-teal-400 to-blue-400 py-3 font-semibold text-white shadow transition hover:cursor-pointer hover:from-teal-500 hover:to-blue-500"
                >
                    Create Room
                </button>
                <div class="mt-6 flex items-center -space-x-3">
                    <img
                        class="h-12 w-12 rounded-full border-2 border-white"
                        src="https://randomuser.me/api/portraits/men/45.jpg"
                    />
                    <img
                        class="h-12 w-12 rounded-full border-2 border-white"
                        src="https://randomuser.me/api/portraits/women/52.jpg"
                    />
                    <img
                        class="h-12 w-12 rounded-full border-2 border-white"
                        src="https://randomuser.me/api/portraits/men/34.jpg"
                    />
                </div>
                <p class="mt-2 text-sm text-gray-500">Invite Your Friends</p>
            </div>
        </div>
    </div>
</template>
