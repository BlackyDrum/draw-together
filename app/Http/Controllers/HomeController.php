<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\UsersInRooms;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        return Inertia::render('Welcome', [
            'live_rooms' => Room::count()
        ]);
    }

    public function room($code)
    {
        $room = Room::where('code', $code)->firstOrFail();

        return Inertia::render('Room', [
            'room' => $room
        ]);
    }

    public function create_room(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:5|max:16'
        ]);

        $room = Room::create([
            'name' => $validated['name'],
            'code' => Str::random(8)
        ]);

        UsersInRooms::firstOrCreate([
            'session_id' => session()->id(),
            'room_id' => $room->id
        ]);

        return redirect()->route('room.show', [
            'code' => $room->code
        ]);
    }

    public function join_room(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64|exists:rooms,code'
        ]);

        $room = Room::where('code', $validated['code'])->first();

        UsersInRooms::firstOrCreate([
            'session_id' => session()->id(),
            'room_id' => $room->id
        ]);

        return redirect()->route('room.show', [
            'code' => $room->code
        ]);
    }
}
