<script setup>
import { ref, onMounted } from 'vue';

import AppLayout from '@/layouts/AppLayout.vue';
import Divider from '@/components/Divider.vue';
import Tool from '@/components/Tool.vue';
import ColorDot from '@/components/ColorDot.vue';

const canvas = ref(null);
const ctx = ref(null);

const drawing = ref(false);

const color = ref('#000000');
const size = ref(4);

const history = [];

const tool = ref('pen');

const colors = [
    {
        value: '#000',
        gradient: 'radial-gradient(circle at 30% 30%, #000, #111)',
    },
    {
        value: 'red',
        gradient: 'radial-gradient(circle at 30% 30%, #ff4d4d, #cc0000)',
    },
    {
        value: 'blue',
        gradient: 'radial-gradient(circle at 30% 30%, #4d9aff, #0033cc)',
    },
    {
        value: 'green',
        gradient: 'radial-gradient(circle at 30% 30%, #66ff66, #009900)',
    },
    {
        value: 'orange',
        gradient: 'radial-gradient(circle at 30% 30%, #ffb84d, #cc6600)',
    },
];

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
                <Tool
                    imagePath="/static/img/tools/pen.png"
                    @click="
                        tool = 'pen';
                        size = 4;
                    "
                >
                </Tool>

                <Divider />

                <!-- Brush -->
                <Tool
                    imagePath="/static/img/tools/brush.png"
                    @click="
                        tool = 'brush';
                        size = 10;
                    "
                >
                </Tool>

                <Divider />

                <!-- Undo -->
                <Tool imagePath="/static/img/tools/undo.png" @click="undo">
                </Tool>

                <Divider />

                <!-- Trash -->
                <Tool
                    imagePath="/static/img/tools/trash.png"
                    @click="clearCanvas"
                >
                </Tool>

                <Divider />

                <!-- colors -->
                <div class="mt-1 flex flex-col items-center gap-4">
                    <ColorDot
                        v-for="c in colors"
                        :key="c.value"
                        :value="c.value"
                        :gradient="c.gradient"
                        @select="color = $event"
                    />
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
