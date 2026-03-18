<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        return Inertia::render('Welcome');
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
            'is_active' => true,
            'code' => Str::random(8)
        ]);

        return redirect()->route('room.show', [
            'code' => $room->code
        ]);
    }
}
