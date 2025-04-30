<?php

namespace App\Http\Controllers;

use App\Models\Whatsapplink;
use Illuminate\Http\Request;

class WhatsapplinkController extends Controller
{
    // List all speech texts with optional search functionality
    public function index(Request $request)
    {
        $search = $request->get('search');

        // Search speech texts by text content or language
        $whatsapplinks = Whatsapplink::when($search, function ($query, $search) {
            $query->where('text', 'like', '%' . $search . '%')
                  ->orWhere('language', 'like', '%' . $search . '%');
        })
        ->orderBy('created_at', 'desc') // Order by latest data
        ->get();

        return view('whatsapplinks.index', compact('whatsapplinks'));
    }

    // Show the form to create a new speech text
    public function create()
    {
        return view('whatsapplinks.create');
    }

    // Store a newly created speech text
    public function store(Request $request)
    {
        // Validate the input data
        $validated = $request->validate([
            'link' => 'required|string|max:5000',
            'language' => 'required|string|max:255',
        ]);

        // Create the speech text record
        Whatsapplink::create($validated);

        return redirect()->route('whatsapplinks.index')->with('success', 'Speech text successfully created.');
    }

    // Show the form to edit an existing speech text
    public function edit($id)
    {
        $whatsapplink = Whatsapplink::findOrFail($id);
        return view('whatsapplinks.edit', compact('whatsapplink'));

    }

    // Update an existing speech text
    public function update(Request $request, $id)
    {
        $whatsapplink = Whatsapplink::findOrFail($id);

        // Validate the input data
        $validated = $request->validate([
            'link' => 'required|string|max:5000',
            'language' => 'required|string|max:255',
        ]);

        // Update speech text details
        $whatsapplink->update($validated);

        return redirect()->route('whatsapplinks.index')->with('success', 'Whatsapp Links successfully updated.');
    }

    // Delete a speech text
    public function destroy($id)
    {
        $whatsapplinks = Whatsapplink::findOrFail($id);
        $whatsapplinks->delete();

        return redirect()->route('whatsapplinks.index')->with('success', 'Whatsapp Links successfully deleted.');
    }
}
