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
                class="flex flex-col gap-1 rounded-3xl border border-white/50 bg-white/90 p-2 shadow-[0_10px_25px_rgba(0,0,0,0.15)] backdrop-blur"
            >
                <!-- Pen -->
                <button
                    @click="
                        tool = 'pen';
                        size = 4;
                    "
                    class="inline-flex cursor-pointer items-center justify-center rounded-xl p-2 transition hover:bg-blue-100/30 active:scale-95"
                >
                    <img
                        src="/static/img/tools/pen.png"
                        class="h-17 w-17 object-contain transition hover:scale-110"
                        alt="Pen"
                    />
                </button>

                <!-- divider -->
                <div class="my-0.5 h-0.5 rounded bg-blue-100"></div>

                <!-- Brush -->
                <button
                    @click="
                        tool = 'brush';
                        size = 10;
                    "
                    class="inline-flex cursor-pointer items-center justify-center rounded-xl p-2 transition hover:bg-blue-100/30 active:scale-95"
                >
                    <img
                        src="/static/img/tools/brush.png"
                        class="h-17 w-17 object-contain transition hover:scale-110"
                        alt="Brush"
                    />
                </button>

                <!-- divider -->
                <div class="my-0.5 h-0.5 rounded bg-blue-100"></div>

                <!-- Undo -->
                <button
                    @click="undo"
                    class="inline-flex cursor-pointer items-center justify-center rounded-xl p-2 transition hover:bg-blue-100/30 active:scale-95"
                >
                    <img
                        src="/static/img/tools/undo.png"
                        class="h-17 w-17 object-contain transition hover:scale-110"
                        alt="Undo"
                    />
                </button>

                <!-- divider -->
                <div class="my-0.5 h-0.5 rounded bg-blue-100"></div>

                <!-- Trash -->
                <button
                    @click="clearCanvas"
                    class="inline-flex cursor-pointer items-center justify-center rounded-xl p-2 transition hover:bg-blue-100/30 active:scale-95"
                >
                    <img
                        src="/static/img/tools/trash.png"
                        class="h-17 w-17 object-contain transition hover:scale-110"
                        alt="Trash"
                    />
                </button>

                <!-- divider -->
                <div class="my-2 h-0.5 rounded bg-blue-100"></div>

                <!-- colors -->
                <div class="mt-1 flex flex-col items-center gap-4">
                    <div
                        class="relative h-10 w-10 cursor-pointer overflow-hidden rounded-full border-4 border-white shadow-[0_5px_15px_rgba(0,0,0,0.3)] transition hover:scale-125 active:scale-110"
                        style="
                            background: radial-gradient(
                                circle at 30% 30%,
                                #000,
                                #111
                            );
                        "
                        @click="color = '#000'"
                    >
                        <div
                            class="absolute top-1 left-1 h-2 w-2 rounded-full bg-white opacity-50"
                        ></div>
                    </div>

                    <div
                        class="relative h-10 w-10 cursor-pointer overflow-hidden rounded-full border-4 border-white shadow-[0_5px_15px_rgba(0,0,0,0.3)] transition hover:scale-125 active:scale-110"
                        style="
                            background: radial-gradient(
                                circle at 30% 30%,
                                #ff4d4d,
                                #cc0000
                            );
                        "
                        @click="color = 'red'"
                    >
                        <div
                            class="absolute top-1 left-1 h-2 w-2 rounded-full bg-white opacity-50"
                        ></div>
                    </div>

                    <div
                        class="relative h-10 w-10 cursor-pointer overflow-hidden rounded-full border-4 border-white shadow-[0_5px_15px_rgba(0,0,0,0.3)] transition hover:scale-125 active:scale-110"
                        style="
                            background: radial-gradient(
                                circle at 30% 30%,
                                #4d9aff,
                                #0033cc
                            );
                        "
                        @click="color = 'blue'"
                    >
                        <div
                            class="absolute top-1 left-1 h-2 w-2 rounded-full bg-white opacity-50"
                        ></div>
                    </div>

                    <div
                        class="relative h-10 w-10 cursor-pointer overflow-hidden rounded-full border-4 border-white shadow-[0_5px_15px_rgba(0,0,0,0.3)] transition hover:scale-125 active:scale-110"
                        style="
                            background: radial-gradient(
                                circle at 30% 30%,
                                #66ff66,
                                #009900
                            );
                        "
                        @click="color = 'green'"
                    >
                        <div
                            class="absolute top-1 left-1 h-2 w-2 rounded-full bg-white opacity-50"
                        ></div>
                    </div>

                    <div
                        class="relative h-10 w-10 cursor-pointer overflow-hidden rounded-full border-4 border-white shadow-[0_5px_15px_rgba(0,0,0,0.3)] transition hover:scale-125 active:scale-110"
                        style="
                            background: radial-gradient(
                                circle at 30% 30%,
                                #ffb84d,
                                #cc6600
                            );
                        "
                        @click="color = 'orange'"
                    >
                        <div
                            class="absolute top-1 left-1 h-2 w-2 rounded-full bg-white opacity-50"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- CANVAS AREA -->
            <div class="flex flex-col gap-4">
                <!-- ROOM BAR -->
                <div
                    class="flex items-center justify-between rounded-2xl border border-white/60 bg-white/90 px-6 py-3 shadow backdrop-blur"
                >
                    <!-- room name -->
                    <div class="text-lg font-bold text-blue-600">
                        {{ $page.props.room.name }}
                    </div>

                    <!-- code -->
                    <div
                        class="rounded-xl bg-blue-100 px-4 py-1 font-mono text-blue-700 shadow"
                    >
                        Code: {{ $page.props.room.code }}
                    </div>
                </div>

                <!-- CANVAS -->
                <div
                    class="relative rounded-[30px] border border-white/60 bg-white p-5 shadow-[0_15px_35px_rgba(0,0,0,0.2)]"
                >
                    <canvas
                        ref="canvas"
                        width="900"
                        height="600"
                        class="rounded-2xl shadow-[inset_0_0_15px_rgba(0,0,0,0.1),0_8px_20px_rgba(0,0,0,0.2)]"
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
        </div>
    </AppLayout>
</template>
