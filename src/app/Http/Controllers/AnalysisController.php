<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;

class AnalysisController extends Controller
{
    public function index()
    {
        $startDate = '2024-04-10';
        $endDate = '2024-05-17';

        $period = Order::betweenDate($startDate, $endDate)
        ->groupBy('id')
        ->selectRaw('id, sum(subtotal) as total, customer_name, status, created_at')
        ->orderBy('created_at')
        ->paginate(50);

        return Inertia::render('Analysis');
    }
}
