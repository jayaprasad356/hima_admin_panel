<?php

namespace App\Http\Controllers;

use App\Models\Coins;
use Illuminate\Http\Request;

class CoinsController extends Controller
{
    // List all speech texts with optional search functionality
    public function index(Request $request)
    {
        $search = $request->get('search');

        // Search speech texts by text content or language
        $coins = Coins::when($search, function ($query, $search) {
            $query->where('price', 'like', '%' . $search . '%');
        })->get();

        return view('coins.index', compact('coins'));
    }

    // Show the form to create a new speech text
    public function create()
    {
        return view('coins.create');
    }

    // Store a newly created speech text
    public function store(Request $request)
    {
        // Validate the input data
        $validated = $request->validate([
            'price' => 'required|string|max:5000',
            'coins' => 'required|string|max:255',
            'save' => 'required|string|max:255',
            'popular' => 'required|string|max:255',
        ]);

        // Create the speech text record
        Coins::create($validated);

        return redirect()->route('speech_texts.index')->with('success', 'Coins successfully created.');
    }

    // Show the form to edit an existing speech text
    public function edit($id)
    {
        $coins = Coins::findOrFail($id);
        return view('coins.edit', compact('coins'));

    }

    // Update an existing speech text
    public function update(Request $request, $id)
    {
        $speechText = SpeechText::findOrFail($id);

        // Validate the input data
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'language' => 'required|string|max:255',
        ]);

        // Update speech text details
        $speechText->update($validated);

        return redirect()->route('speech_texts.index')->with('success', 'Speech text successfully updated.');
    }

    // Delete a speech text
    public function destroy($id)
    {
        $speechText = SpeechText::findOrFail($id);
        $speechText->delete();

        return redirect()->route('speech_texts.index')->with('success', 'Speech text successfully deleted.');
    }
}
