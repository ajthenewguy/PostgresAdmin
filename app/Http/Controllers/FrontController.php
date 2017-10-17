<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class FrontController extends Controller
{
    public function index(Request $request)
    {
        $data = [
            'selectedDatabase' => $this->getSelectedDatabase($request)
        ];

        if (Auth::check()) {
            $data['tables'] = $this->selectDatabase($request, $data['selectedDatabase']);
        } else {
            return redirect('/login');
        }

        return view('app', $data);
    }

    protected function getSelectedDatabase(Request $request)
    {
        if (! $request->session()->get('selectedDatabase')) {
            $request->session()->put('selectedDatabase', env('DB_CONNECTION'));
        }
        return $request->session()->get('selectedDatabase');
    }

    protected function selectDatabase(Request $request, $database = null)
    {
        if (is_null($database)) {
            $database = $this->getSelectedDatabase($request);
        }

        if (in_array($database, [ 'stars10', 'team20' ])) {
            $request->session()->put('selectedDatabase', $database);
            Config::set('database.default', $database);
            DB::reconnect($database);
        }

        return $this->getTables();
    }

    protected function getTables()
    {
        $sql = 'SELECT * FROM pg_catalog.pg_tables WHERE schemaname = \'public\' ORDER BY tablename';
        return collect(DB::select($sql))->pluck('tablename');
    }
}
