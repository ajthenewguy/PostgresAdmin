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
     * @var string $schema
     */
    protected $schema = 'public';

    /**
     * @var array $tables
     */
    protected $tables = [];

    /**
     * @var bool $schemaChanged
     */
    private $schemaChanged;

	/**
	 */
	public function index(Request $request, $database = null)
    {
        $redirects = session('redirects', 0);
        $connectionName = $this->getConnection($database);
        $data = [
            'connections' => collect(Setting::get('connections')),
            'selectedDatabase' => $connectionName
        ];

        if (Auth::check()) {
            try {
                $data['tables'] = $this->setConnection($connectionName);
            } catch (\Throwable $e) {
                Setting::set('connection', $this->getDefaultConnection());
                if ($redirects < 3) {
                    session(['redirects' => ++$redirects]);
                    return redirect('/');
                }
            }
        } else {
            return redirect('/login');
        }

        return view('app', $data);
    }

    public function getDefaultConnection()
    {
        return env('DB_CONNECTION', 'users');
    }

	/**
	 */
	public function token()
	{
		return response()->json(csrf_token());
	}

	/**
	 */
    protected function getConnection($connectionName = null)
    {
        if (is_null($connectionName)) {
            if (Setting::has('connection')) {
                $connectionName = Setting::get('connection');
            } else {
                $connectionName = $this->getDefaultConnection();
                Setting::set('connection', $connectionName);
            }
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
        if ($connections) {
            foreach ($connections as $connection) {
                if ($connection['name'] === $connectionName) {
                    if (DB::connection()->getDatabaseName() !== $connection['database'] || $this->schemaChanged) {
                        DB::purge('dynamic');
                        Config::set('database.connections.dynamic.host', $connection['host']);
                        Config::set('database.connections.dynamic.database', $connection['database']);
                        Config::set('database.connections.dynamic.username', $connection['username']);
                        Config::set('database.connections.dynamic.password', $connection['password']);
                        Config::set('database.connections.dynamic.schema', $this->schema);
                        Config::set('database.default', 'dynamic');
                        DB::reconnect('dynamic');
                        Schema::connection('dynamic')->getConnection()->reconnect();
                        $this->schemaChanged = false;
                    }
                    $tables = $this->getTables($connection['database']);
                }
            }
        }
        return $tables;
    }

    /**
     * @param $schema
     * @return $this
     */
    protected function setSchema($schema, $reconnect = true)
    {
        if ($schema && $schema !== $this->schema) {
            $this->schema = $schema;
            if ($reconnect) {
                DB::purge('dynamic');
                Config::set('database.connections.dynamic.schema', $this->schema);
                Config::set('database.default', 'dynamic');
                DB::reconnect('dynamic');
                Schema::connection('dynamic')->getConnection()->reconnect();
            } else {
                $this->schemaChanged = true;
            }
        }
        return $this;
    }

    protected function getTables($database = null, $bustcache = false)
    {
        $sql = 'SELECT * FROM pg_catalog.pg_tables WHERE schemaname = \''.$this->schema.'\' ORDER BY tablename';
        if (is_null($database)) {
            $database = DB::connection()->getDatabaseName();
        }
        if (! isset($this->tables[$database]) && ! $bustcache) {
            $this->tables[$database] = collect(DB::select($sql))->pluck('tablename');
        }
        return $this->tables[$database];
    }
}
