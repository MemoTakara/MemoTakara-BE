<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collections;
use Illuminate\Support\Facades\Auth;

class CollectionsController extends Controller
{
    public function index()
    {
        return response()->json(Collections::where('user_id', Auth::id())->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'collection_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'boolean',
            'tag' => 'nullable|string',
            'star_count' => 'numeric|min:0|max:5'
        ]);

        $collection = Collections::create([
            'user_id' => Auth::id(),
            'collection_name' => $request->collection_name,
            'description' => $request->description,
            'privacy' => $request->privacy ?? 0,
            'tag' => $request->tag,
            'star_count' => $request->star_count ?? 0
        ]);

        return response()->json($collection, 201);
    }

    public function show($id)
    {
        $collection = Collections::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($collection);
    }

    public function update(Request $request, $id)
    {
        $collection = Collections::where('user_id', Auth::id())->findOrFail($id);

        $collection->update($request->only(['collection_name', 'description', 'privacy', 'tag', 'star_count']));

        return response()->json($collection);
    }

    public function destroy($id)
    {
        $collection = Collections::where('user_id', Auth::id())->findOrFail($id);
        $collection->delete();

        return response()->json(['message' => 'Collection deleted successfully']);
    }
}
