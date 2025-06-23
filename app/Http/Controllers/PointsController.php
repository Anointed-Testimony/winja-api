<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PointsTransaction;
use App\Services\PointsService;
use Illuminate\Http\Request;

class PointsController extends Controller
{
    protected $pointsService;

    public function __construct(PointsService $pointsService)
    {
        $this->pointsService = $pointsService;
    }

    /**
     * Get user points balance and earnings
     */
    public function getBalance()
    {
        $user = auth()->user();
        
        $balance = $this->pointsService->getUserPointsSummary($user->id);
        
        return response()->json($balance);
    }

    /**
     * Get user points transaction history
     */
    public function getTransactions(Request $request)
    {
        $user = auth()->user();
        
        $query = PointsTransaction::where('user_id', $user->id);
        
        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return response()->json($transactions);
    }
} 