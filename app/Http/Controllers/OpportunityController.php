<?php

namespace App\Http\Controllers;

use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\B2StorageService;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = Opportunity::with(['type', 'partner']);
        if ($request->has('opportunity_type_id')) {
            $query->where('opportunity_type_id', $request->opportunity_type_id);
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'sponsor' => 'nullable|string',
            'description' => 'required|string',
            'eligibility' => 'nullable|string',
            'status' => 'required|string',
            'expiry' => 'nullable|date',
            'verified' => 'boolean',
            'opportunity_type_id' => 'required|exists:opportunity_types,id',
            'image' => 'nullable|image|max:2048',
            'created_by' => 'nullable|integer',
            'partner_id' => 'nullable|exists:users,id',
            'application_link' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && !filter_var($value, FILTER_VALIDATE_URL) && !filter_var('https://' . $value, FILTER_VALIDATE_URL)) {
                    $fail('The application link must be a valid URL.');
                }
            }],
        ]);
        // Ensure sponsor is set to null if not present
        if (!array_key_exists('sponsor', $data)) {
            $data['sponsor'] = null;
        }
        if ($request->hasFile('image')) {
            $b2 = new B2StorageService();
            $data['image'] = $b2->uploadImage($request->file('image'), 'opportunities');
        }
        $opp = Opportunity::create($data);
        return response()->json($opp->load('type'), 201);
    }

    public function show($id)
    {
        $opp = Opportunity::with(['type', 'partner'])->findOrFail($id);
        return response()->json($opp);
    }

    public function update(Request $request, $id)
    {
        $opp = Opportunity::findOrFail($id);
        $data = $request->validate([
            'title' => 'sometimes|required|string',
            'sponsor' => 'nullable|string',
            'description' => 'sometimes|required|string',
            'eligibility' => 'nullable|string',
            'status' => 'sometimes|required|string',
            'expiry' => 'nullable|date',
            'verified' => 'boolean',
            'opportunity_type_id' => 'sometimes|required|exists:opportunity_types,id',
            'image' => 'nullable|image|max:2048',
            'created_by' => 'nullable|integer',
            'partner_id' => 'nullable|exists:users,id',
            'application_link' => ['nullable', function ($attribute, $value, $fail) {
                if ($value && !filter_var($value, FILTER_VALIDATE_URL) && !filter_var('https://' . $value, FILTER_VALIDATE_URL)) {
                    $fail('The application link must be a valid URL.');
                }
            }],
        ]);
        if ($request->hasFile('image')) {
            $b2 = new B2StorageService();
            $data['image'] = $b2->uploadImage($request->file('image'), 'opportunities');
        }
        $opp->update($data);
        return response()->json($opp->load('type'));
    }

    public function destroy($id)
    {
        $opp = Opportunity::findOrFail($id);
        if ($opp->image) Storage::disk('public')->delete($opp->image);
        $opp->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // Analytics summary for all opportunities
    public function analytics()
    {
        $summary = [
            'total_views' => Opportunity::sum('view_count'),
            'total_saves' => Opportunity::sum('save_count'),
            'total_clicks' => Opportunity::sum('click_count'),
            'total_applications' => Opportunity::sum('application_count'),
            'opportunities' => Opportunity::select('id', 'title', 'view_count', 'save_count', 'click_count', 'application_count')->get(),
        ];
        return response()->json($summary);
    }

    // Increment analytics counters
    public function incrementCounter($id, $type)
    {
        $opp = Opportunity::findOrFail($id);
        switch ($type) {
            case 'view':
                $opp->increment('view_count');
                break;
            case 'click':
                $opp->increment('click_count');
                break;
            case 'save':
                $opp->increment('save_count');
                break;
            case 'application':
                $opp->increment('application_count');
                break;
            default:
                return response()->json(['message' => 'Invalid type'], 400);
        }
        return response()->json(['message' => 'Counter incremented']);
    }

    // Export analytics as CSV
    public function exportCsv()
    {
        $header = ['ID', 'Title', 'Views', 'Saves', 'Clicks', 'Applications'];
        $rows = Opportunity::select('id', 'title', 'view_count', 'save_count', 'click_count', 'application_count')->get();
        $response = new StreamedResponse(function () use ($rows, $header) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $header);
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->id,
                    $row->title,
                    $row->view_count,
                    $row->save_count,
                    $row->click_count,
                    $row->application_count,
                ]);
            }
            fclose($handle);
        });
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="opportunity_analytics.csv"');
        return $response;
    }

    // Export analytics as PDF (placeholder)
    public function exportPdf()
    {
        return response()->json(['message' => 'PDF export not implemented yet.']);
    }

    // Add this method to allow partners to fetch their own opportunities
    public function mine()
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Not authenticated'], 401);
        }
        $opps = Opportunity::with(['type', 'partner'])
            ->where('partner_id', $user->id)
            ->get();
        return response()->json($opps);
    }
} 