<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomStroke;
use App\Models\UsersInRooms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    private const ACTIVE_WINDOW_SECONDS = 45;

    private const FALLBACK_COLOR = '#2563eb';

    public function home(): Response
    {
        return Inertia::render('Welcome', [
            'live_rooms' => $this->activeRoomsCount(),
        ]);
    }

    public function room(Room $room): Response
    {
        $viewer = $room->participants()
            ->where('session_id', $this->sessionId())
            ->first();

        return Inertia::render('Room', [
            'room' => [
                'name' => $room->name,
                'code' => $room->code,
                'invite_url' => route('room.show', $room),
            ],
            'strokes' => $room->strokes()
                ->orderBy('position')
                ->get()
                ->map(fn(RoomStroke $stroke) => $this->strokePayload($stroke))
                ->values()
                ->all(),
            'participants' => $this->activeParticipants($room)->all(),
            'viewer' => $viewer ? $this->participantPayload($viewer) : null,
        ]);
    }

    public function createRoom(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:16'],
        ]);

        $room = Room::create([
            'name' => Str::of($validated['name'])->trim()->squish()->value(),
            'code' => $this->generateRoomCode(),
            'last_activity_at' => now(),
        ]);

        $this->ensureParticipant($room);

        return redirect()->route('room.show', $room);
    }

    public function joinRoom(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:8', 'exists:rooms,code'],
        ]);

        $room = Room::where('code', $validated['code'])->first();

        $this->ensureParticipant($room);

        return redirect()->route('room.show', $room);
    }

    public function syncPresence(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'min:2', 'max:24'],
            'color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ]);

        $participant = $this->ensureParticipant($room, [
            'display_name' => Str::of($validated['display_name'])->trim()->squish()->value(),
            'color' => Str::lower($validated['color']),
            'last_seen_at' => now(),
        ]);

        $room->forceFill([
            'last_activity_at' => now(),
        ])->save();

        return response()->json([
            'viewer' => $this->participantPayload($participant),
            'participants' => $this->activeParticipants($room)->all(),
            'live_rooms' => $this->activeRoomsCount(),
        ]);
    }

    public function leaveRoom(Room $room): JsonResponse
    {
        $room->participants()
            ->where('session_id', $this->sessionId())
            ->delete();

        return response()->json([
            'participants' => $this->activeParticipants($room)->all(),
            'live_rooms' => $this->activeRoomsCount(),
        ]);
    }

    public function storeStroke(Request $request, Room $room): JsonResponse
    {
        $validated = $request->validate([
            'stroke_id' => ['required', 'string', 'max:64'],
            'display_name' => ['required', 'string', 'min:2', 'max:24'],
            'tool' => ['required', Rule::in(['pen', 'brush', 'eraser'])],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'size' => ['required', 'integer', 'min:2', 'max:32'],
            'points' => ['required', 'array', 'min:1'],
            'points.*.x' => ['required', 'numeric', 'min:0', 'max:1200'],
            'points.*.y' => ['required', 'numeric', 'min:0', 'max:800'],
        ]);

        $participant = $this->ensureParticipant($room, [
            'display_name' => Str::of($validated['display_name'])->trim()->squish()->value(),
            'color' => Str::lower($validated['color'] ?? self::FALLBACK_COLOR),
            'last_seen_at' => now(),
        ]);

        $points = collect($validated['points'])
            ->map(fn(array $point) => [
                'x' => round((float) $point['x'], 2),
                'y' => round((float) $point['y'], 2),
            ])
            ->values()
            ->all();

        $stroke = DB::transaction(function () use ($participant, $points, $room, $validated) {
            $existingStroke = $room->strokes()
                ->where('stroke_id', $validated['stroke_id'])
                ->first();

            if ($existingStroke) {
                return $existingStroke;
            }

            $position = ((int) $room->strokes()->max('position')) + 1;

            return $room->strokes()->create([
                'stroke_id' => $validated['stroke_id'],
                'session_id' => $participant->session_id,
                'display_name' => $participant->display_name ?? 'Guest Artist',
                'tool' => $validated['tool'],
                'color' => $validated['tool'] === 'eraser'
                    ? null
                    : Str::lower($validated['color'] ?? self::FALLBACK_COLOR),
                'size' => $validated['size'],
                'points' => $points,
                'position' => $position,
            ]);
        });

        $room->forceFill([
            'last_activity_at' => now(),
        ])->save();

        return response()->json([
            'stroke' => $this->strokePayload($stroke),
            'participants' => $this->activeParticipants($room)->all(),
        ]);
    }

    public function undoStroke(Room $room): JsonResponse
    {
        $stroke = $room->strokes()
            ->orderByDesc('position')
            ->first();

        if (! $stroke) {
            return response()->json([
                'removed_stroke_id' => null,
            ]);
        }

        $removedStrokeId = $stroke->stroke_id;

        $stroke->delete();

        $room->forceFill([
            'last_activity_at' => now(),
        ])->save();

        return response()->json([
            'removed_stroke_id' => $removedStrokeId,
        ]);
    }

    public function clearRoom(Room $room): JsonResponse
    {
        $room->strokes()->delete();

        $room->forceFill([
            'last_activity_at' => now(),
        ])->save();

        return response()->json([
            'cleared' => true,
        ]);
    }

    private function generateRoomCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (Room::where('code', $code)->exists());

        return $code;
    }

    private function ensureParticipant(Room $room, array $attributes = []): UsersInRooms
    {
        $participant = UsersInRooms::updateOrCreate(
            [
                'session_id' => $this->sessionId(),
                'room_id' => $room->id,
            ],
            [
                ...$attributes,
                'last_seen_at' => $attributes['last_seen_at'] ?? now(),
            ],
        );

        return $participant->refresh();
    }

    private function activeParticipants(Room $room): Collection
    {
        return $room->participants()
            ->active(self::ACTIVE_WINDOW_SECONDS)
            ->orderBy('display_name')
            ->get()
            ->map(fn(UsersInRooms $participant) => $this->participantPayload($participant))
            ->values();
    }

    private function participantPayload(UsersInRooms $participant): array
    {
        return [
            'session_id' => $participant->session_id,
            'display_name' => $participant->display_name ?: 'Guest Artist',
            'color' => $participant->color ?: self::FALLBACK_COLOR,
            'is_viewer' => $participant->session_id === $this->sessionId(),
        ];
    }

    private function strokePayload(RoomStroke $stroke): array
    {
        return [
            'id' => $stroke->stroke_id,
            'session_id' => $stroke->session_id,
            'display_name' => $stroke->display_name,
            'tool' => $stroke->tool,
            'color' => $stroke->color,
            'size' => $stroke->size,
            'points' => $stroke->points,
            'position' => $stroke->position,
        ];
    }

    private function activeRoomsCount(): int
    {
        return Room::query()
            ->whereHas('participants', fn($query) => $query->active(self::ACTIVE_WINDOW_SECONDS))
            ->count();
    }

    private function sessionId(): string
    {
        return (string) request()->session()->getId();
    }
}
