<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Router dashboard — menampilkan view sesuai role user
     * (docs/feature/dashboard.md).
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.'.$user->role, [
            'user' => $user,
        ]);
    }
}
