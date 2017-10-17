<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;

class AdminController extends FrontController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('home');
    }

    public function switchDatabase(Request $request)
    {
        $result['tables'] = $this->selectDatabase($request, $request->database);

        return response()->json($result);
    }
}
