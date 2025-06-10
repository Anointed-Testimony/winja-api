<?php

namespace App\Http\Controllers;

use App\Models\OpportunityType;
use Illuminate\Http\Request;

class OpportunityTypeController extends Controller
{
    public function index()
    {
        return response()->json(OpportunityType::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:opportunity_types,name',
            'description' => 'nullable|string',
            'created_by' => 'nullable|integer',
        ]);
        $type = OpportunityType::create($data);
        return response()->json($type, 201);
    }

    public function show($id)
    {
        $type = OpportunityType::findOrFail($id);
        return response()->json($type);
    }

    public function update(Request $request, $id)
    {
        $type = OpportunityType::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|required|string|unique:opportunity_types,name,' . $id,
            'description' => 'nullable|string',
            'created_by' => 'nullable|integer',
        ]);
        $type->update($data);
        return response()->json($type);
    }

    public function destroy($id)
    {
        $type = OpportunityType::findOrFail($id);
        $type->delete();
        return response()->json(['message' => 'Deleted']);
    }
} 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 