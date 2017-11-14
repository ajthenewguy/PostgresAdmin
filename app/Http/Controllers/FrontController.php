<?php

namespace App\Http\Controllers;

use DB;
use Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;

class FrontController extends Controller
{
    /**
     * @var array $tables
     */
    protected $tables = [];

    public function index(Request $request, $database = null)
    {
//        Setting::set('connection', null);
        $connectionName = $this->getConnection($database);
        $data = [
            'connections' => collect(Setting::get('connections')),
            'selectedDatabase' => $connectionName
        ];

        if (Auth::check()) {
            try {
                $data['tables'] = $this->setConnection($connectionName);
            } catch (\Throwable $e) {
                Setting::set('connection', null);
                return redirect('/');
            }
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
        } elseif (Setting::get('connection') !== $connectionName) {
            Setting::set('connection', $connectionName);
        }
        foreach ($connections as $connection) {
            if ($connection['name'] === $connectionName) {
                if (DB::connection()->getDatabaseName() !== $connection['database']) {
                    DB::purge('dynamic');
                    Config::set('database.connections.dynamic.host', $connection['host']);
                    Config::set('database.connections.dynamic.database', $connection['database']);
                    Config::set('database.connections.dynamic.username', $connection['username']);
                    Config::set('database.connections.dynamic.password', $connection['password']);
                    Config::set('database.default', 'dynamic');
                    DB::reconnect('dynamic');
                    Schema::connection('dynamic')->getConnection()->reconnect();
                }
                $tables = $this->getTables($connection['database']);
            }
        }
        return $tables;
    }

    protected function getTables($database = null, $bustcache = false)
    {
        $sql = 'SELECT * FROM pg_catalog.pg_tables WHERE schemaname = \'public\' ORDER BY tablename';
        if (is_null($database)) {
            $database = DB::connection()->getDatabaseName();
        }
        if (! isset($this->tables[$database]) && ! $bustcache) {
            $this->tables[$database] = collect(DB::select($sql))->pluck('tablename');
        }
        return $this->tables[$database];
    }
}
