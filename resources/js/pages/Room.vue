<script setup lang="ts">
import { ref, onMounted } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';

const canvas = ref(null);
const ctx = ref(null);

const drawing = ref(false);

const color = ref('#000000');
const size = ref(4);

const history = [];

const tool = ref('pen');

onMounted(() => {
    ctx.value = canvas.value.getContext('2d');

    ctx.value.lineCap = 'round';
    ctx.value.lineJoin = 'round';
});

function startDraw(e) {
    drawing.value = true;
    ctx.value.beginPath();
    ctx.value.moveTo(e.offsetX, e.offsetY);

    saveState();
}

function draw(e) {
    if (!drawing.value) return;

    ctx.value.strokeStyle = color.value;
    ctx.value.lineWidth = size.value;

    ctx.value.lineTo(e.offsetX, e.offsetY);
    ctx.value.stroke();
}

function stopDraw() {
    drawing.value = false;
}

function saveState() {
    history.push(canvas.value.toDataURL());
}

function undo() {
    if (!history.length) return;

    const img = new Image();
    img.src = history.pop();

    img.onload = () => {
        ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height);

        ctx.value.drawImage(img, 0, 0);
    };
}

function clearCanvas() {
    saveState();

    ctx.value.clearRect(0, 0, canvas.value.width, canvas.value.height);
}
</script>

<template>
    <AppLayout>
        <div class="flex flex-row items-stretch justify-center gap-8">
            <!-- LEFT TOOLBAR -->
            <div
                class="flex flex-col gap-4 rounded-3xl border border-white/50 bg-white/90 p-5 shadow-[0_10px_25px_rgba(0,0,0,0.15)] backdrop-blur"
            >
                <!-- tools title -->
                <p class="text-center font-bold text-blue-500">Tools</p>

                <!-- Pen -->
                <button
                    @click="
                        tool = 'pen';
                        size = 4;
                    "
                    class="rounded-xl bg-blue-200 px-4 py-2 font-semibold text-blue-900 shadow transition hover:scale-105 active:scale-95"
                >
                    ✏️ Pen
                </button>

                <!-- Brush -->
                <button
                    @click="
                        tool = 'brush';
                        size = 10;
                    "
                    class="rounded-xl bg-purple-200 px-4 py-2 font-semibold text-purple-900 shadow transition hover:scale-105 active:scale-95"
                >
                    🖌 Brush
                </button>

                <!-- Undo -->
                <button
                    @click="undo"
                    class="rounded-xl bg-yellow-200 px-4 py-2 font-semibold text-yellow-900 shadow transition hover:scale-105 active:scale-95"
                >
                    ↩ Undo
                </button>

                <!-- Trash -->
                <button
                    @click="clearCanvas"
                    class="rounded-xl bg-red-200 px-4 py-2 font-semibold text-red-900 shadow transition hover:scale-105 active:scale-95"
                >
                    🗑 Clear
                </button>

                <!-- divider -->
                <div class="my-2 h-[2px] rounded bg-blue-100"></div>

                <!-- colors title -->
                <p class="text-center font-bold text-pink-500">Colors</p>

                <!-- colors -->
                <div class="mt-1 flex flex-col items-center gap-3">
                    <div
                        class="h-8 w-8 cursor-pointer rounded-full border-2 border-white shadow transition hover:scale-110"
                        style="background: black"
                        @click="color = '#000'"
                    />

                    <div
                        class="h-8 w-8 cursor-pointer rounded-full border-2 border-white shadow transition hover:scale-110"
                        style="background: red"
                        @click="color = 'red'"
                    />

                    <div
                        class="h-8 w-8 cursor-pointer rounded-full border-2 border-white shadow transition hover:scale-110"
                        style="background: blue"
                        @click="color = 'blue'"
                    />

                    <div
                        class="h-8 w-8 cursor-pointer rounded-full border-2 border-white shadow transition hover:scale-110"
                        style="background: green"
                        @click="color = 'green'"
                    />

                    <div
                        class="h-8 w-8 cursor-pointer rounded-full border-2 border-white shadow transition hover:scale-110"
                        style="background: orange"
                        @click="color = 'orange'"
                    />
                </div>
            </div>

            <!-- CANVAS -->
            <div
                class="rounded-[30px] border border-white/60 bg-white p-5 shadow-[0_15px_35px_rgba(0,0,0,0.2)]"
            >
                <canvas
                    ref="canvas"
                    width="900"
                    height="600"
                    class="rounded-2xl"
                    :class="[
                        tool === 'pen' && 'cursor-crosshair',
                        tool === 'brush' && 'cursor-cell',
                    ]"
                    @mousedown="startDraw"
                    @mousemove="draw"
                    @mouseup="stopDraw"
                    @mouseleave="stopDraw"
                />
            </div>
        </div>
    </AppLayout>
</template>
