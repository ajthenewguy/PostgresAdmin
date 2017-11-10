<?php

namespace App\Http\Controllers;

use DB;
use Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class FrontController extends Controller
{
    public function index(Request $request, $database = null)
    {
//        Setting::set('connection', null);
        $connectionName = $this->getConnection($database);
        $data = [
            'connections' => collect(Setting::get('connections')),
            'selectedDatabase' => $connectionName
        ];

        if (Auth::check()) {
            $data['tables'] = $this->setConnection($connectionName);
        } else {
            return redirect('/login');
        }

        return view('app', $data);
    }

    protected function getConnection($connectionName = null)
    {
        if (is_null($connectionName)) {
            $connectionName = Setting::get('connection');
        } else {
            Setting::set('connection', $connectionName);
        }
        if (! Setting::get('connection')) {
            Setting::set('connection', $connectionName);
        }
        return Setting::get('connection');
    }

    protected function setConnection($connectionName = null)
    {
        $tables = [];
        $connections = Setting::get('connections');
        if (is_null($connectionName)) {
            $connectionName = $this->getConnection();
        }
        foreach ($connections as $connection) {
            if ($connection['name'] === $connectionName) {
                Setting::set('connection', $connectionName);
                Config::set('database.default', $connection['database']);
                DB::reconnect($connection['database']);
                $tables = $this->getTables();
            }
        }
        return $tables;
    }

    protected function getTables()
    {
        $sql = 'SELECT * FROM pg_catalog.pg_tables WHERE schemaname = \'public\' ORDER BY tablename';
        return collect(DB::select($sql))->pluck('tablename');
    }
}
