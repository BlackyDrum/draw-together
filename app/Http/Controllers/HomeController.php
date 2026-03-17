<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        return Inertia::render('Welcome');
    }

    public function create_room(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:5|max:16'
        ]);
    }
}
