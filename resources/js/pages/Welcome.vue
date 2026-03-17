<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

import Spinner from '@/components/Spinner.vue';

const roomName = ref(null);

const isCreatingRoom = ref(false);

const createRoom = () => {
    if (isCreatingRoom.value) {
        return;
    }

    isCreatingRoom.value = true;

    router.post(
        '/room/create',
        {
            name: roomName.value,
        },
        {
            onSuccess: () => {
                // ...
            },

            onError: () => {
                // ...
            },

            onFinish: () => {
                isCreatingRoom.value = false;
            },
        },
    );
};
</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <div
        class="min-h-screen bg-linear-to-br from-purple-100 via-blue-100 to-pink-50 px-4 py-8"
    >
        <!-- Header -->
        <header class="w-64 max-md:mx-auto">
            <Link href="/">
                <img
                    src="static/img/logo.png"
                    class="transition duration-200 hover:scale-115"
                    alt="Draw Together Logo"
                />
            </Link>
        </header>

        <div class="flex flex-col items-center justify-center">
            <!-- Welcome -->
            <div class="mb-12 text-center">
                <h1 class="mb-2 text-4xl font-bold text-blue-900 md:text-5xl">
                    <span
                        class="bg-linear-to-br from-blue-300 to-blue-800 bg-clip-text text-transparent"
                        >Welcome
                    </span>
                    <span
                        class="bg-linear-to-br from-blue-300 to-blue-800 bg-clip-text text-transparent"
                        >to Draw
                    </span>
                    <span
                        class="bg-linear-to-br from-blue-300 to-blue-800 bg-clip-text text-transparent"
                        >Together!
                    </span>
                </h1>
                <p class="text-lg font-bold text-blue-400 md:text-xl">
                    Join or create a drawing room
                </p>
            </div>

            <!-- Cards -->
            <div
                class="mt-10 grid w-full max-w-5xl grid-cols-1 gap-10 gap-y-20 font-bold select-none md:grid-cols-2"
            >
                <!-- Join Card -->
                <div
                    class="relative flex flex-col items-center rounded-3xl bg-white/90 p-10 shadow-2xl backdrop-blur transition duration-300 hover:-translate-y-1 hover:shadow-[0_20px_60px_rgba(0,0,0,0.15)]"
                >
                    <!-- glow border -->
                    <div
                        class="pointer-events-none absolute inset-0 rounded-3xl border border-white/40"
                    ></div>

                    <!-- floating icon -->
                    <div
                        class="absolute -top-20 transition duration-300 hover:scale-105"
                    >
                        <img
                            src="static/img/join.png"
                            alt="join icon"
                            class="drop-shadow-lg"
                        />
                    </div>

                    <div class="mt-36 w-full">
                        <!-- input -->
                        <input
                            type="text"
                            placeholder="Enter Room Code"
                            class="mb-6 w-full rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm transition focus:border-purple-300 focus:ring-2 focus:ring-purple-200 focus:outline-none"
                        />

                        <!-- button -->
                        <button
                            class="w-full rounded-xl bg-linear-to-r from-purple-400 to-purple-500 py-3 font-semibold text-white shadow-lg transition duration-200 hover:scale-[1.02] hover:from-purple-500 hover:to-purple-600 active:scale-95"
                        >
                            Join Room
                        </button>

                        <!-- avatars -->
                        <div class="mt-6 flex justify-center">
                            <div>
                                <div class="flex items-center -space-x-3">
                                    <img
                                        class="h-12 w-12 rounded-full border-2 border-white shadow"
                                        src="static/img/avatars/avatar1.png"
                                    />
                                    <img
                                        class="h-12 w-12 rounded-full border-2 border-white shadow"
                                        src="static/img/avatars/avatar2.png"
                                    />
                                    <img
                                        class="h-12 w-12 rounded-full border-2 border-white shadow"
                                        src="static/img/avatars/avatar3.png"
                                    />
                                </div>

                                <p
                                    class="mt-2 bg-linear-to-br from-purple-400 to-purple-700 bg-clip-text text-center text-sm font-semibold text-transparent"
                                >
                                    1275 Rooms Live
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Create Card -->
                <div
                    class="relative flex flex-col items-center rounded-3xl bg-white/90 p-10 shadow-2xl backdrop-blur transition duration-300 hover:-translate-y-1 hover:shadow-[0_20px_60px_rgba(0,0,0,0.15)]"
                >
                    <!-- gradient border glow -->
                    <div
                        class="pointer-events-none absolute inset-0 rounded-3xl border border-white/40"
                    ></div>

                    <!-- floating icon -->
                    <div
                        class="absolute -top-20 mb-30 transition duration-300 hover:scale-105"
                    >
                        <img
                            src="static/img/create.png"
                            alt="create icon"
                            class="drop-shadow-lg"
                        />
                    </div>

                    <div class="mt-36 w-full">
                        <!-- input -->
                        <input
                            v-model="roomName"
                            type="text"
                            placeholder="Room Name"
                            class="mb-6 w-full rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm transition focus:border-teal-300 focus:ring-2 focus:ring-teal-200 focus:outline-none"
                        />

                        <!-- button -->
                        <button
                            @click="createRoom"
                            :disabled="isCreatingRoom"
                            class="w-full rounded-xl bg-linear-to-r from-teal-400 to-blue-400 py-3 font-semibold text-white shadow-lg transition duration-200"
                            :class="[
                                isCreatingRoom
                                    ? 'cursor-not-allowed opacity-70'
                                    : 'cursor-pointer hover:scale-[1.02] hover:from-teal-500 hover:to-blue-500 active:scale-95',
                            ]"
                        >
                            <span
                                class="flex items-center justify-center gap-2"
                            >
                                <Spinner v-if="isCreatingRoom" />

                                {{
                                    isCreatingRoom
                                        ? 'Creating...'
                                        : 'Create Room'
                                }}
                            </span>
                        </button>

                        <!-- error -->
                        <div
                            v-if="$page.props.errors.name"
                            class="mt-3 flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 shadow-sm"
                        >
                            <svg
                                class="h-5 w-5 text-red-500"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"
                                />
                            </svg>

                            <span>
                                {{ $page.props.errors.name }}
                            </span>
                        </div>

                        <!-- avatars -->
                        <div class="mt-6 flex justify-center">
                            <div>
                                <div class="flex items-center -space-x-3">
                                    <img
                                        class="h-12 w-12 rounded-full border-2 border-white shadow"
                                        src="static/img/avatars/avatar4.png"
                                    />
                                    <img
                                        class="h-12 w-12 rounded-full border-2 border-white shadow"
                                        src="static/img/avatars/avatar5.png"
                                    />
                                    <img
                                        class="h-12 w-12 rounded-full border-2 border-white shadow"
                                        src="static/img/avatars/avatar6.png"
                                    />
                                </div>

                                <p
                                    class="mt-2 bg-linear-to-br from-blue-400 to-blue-700 bg-clip-text text-center text-sm font-semibold text-transparent"
                                >
                                    Invite your friends
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
