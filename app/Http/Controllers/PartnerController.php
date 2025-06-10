<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\SponsoredOpportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\B2StorageService;
use Illuminate\Support\Facades\Log;

class PartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('user_type', 'partner');

        // Filter by status
        if ($request->has('status')) {
            $query->where('partner_status', $request->status);
        }

        // Search by name or company
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        $partners = $query->paginate(10);

        return response()->json($partners);
    }

    public function show($id)
    {
        $partner = User::where('user_type', 'partner')->findOrFail($id);
        
        return response()->json([
            'partner' => $partner,
            'metrics' => $partner->getPartnerMetrics(),
            'sponsored_opportunities' => $partner->sponsoredOpportunities()
                ->with('opportunity')
                ->latest()
                ->get()
        ]);
    }

    public function update(Request $request, $id)
    {
        $partner = User::where('user_type', 'partner')->findOrFail($id);

        $validated = $request->validate([
            'company_name' => 'sometimes|string|max:255',
            'company_description' => 'sometimes|string',
            'company_website' => 'sometimes|url|max:255',
            'partner_status' => 'sometimes|in:active,inactive,suspended',
        ]);

        if ($request->hasFile('company_logo')) {
            $request->validate([
                'company_logo' => 'image|max:2048'
            ]);

            $b2 = new B2StorageService();
            $validated['company_logo'] = $b2->uploadImage($request->file('company_logo'), 'partner-logos');
        }

        $partner->update($validated);

        return response()->json($partner);
    }

    public function getSponsoredOpportunities($id)
    {
        $partner = User::where('user_type', 'partner')->findOrFail($id);
        
        $opportunities = $partner->sponsoredOpportunities()
            ->with('opportunity')
            ->when(request('status'), function($query, $status) {
                return $query->where('status', $status);
            })
            ->when(request('payment_status'), function($query, $status) {
                return $query->where('payment_status', $status);
            })
            ->latest()
            ->paginate(10);

        return response()->json($opportunities);
    }

    public function getMetrics($id)
    {
        $partner = User::where('user_type', 'partner')->findOrFail($id);
        return response()->json($partner->getPartnerMetrics());
    }

    public function getCurrentPartnerStatus()
    {
        $user = auth()->user();
        if (!($user instanceof \App\Models\User)) {
            $user = \App\Models\User::find($user->id);
        }
        Log::info('Current user: ' . ($user ? $user->id : 'null'));
        Log::info('Partner status: ' . ($user ? $user->partner_status : 'null'));
        $status = $user ? $user->partner_status : null;
        if ($status === null) {
            return response()->json(['partner_status' => 'not_applied']);
        }
        return response()->json(['partner_status' => $status]);
    }

    public function all()
    {
        $partners = User::where('user_type', 'partner')
            ->select('id', 'company_name')
            ->orderBy('company_name')
            ->get();
        return response()->json($partners);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_description' => 'required|string',
            'company_website' => 'required|url|max:255',
            'email' => 'required|email|unique:users,email',
            'company_logo' => 'nullable|image|max:2048',
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
        ]);

        $data['user_type'] = 'partner';
        $data['partner_status'] = 'inactive';
        $data['status'] = 'inactive';
        $data['partner_since'] = now();
        $data['password'] = $data['password'] ?? bcrypt(str()->random(10));
        $data['name'] = $data['name'] ?? $data['company_name'];

        if ($request->hasFile('company_logo')) {
            $b2 = new \App\Services\B2StorageService();
            $data['company_logo'] = $b2->uploadImage($request->file('company_logo'), 'partner-logos');
        }

        $partner = \App\Models\User::create($data);
        return response()->json($partner, 201);
    }

    public function applyAsPartner(Request $request)
    {
        $user = auth()->user();
        if (!($user instanceof \App\Models\User)) {
            $user = \App\Models\User::find($user->id);
        }
        $data = $request->validate([
            'company_name' => 'required|string|max:255',
            'company_description' => 'required|string',
            'company_website' => 'required|url|max:255',
            'company_logo' => 'nullable|image|max:2048',
        ]);

        $updateData = [
            'user_type' => 'partner',
            'partner_status' => 'inactive',
            'status' => 'inactive',
            'partner_since' => now(),
            'company_name' => $data['company_name'],
            'company_description' => $data['company_description'],
            'company_website' => $data['company_website'],
        ];

        if ($request->hasFile('company_logo')) {
            $b2 = new \App\Services\B2StorageService();
            $updateData['company_logo'] = $b2->uploadImage($request->file('company_logo'), 'partner-logos');
        }

        $user->update($updateData);
        return response()->json($user->fresh(), 200);
    }
} 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 