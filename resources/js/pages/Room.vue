<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { echo, useConnectionStatus } from '@laravel/echo-vue';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

import ColorDot from '@/components/ColorDot.vue';
import Tool from '@/components/Tool.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type {
    CanvasPoint,
    RoomPageProps,
    RoomParticipant,
    RoomStroke,
} from '@/types';

type ToolMode = RoomStroke['tool'];

type StrokeDraft = Omit<RoomStroke, 'position'>;

type CursorState = {
    session_id: string;
    display_name: string;
    color: string;
    x: number;
    y: number;
    drawing: boolean;
    last_seen: number;
};

type PresenceResponse = {
    viewer: RoomParticipant;
    participants: RoomParticipant[];
    live_rooms: number;
};

type StrokeResponse = {
    stroke: RoomStroke;
    participants: RoomParticipant[];
};

type StrokePreviewPayload = {
    stroke_id: string;
    session_id: string;
    display_name: string;
    tool: ToolMode;
    color: string;
    size: number;
    point: CanvasPoint;
};

type StrokeCommitPayload = {
    stroke: RoomStroke;
};

type CursorPayload = {
    session_id: string;
    display_name: string;
    color: string;
    x: number;
    y: number;
    drawing: boolean;
};

type StatusTone = 'default' | 'error' | 'success';

const CANVAS_WIDTH = 1200;
const CANVAS_HEIGHT = 800;
const PRESENCE_INTERVAL_MS = 15000;
const CURSOR_TIMEOUT_MS = 1800;
const CURSOR_THROTTLE_MS = 45;
const NAME_STORAGE_KEY = 'draw-together:name';
const COLOR_STORAGE_KEY = 'draw-together:color';
const FALLBACK_NAME = 'Guest Artist';
const FALLBACK_COLOR = '#2563eb';

const page = usePage<RoomPageProps>();
const connectionStatus = useConnectionStatus();
const room = ref(page.props.room);
const roomPath = computed(() => `/room/${room.value.code}`);

const canvas = ref<HTMLCanvasElement | null>(null);
const ctx = ref<CanvasRenderingContext2D | null>(null);

const viewer = ref<RoomParticipant | null>(page.props.viewer);
const viewerSessionId = ref(
    page.props.viewer?.session_id ?? `local-${createId()}`,
);
const participants = ref<RoomParticipant[]>([...page.props.participants]);
const strokes = ref<RoomStroke[]>([...page.props.strokes]);
const cursors = ref<Record<string, CursorState>>({});

const drawing = ref(false);
const savingStroke = ref(false);
const syncingPresence = ref(false);
const statusMessage = ref('');
const statusTone = ref<StatusTone>('default');

const tool = ref<ToolMode>('pen');
const size = ref(4);
const color = ref(page.props.viewer?.color ?? FALLBACK_COLOR);
const displayName = ref(page.props.viewer?.display_name ?? '');

const currentStroke = ref<StrokeDraft | null>(null);
const activePointerId = ref<number | null>(null);

const remoteDrafts = new Map<string, StrokeDraft>();

type PrivateChannel = ReturnType<ReturnType<typeof echo>['private']>;

let roomChannel: PrivateChannel | null = null;
let presenceIntervalId: number | undefined;
let cursorCleanupIntervalId: number | undefined;
let pendingPresenceSyncId: number | undefined;
let statusTimeoutId: number | undefined;
let lastCursorSentAt = 0;
let hasLeftRoom = false;

const presetColors = [
    {
        value: '#111827',
        gradient: 'radial-gradient(circle at 30% 30%, #374151, #111827)',
    },
    {
        value: '#2563eb',
        gradient: 'radial-gradient(circle at 30% 30%, #60a5fa, #2563eb)',
    },
    {
        value: '#ef4444',
        gradient: 'radial-gradient(circle at 30% 30%, #fca5a5, #ef4444)',
    },
    {
        value: '#10b981',
        gradient: 'radial-gradient(circle at 30% 30%, #6ee7b7, #059669)',
    },
    {
        value: '#f59e0b',
        gradient: 'radial-gradient(circle at 30% 30%, #fcd34d, #d97706)',
    },
    {
        value: '#8b5cf6',
        gradient: 'radial-gradient(circle at 30% 30%, #c4b5fd, #7c3aed)',
    },
    {
        value: '#ec4899',
        gradient: 'radial-gradient(circle at 30% 30%, #f9a8d4, #db2777)',
    },
    {
        value: '#14b8a6',
        gradient: 'radial-gradient(circle at 30% 30%, #81e6d9, #14b8a6)',
    },
    {
        value: '#eab308',
        gradient: 'radial-gradient(circle at 30% 30%, #fde68a, #eab308)',
    },
    {
        value: '#ffffff',
        gradient: 'radial-gradient(circle at 30% 30%, #ffffff, #e5e7eb)',
    },
];

const activeParticipants = computed(() =>
    [...participants.value].sort((left, right) => {
        if (left.is_viewer !== right.is_viewer) {
            return left.is_viewer ? -1 : 1;
        }

        return left.display_name.localeCompare(right.display_name);
    }),
);

const cursorList = computed(() => Object.values(cursors.value));

const connectionMeta = computed(() => {
    switch (connectionStatus.value) {
        case 'connected':
            return {
                label: 'Connected',
                className: 'bg-emerald-100 text-emerald-700',
            };
        case 'connecting':
        case 'reconnecting':
            return {
                label: 'Reconnecting',
                className: 'bg-amber-100 text-amber-700',
            };
        case 'failed':
            return {
                label: 'Connection failed',
                className: 'bg-rose-100 text-rose-700',
            };
        default:
            return {
                label: 'Offline',
                className: 'bg-slate-200 text-slate-600',
            };
    }
});

watch(
    [displayName, color],
    ([nextName, nextColor]) => {
        window.localStorage.setItem(NAME_STORAGE_KEY, nextName);
        window.localStorage.setItem(COLOR_STORAGE_KEY, nextColor);
        schedulePresenceSync();
    },
    { flush: 'post' },
);

onMounted(() => {
    hydrateProfile();
    applyParticipants(participants.value);
    resizeCanvas();
    redrawCanvas();
    connectToRoomChannel();

    void syncPresence();

    presenceIntervalId = window.setInterval(() => {
        void syncPresence();
    }, PRESENCE_INTERVAL_MS);

    cursorCleanupIntervalId = window.setInterval(() => {
        pruneCursors();
    }, 500);

    window.addEventListener('resize', resizeCanvas);
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onBeforeUnmount(() => {
    cleanupRoomState(false);
});

function hydrateProfile() {
    const storedName = window.localStorage.getItem(NAME_STORAGE_KEY) ?? '';
    const storedColor = window.localStorage.getItem(COLOR_STORAGE_KEY) ?? '';

    if (!displayName.value || displayName.value === FALLBACK_NAME) {
        displayName.value =
            sanitizeDisplayName(storedName) || defaultDisplayName();
    }

    if (!isHexColor(color.value) || color.value === FALLBACK_COLOR) {
        color.value = isHexColor(storedColor)
            ? storedColor
            : page.props.viewer?.color || presetColors[1].value;
    }
}

function connectToRoomChannel() {
    roomChannel = echo().private(`room.${room.value.code}`);

    roomChannel.listenForWhisper(
        'stroke-start',
        (payload: StrokePreviewPayload) => {
            if (payload.session_id === viewerSessionId.value) {
                return;
            }

            remoteDrafts.set(payload.stroke_id, {
                id: payload.stroke_id,
                session_id: payload.session_id,
                display_name: payload.display_name,
                tool: payload.tool,
                color: payload.tool === 'eraser' ? null : payload.color,
                size: payload.size,
                points: [payload.point],
            });

            mergeParticipant({
                session_id: payload.session_id,
                display_name: payload.display_name,
                color: payload.color,
            });

            redrawCanvas();
        },
    );

    roomChannel.listenForWhisper(
        'stroke-update',
        (payload: StrokePreviewPayload) => {
            if (payload.session_id === viewerSessionId.value) {
                return;
            }

            const draft = remoteDrafts.get(payload.stroke_id);

            if (draft) {
                draft.points.push(payload.point);
            } else {
                remoteDrafts.set(payload.stroke_id, {
                    id: payload.stroke_id,
                    session_id: payload.session_id,
                    display_name: payload.display_name,
                    tool: payload.tool,
                    color: payload.tool === 'eraser' ? null : payload.color,
                    size: payload.size,
                    points: [payload.point],
                });
            }

            redrawCanvas();
        },
    );

    roomChannel.listenForWhisper(
        'stroke-commit',
        ({ stroke }: StrokeCommitPayload) => {
            if (stroke.session_id === viewerSessionId.value) {
                return;
            }

            remoteDrafts.delete(stroke.id);
            upsertStroke(stroke);
            redrawCanvas();
        },
    );

    roomChannel.listenForWhisper(
        'stroke-undo',
        ({ stroke_id }: { stroke_id: string }) => {
            removeStroke(stroke_id);
            remoteDrafts.delete(stroke_id);
            redrawCanvas();
        },
    );

    roomChannel.listenForWhisper('stroke-clear', () => {
        remoteDrafts.clear();
        currentStroke.value = null;
        strokes.value = [];
        redrawCanvas();
    });

    roomChannel.listenForWhisper('cursor-move', (payload: CursorPayload) => {
        if (payload.session_id === viewerSessionId.value) {
            return;
        }

        cursors.value = {
            ...cursors.value,
            [payload.session_id]: {
                ...payload,
                last_seen: Date.now(),
            },
        };

        mergeParticipant({
            session_id: payload.session_id,
            display_name: payload.display_name,
            color: payload.color,
        });
    });

    roomChannel.listenForWhisper(
        'cursor-hide',
        ({ session_id }: { session_id: string }) => {
            removeCursor(session_id);
        },
    );
}

function resizeCanvas() {
    if (!canvas.value) {
        return;
    }

    const context = canvas.value.getContext('2d');

    if (!context) {
        return;
    }

    const devicePixelRatio = window.devicePixelRatio || 1;

    canvas.value.width = CANVAS_WIDTH * devicePixelRatio;
    canvas.value.height = CANVAS_HEIGHT * devicePixelRatio;

    context.setTransform(devicePixelRatio, 0, 0, devicePixelRatio, 0, 0);
    context.lineCap = 'round';
    context.lineJoin = 'round';

    ctx.value = context;

    redrawCanvas();
}

function redrawCanvas() {
    if (!ctx.value) {
        return;
    }

    ctx.value.clearRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);
    ctx.value.fillStyle = '#ffffff';
    ctx.value.fillRect(0, 0, CANVAS_WIDTH, CANVAS_HEIGHT);

    for (const stroke of [...strokes.value].sort(
        (left, right) => left.position - right.position,
    )) {
        drawStroke(stroke);
    }

    for (const draft of remoteDrafts.values()) {
        drawStroke(draft);
    }

    if (currentStroke.value) {
        drawStroke(currentStroke.value);
    }
}

function drawStroke(
    stroke: Pick<RoomStroke, 'tool' | 'color' | 'size' | 'points'>,
) {
    if (!ctx.value || stroke.points.length === 0) {
        return;
    }

    const strokeColor =
        stroke.tool === 'eraser' ? '#ffffff' : stroke.color || FALLBACK_COLOR;

    ctx.value.save();
    ctx.value.strokeStyle = strokeColor;
    ctx.value.fillStyle = strokeColor;
    ctx.value.lineWidth = stroke.size;

    if (stroke.points.length === 1) {
        ctx.value.beginPath();
        ctx.value.arc(
            stroke.points[0].x,
            stroke.points[0].y,
            stroke.size / 2,
            0,
            Math.PI * 2,
        );
        ctx.value.fill();
        ctx.value.restore();

        return;
    }

    ctx.value.beginPath();
    ctx.value.moveTo(stroke.points[0].x, stroke.points[0].y);

    for (const point of stroke.points.slice(1)) {
        ctx.value.lineTo(point.x, point.y);
    }

    ctx.value.stroke();
    ctx.value.restore();
}

function selectTool(nextTool: ToolMode) {
    tool.value = nextTool;

    if (nextTool === 'pen') {
        size.value = 4;
    }

    if (nextTool === 'brush') {
        size.value = 10;
    }

    if (nextTool === 'eraser') {
        size.value = 18;
    }
}

function handlePointerDown(event: PointerEvent) {
    if (event.pointerType === 'mouse' && event.button !== 0) {
        return;
    }

    const point = getCanvasPoint(event);

    if (!point || !canvas.value) {
        return;
    }

    activePointerId.value = event.pointerId;
    drawing.value = true;

    canvas.value.setPointerCapture(event.pointerId);

    currentStroke.value = {
        id: createId(),
        session_id: viewerSessionId.value,
        display_name: effectiveDisplayName(),
        tool: tool.value,
        color: tool.value === 'eraser' ? null : color.value,
        size: size.value,
        points: [point],
    };

    roomChannel?.whisper('stroke-start', {
        stroke_id: currentStroke.value.id,
        session_id: viewerSessionId.value,
        display_name: currentStroke.value.display_name,
        tool: currentStroke.value.tool,
        color: color.value,
        size: size.value,
        point,
    } satisfies StrokePreviewPayload);

    sendCursor(point, true);
    redrawCanvas();
}

function handlePointerMove(event: PointerEvent) {
    const point = getCanvasPoint(event);

    if (!point) {
        return;
    }

    if (
        drawing.value &&
        event.pointerId === activePointerId.value &&
        currentStroke.value
    ) {
        const lastPoint =
            currentStroke.value.points[currentStroke.value.points.length - 1];

        if (distanceBetween(lastPoint, point) < 0.7) {
            sendCursor(point, true);

            return;
        }

        currentStroke.value.points.push(point);

        roomChannel?.whisper('stroke-update', {
            stroke_id: currentStroke.value.id,
            session_id: viewerSessionId.value,
            display_name: currentStroke.value.display_name,
            tool: currentStroke.value.tool,
            color: color.value,
            size: size.value,
            point,
        } satisfies StrokePreviewPayload);

        redrawCanvas();
        sendCursor(point, true);

        return;
    }

    sendCursor(point, false);
}

function handlePointerUp(event: PointerEvent) {
    if (event.pointerId !== activePointerId.value) {
        return;
    }

    if (canvas.value?.hasPointerCapture(event.pointerId)) {
        canvas.value.releasePointerCapture(event.pointerId);
    }

    const draft = currentStroke.value;

    drawing.value = false;
    activePointerId.value = null;
    currentStroke.value = null;

    if (!draft) {
        redrawCanvas();

        return;
    }

    const optimisticStroke: RoomStroke = {
        ...draft,
        position: nextPosition(),
    };

    upsertStroke(optimisticStroke);
    redrawCanvas();

    void persistStroke(optimisticStroke);
}

function handlePointerLeave() {
    if (drawing.value) {
        return;
    }

    broadcastCursorHide();
}

async function persistStroke(stroke: RoomStroke) {
    savingStroke.value = true;

    try {
        const response = await axios.post<StrokeResponse>(
            `${roomPath.value}/strokes`,
            {
                stroke_id: stroke.id,
                display_name: stroke.display_name,
                tool: stroke.tool,
                color: stroke.color ?? color.value,
                size: stroke.size,
                points: stroke.points,
            },
        );

        upsertStroke(response.data.stroke);
        applyParticipants(response.data.participants);

        roomChannel?.whisper('stroke-commit', {
            stroke: response.data.stroke,
        } satisfies StrokeCommitPayload);
    } catch {
        setStatus(
            'Stroke kept locally, but the server did not confirm it.',
            'error',
            4200,
        );
    } finally {
        savingStroke.value = false;
        redrawCanvas();
    }
}

async function syncPresence() {
    if (!canSyncPresence()) {
        return;
    }

    syncingPresence.value = true;

    try {
        const response = await axios.post<PresenceResponse>(
            `${roomPath.value}/presence`,
            {
                display_name: effectiveDisplayName(),
                color: color.value,
            },
        );

        viewer.value = response.data.viewer;
        viewerSessionId.value = response.data.viewer.session_id;
        applyParticipants(response.data.participants);
    } catch {
        setStatus('Could not refresh who is in the room.', 'error');
    } finally {
        syncingPresence.value = false;
    }
}

function schedulePresenceSync() {
    if (pendingPresenceSyncId) {
        window.clearTimeout(pendingPresenceSyncId);
    }

    pendingPresenceSyncId = window.setTimeout(() => {
        void syncPresence();
    }, 400);
}

async function undoLastStroke() {
    if (strokes.value.length === 0) {
        return;
    }

    try {
        const response = await axios.post<{ removed_stroke_id: string | null }>(
            `${roomPath.value}/strokes/undo`,
        );

        if (!response.data.removed_stroke_id) {
            return;
        }

        removeStroke(response.data.removed_stroke_id);
        remoteDrafts.delete(response.data.removed_stroke_id);

        roomChannel?.whisper('stroke-undo', {
            stroke_id: response.data.removed_stroke_id,
        });

        redrawCanvas();
    } catch {
        setStatus('Undo did not reach the server.', 'error');
    }
}

async function clearCanvas() {
    if (strokes.value.length === 0 && !currentStroke.value) {
        return;
    }

    try {
        await axios.post(`${roomPath.value}/clear`);

        strokes.value = [];
        currentStroke.value = null;
        remoteDrafts.clear();

        roomChannel?.whisper('stroke-clear', {});

        redrawCanvas();
    } catch {
        setStatus('Could not clear the board for everyone.', 'error');
    }
}

async function copyInviteLink() {
    try {
        await navigator.clipboard.writeText(room.value.invite_url);
        setStatus('Invite link copied.', 'success');
    } catch {
        setStatus('Copy failed. You can still share the room URL.', 'error');
    }
}

function exportCanvas() {
    if (!canvas.value) {
        return;
    }

    const link = document.createElement('a');
    link.href = canvas.value.toDataURL('image/png');
    link.download = `${slugify(room.value.name)}-${room.value.code}.png`;
    link.click();
}

function applyParticipants(nextParticipants: RoomParticipant[]) {
    participants.value = nextParticipants.map((participant) => ({
        ...participant,
        is_viewer: participant.session_id === viewerSessionId.value,
    }));
}

function mergeParticipant(participant: {
    session_id: string;
    display_name: string;
    color: string;
}) {
    const existingParticipant = participants.value.find(
        (item) => item.session_id === participant.session_id,
    );

    if (existingParticipant) {
        existingParticipant.display_name = participant.display_name;
        existingParticipant.color = participant.color;

        return;
    }

    participants.value = [
        ...participants.value,
        {
            ...participant,
            is_viewer: participant.session_id === viewerSessionId.value,
        },
    ];
}

function upsertStroke(stroke: RoomStroke) {
    const withoutStroke = strokes.value.filter((item) => item.id !== stroke.id);

    strokes.value = [...withoutStroke, stroke].sort(
        (left, right) => left.position - right.position,
    );
}

function removeStroke(strokeId: string) {
    strokes.value = strokes.value.filter((stroke) => stroke.id !== strokeId);
}

function sendCursor(point: CanvasPoint, isDrawing: boolean) {
    const now = Date.now();

    if (now - lastCursorSentAt < CURSOR_THROTTLE_MS) {
        return;
    }

    lastCursorSentAt = now;

    roomChannel?.whisper('cursor-move', {
        session_id: viewerSessionId.value,
        display_name: effectiveDisplayName(),
        color: color.value,
        x: point.x,
        y: point.y,
        drawing: isDrawing,
    } satisfies CursorPayload);
}

function broadcastCursorHide() {
    roomChannel?.whisper('cursor-hide', {
        session_id: viewerSessionId.value,
    });
}

function pruneCursors() {
    const activeCursorEntries = Object.entries(cursors.value).filter(
        ([, cursor]) => Date.now() - cursor.last_seen < CURSOR_TIMEOUT_MS,
    );

    cursors.value = Object.fromEntries(activeCursorEntries);
}

function removeCursor(sessionId: string) {
    const nextCursors = { ...cursors.value };
    delete nextCursors[sessionId];
    cursors.value = nextCursors;
}

function handleBeforeUnload() {
    cleanupRoomState(true);
}

function cleanupRoomState(useBeacon: boolean) {
    if (presenceIntervalId) {
        window.clearInterval(presenceIntervalId);
    }

    if (cursorCleanupIntervalId) {
        window.clearInterval(cursorCleanupIntervalId);
    }

    if (pendingPresenceSyncId) {
        window.clearTimeout(pendingPresenceSyncId);
    }

    if (statusTimeoutId) {
        window.clearTimeout(statusTimeoutId);
    }

    window.removeEventListener('resize', resizeCanvas);
    window.removeEventListener('beforeunload', handleBeforeUnload);

    broadcastCursorHide();

    if (!hasLeftRoom) {
        hasLeftRoom = true;

        if (useBeacon && navigator.sendBeacon) {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content');
            const payload = new FormData();

            if (csrfToken) {
                payload.append('_token', csrfToken);
            }

            navigator.sendBeacon(`${roomPath.value}/leave`, payload);
        } else {
            void axios.post(`${roomPath.value}/leave`);
        }
    }

    echo().leave(`room.${room.value.code}`);
}

function defaultDisplayName() {
    return `Artist ${room.value.code.slice(0, 4)}`;
}

function effectiveDisplayName() {
    return sanitizeDisplayName(displayName.value) || defaultDisplayName();
}

function canSyncPresence() {
    return effectiveDisplayName().length >= 2 && isHexColor(color.value);
}

function getCanvasPoint(event: PointerEvent): CanvasPoint | null {
    if (!canvas.value) {
        return null;
    }

    const rect = canvas.value.getBoundingClientRect();

    return {
        x: clamp(
            ((event.clientX - rect.left) / rect.width) * CANVAS_WIDTH,
            0,
            CANVAS_WIDTH,
        ),
        y: clamp(
            ((event.clientY - rect.top) / rect.height) * CANVAS_HEIGHT,
            0,
            CANVAS_HEIGHT,
        ),
    };
}

function distanceBetween(first: CanvasPoint, second: CanvasPoint) {
    return Math.hypot(first.x - second.x, first.y - second.y);
}

function nextPosition() {
    return (
        strokes.value.reduce(
            (maxPosition, stroke) => Math.max(maxPosition, stroke.position),
            -1,
        ) + 1
    );
}

function sanitizeDisplayName(value: string) {
    return value.replace(/\s+/g, ' ').trim().slice(0, 24);
}

function slugify(value: string) {
    return value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function setStatus(
    message: string,
    tone: StatusTone = 'default',
    duration = 2600,
) {
    statusMessage.value = message;
    statusTone.value = tone;

    if (statusTimeoutId) {
        window.clearTimeout(statusTimeoutId);
    }

    statusTimeoutId = window.setTimeout(() => {
        statusMessage.value = '';
    }, duration);
}

function createId() {
    if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
        return crypto.randomUUID();
    }

    return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

function clamp(value: number, min: number, max: number) {
    return Math.min(max, Math.max(min, value));
}

function isHexColor(value: string) {
    return /^#[0-9A-Fa-f]{6}$/.test(value);
}
</script>

<template>
    <AppLayout>
        <div class="mx-auto flex max-w-7xl flex-col gap-6 pb-10 lg:flex-row">
            <aside class="w-full lg:max-w-xs">
                <div
                    class="space-y-5 rounded-[32px] border border-white/70 bg-white/90 p-5 shadow-[0_20px_55px_rgba(15,23,42,0.14)] backdrop-blur"
                >
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.35em] text-slate-400 uppercase"
                        >
                            Profile
                        </p>
                        <div class="mt-3 space-y-3">
                            <label class="block">
                                <span
                                    class="mb-2 block text-sm font-semibold text-slate-700"
                                >
                                    Nickname
                                </span>
                                <input
                                    v-model="displayName"
                                    type="text"
                                    maxlength="24"
                                    placeholder="Pick a nickname"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm transition outline-none focus:border-blue-300 focus:ring-4 focus:ring-blue-100"
                                />
                            </label>

                            <div class="rounded-2xl bg-slate-50 px-4 py-3">
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span
                                        class="text-sm font-semibold text-slate-700"
                                    >
                                        Brush size
                                    </span>
                                    <span
                                        class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-500"
                                    >
                                        {{ size }} px
                                    </span>
                                </div>

                                <input
                                    v-model="size"
                                    type="range"
                                    min="2"
                                    max="32"
                                    class="mt-3 w-full accent-blue-500"
                                />
                            </div>
                        </div>
                    </div>

                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.35em] text-slate-400 uppercase"
                        >
                            Tools
                        </p>
                        <div class="mt-3 flex flex-wrap gap-3">
                            <Tool
                                label="Pen"
                                image-path="/static/img/tools/pen.png"
                                :active="tool === 'pen'"
                                @click="selectTool('pen')"
                            />
                            <Tool
                                label="Brush"
                                image-path="/static/img/tools/brush.png"
                                :active="tool === 'brush'"
                                @click="selectTool('brush')"
                            />
                            <Tool
                                label="Erase"
                                image-path="/static/img/tools/eraser.png"
                                :active="tool === 'eraser'"
                                @click="selectTool('eraser')"
                            >
                            </Tool>
                            <Tool
                                label="Undo"
                                image-path="/static/img/tools/undo.png"
                                @click="undoLastStroke"
                            />
                            <Tool
                                label="Clear"
                                image-path="/static/img/tools/trash.png"
                                @click="clearCanvas"
                            />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <p
                                class="text-xs font-semibold tracking-[0.35em] text-slate-400 uppercase"
                            >
                                Colors
                            </p>
                            <label
                                class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600"
                            >
                                Custom
                                <input
                                    v-model="color"
                                    type="color"
                                    class="h-7 w-7 cursor-pointer rounded-full border-none bg-transparent p-0"
                                />
                            </label>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-3">
                            <ColorDot
                                v-for="preset in presetColors"
                                :key="preset.value"
                                :value="preset.value"
                                :gradient="preset.gradient"
                                :selected="color === preset.value"
                                @select="color = $event"
                            />
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <p
                                class="text-xs font-semibold tracking-[0.35em] text-slate-400 uppercase"
                            >
                                People Here
                            </p>
                            <span
                                class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-600"
                            >
                                {{ activeParticipants.length }} live
                            </span>
                        </div>

                        <div class="mt-3 space-y-2">
                            <div
                                v-for="participant in activeParticipants"
                                :key="participant.session_id"
                                class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="h-3 w-3 rounded-full shadow-sm"
                                        :style="{
                                            backgroundColor: participant.color,
                                        }"
                                    ></span>
                                    <div>
                                        <p
                                            class="text-sm font-semibold text-slate-700"
                                        >
                                            {{ participant.display_name }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{
                                                participant.is_viewer
                                                    ? 'You'
                                                    : 'Collaborator'
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <span
                                    class="rounded-full px-2 py-1 text-[10px] font-semibold tracking-[0.2em] uppercase"
                                    :class="
                                        participant.is_viewer
                                            ? 'bg-blue-100 text-blue-700'
                                            : 'bg-slate-200 text-slate-600'
                                    "
                                >
                                    {{ participant.is_viewer ? 'You' : 'Live' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            <section class="min-w-0 flex-1">
                <div
                    class="rounded-[32px] border border-white/70 bg-white/92 p-5 shadow-[0_24px_60px_rgba(15,23,42,0.16)] backdrop-blur"
                >
                    <div
                        class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                    >
                        <div>
                            <p
                                class="text-xs font-semibold tracking-[0.35em] text-slate-400 uppercase"
                            >
                                Shared Room
                            </p>
                            <div class="mt-2 flex flex-wrap items-center gap-3">
                                <h1
                                    class="text-3xl font-black tracking-tight text-slate-800"
                                >
                                    {{ room.name }}
                                </h1>
                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 font-mono text-sm font-semibold text-slate-600"
                                >
                                    Code {{ room.code }}
                                </span>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="connectionMeta.className"
                                >
                                    {{ connectionMeta.label }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3 lg:justify-end">
                            <button
                                type="button"
                                class="cursor-pointer rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:text-blue-700"
                                @click="copyInviteLink"
                            >
                                Copy Invite Link
                            </button>
                            <button
                                type="button"
                                class="cursor-pointer rounded-2xl bg-linear-to-r from-blue-500 to-cyan-500 px-4 py-3 text-sm font-semibold text-white shadow-[0_16px_35px_rgba(59,130,246,0.28)] transition hover:-translate-y-0.5"
                                @click="exportCanvas"
                            >
                                Export PNG
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        <span
                            class="rounded-full px-3 py-1 text-xs font-semibold"
                            :class="
                                statusTone === 'error'
                                    ? 'bg-rose-100 text-rose-700'
                                    : statusTone === 'success'
                                      ? 'bg-emerald-100 text-emerald-700'
                                      : 'bg-slate-100 text-slate-600'
                            "
                        >
                            {{
                                statusMessage ||
                                (savingStroke
                                    ? 'Saving stroke...'
                                    : syncingPresence
                                      ? 'Syncing profile...'
                                      : 'Everything is live')
                            }}
                        </span>
                    </div>

                    <div class="mt-6 rounded-[30px] bg-slate-100 p-3 sm:p-5">
                        <div
                            class="relative overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-[inset_0_0_0_1px_rgba(255,255,255,0.5),0_20px_40px_rgba(15,23,42,0.12)]"
                        >
                            <canvas
                                ref="canvas"
                                class="block w-full touch-none select-none"
                                :style="{
                                    aspectRatio: `${CANVAS_WIDTH} / ${CANVAS_HEIGHT}`,
                                }"
                                @pointerdown="handlePointerDown"
                                @pointermove="handlePointerMove"
                                @pointerup="handlePointerUp"
                                @pointercancel="handlePointerUp"
                                @pointerleave="handlePointerLeave"
                            />

                            <div class="pointer-events-none absolute inset-0">
                                <div
                                    v-for="cursor in cursorList"
                                    :key="cursor.session_id"
                                    class="absolute"
                                    :style="{
                                        left: `${(cursor.x / CANVAS_WIDTH) * 100}%`,
                                        top: `${(cursor.y / CANVAS_HEIGHT) * 100}%`,
                                    }"
                                >
                                    <span
                                        class="absolute h-3.5 w-3.5 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow"
                                        :style="{
                                            backgroundColor: cursor.color,
                                        }"
                                    ></span>
                                    <span
                                        class="absolute top-0 left-3 -translate-y-1/2 rounded-full px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap text-white shadow"
                                        :style="{
                                            backgroundColor: cursor.color,
                                        }"
                                    >
                                        {{ cursor.display_name }}
                                        <span v-if="cursor.drawing">
                                            drawing
                                        </span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
