<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FrontController extends Controller
{
    public function index(Request $request)
    {
        $data = [];

        if (Auth::check()) {
            $sql = 'SELECT * FROM pg_catalog.pg_tables WHERE schemaname = \'public\' ORDER BY tablename';
            $data['tables'] = collect(DB::select($sql))->pluck('tablename');
        } else {
            return redirect('/login');
        }

        return view('app', $data);
    }
}
