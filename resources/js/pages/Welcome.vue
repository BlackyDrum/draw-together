<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

import ErrorMessage from '@/components/ErrorMessage.vue';
import Spinner from '@/components/Spinner.vue';
import AppLayout from '@/layouts/AppLayout.vue';

const roomName = ref(null);
const roomCode = ref(null);

const isCreatingRoom = ref(false);
const isJoiningRoom = ref(false);

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
            onFinish: () => {
                isCreatingRoom.value = false;
            },
        },
    );
};

const joinRoom = () => {
    if (isJoiningRoom.value) {
        return;
    }

    isJoiningRoom.value = true;

    router.post(
        '/room/join',
        {
            code: roomCode.value,
        },
        {
            onFinish: () => {
                isJoiningRoom.value = false;
            },
        },
    );
};
</script>

<template>
    <AppLayout>
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
                            v-model="roomCode"
                            type="text"
                            placeholder="Enter Room Code"
                            class="mb-6 w-full rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm transition focus:border-purple-300 focus:ring-2 focus:ring-purple-200 focus:outline-none"
                        />

                        <button
                            @click="joinRoom"
                            :disabled="isJoiningRoom"
                            class="w-full rounded-xl bg-linear-to-r from-purple-400 to-purple-500 py-3 font-semibold text-white shadow-lg transition duration-200"
                            :class="[
                                isJoiningRoom
                                    ? 'cursor-not-allowed opacity-70'
                                    : 'cursor-pointer hover:scale-[1.02] hover:from-purple-500 hover:to-purple-600 active:scale-95',
                            ]"
                        >
                            <span
                                class="flex items-center justify-center gap-2"
                            >
                                <Spinner v-if="isJoiningRoom" />

                                {{ isJoiningRoom ? 'Joining...' : 'Join Room' }}
                            </span>
                        </button>

                        <ErrorMessage v-if="$page.props.errors.code">
                            {{ $page.props.errors.code }}
                        </ErrorMessage>

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
                                    class="mt-2 bg-linear-to-br from-purple-400 to-purple-700 bg-clip-text text-center text-sm font-semibold text-transparent select-text"
                                >
                                    {{ $page.props.live_rooms }} Rooms Live
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

                        <ErrorMessage v-if="$page.props.errors.name">
                            {{ $page.props.errors.name }}
                        </ErrorMessage>

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
                                    class="mt-2 bg-linear-to-br from-blue-400 to-blue-700 bg-clip-text text-center text-sm font-semibold text-transparent select-text"
                                >
                                    Invite your friends
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
