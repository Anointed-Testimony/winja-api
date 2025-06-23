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
        try {
            if (!auth()->check()) {
                return response()->json([
                    'status' => 'not_applied',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'not_applied',
                    'message' => 'User not found'
                ], 404);
            }

            $status = $user->partner_status ?? 'not_applied';
            
            return response()->json([
                'status' => $status,
                'message' => 'Status retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Partner status error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve partner status'
            ], 500);
        }
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
            'business_registration_number' => 'required|string|max:255',
            'tax_identification_number' => 'required|string|max:255',
            'business_address' => 'required|string|max:255',
            'contact_person_name' => 'required|string|max:255',
            'contact_person_position' => 'required|string|max:255',
            'contact_person_phone' => 'required|string|max:255',
            'verification_documents' => 'required|array',
            'verification_documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $updateData = [
            'user_type' => 'partner',
            'partner_status' => 'inactive',
            'status' => 'inactive',
            'partner_since' => now(),
            'company_name' => $data['company_name'],
            'company_description' => $data['company_description'],
            'company_website' => $data['company_website'],
            'business_registration_number' => $data['business_registration_number'],
            'tax_identification_number' => $data['tax_identification_number'],
            'business_address' => $data['business_address'],
            'contact_person_name' => $data['contact_person_name'],
            'contact_person_position' => $data['contact_person_position'],
            'contact_person_phone' => $data['contact_person_phone'],
        ];

        // Handle company logo
        if ($request->hasFile('company_logo')) {
            $b2 = new \App\Services\B2StorageService();
            $updateData['company_logo'] = $b2->uploadImage($request->file('company_logo'), 'partner-logos');
        }

        // Handle verification documents
        $documentUrls = [];
        if ($request->hasFile('verification_documents')) {
            $b2 = new \App\Services\B2StorageService();
            foreach ($request->file('verification_documents') as $document) {
                $documentUrls[] = $b2->uploadImage($document, 'partner-documents');
            }
            $updateData['verification_documents'] = json_encode($documentUrls);
        }

        $user->update($updateData);
        return response()->json($user->fresh(), 200);
    }

    public function verifyPartner(Request $request, $id)
    {
        $this->authorize('verify-partners');
        
        $partner = User::where('user_type', 'partner')->findOrFail($id);
        
        $data = $request->validate([
            'partner_status' => 'required|in:active,inactive,suspended',
            'verification_notes' => 'nullable|string',
        ]);

        $data['verified_at'] = now();
        $data['verified_by'] = auth()->id();

        $partner->update($data);

        // Send notification to partner
        // TODO: Implement notification system

        return response()->json($partner->fresh());
    }
} 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 
 