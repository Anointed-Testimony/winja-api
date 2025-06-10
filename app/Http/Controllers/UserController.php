<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // List users with filters
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'referred', 'interests']);
        $users = User::filter($filters)->get();
        return response()->json($users);
    }

    // Show user profile and activity
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user);
    }

    // Update user (role, status, etc)
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $data = $request->validate([
            'user_type' => 'in:user,admin,partner',
            'status' => 'in:active,inactive,banned',
        ]);
        $user->update($data);
        return response()->json($user);
    }

    // Ban or deactivate user
    public function ban($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'banned';
        $user->save();
        return response()->json(['message' => 'User banned']);
    }

    public function deactivate($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'inactive';
        $user->save();
        return response()->json(['message' => 'User deactivated']);
    }

    public function activate($id)
    {
        $user = User::findOrFail($id);
        $user->status = 'active';
        $user->save();
        return response()->json(['message' => 'User activated']);
    }
} 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 