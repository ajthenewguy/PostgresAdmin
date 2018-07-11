<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\Paginator;

class QueryController extends AdminController
{

    private $collection;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function beforeQuery(Request $request)
    {
        return $this->setConnection($request->database);
    }

    public function tables(Request $request)
    {
        if ($request->schema) {
            $this->setSchema($request->schema, false);
        }
        $this->collection = $this->setConnection($request->database);
        return response()->json($this->collection);
    }

    /**
     * Run a select statement
     *
     * @return \Illuminate\Http\Response
     */
    public function select(Request $request)
    {
        $perPage = $request->per_page ?: 25;
        $sql = $request->sql;

        $this->beforeQuery($request);

        if ($bindings = $request->bindings) {
            $this->collection = collect(DB::select($sql), $bindings);
        } else {
            $this->collection = collect(DB::select($sql));
        }

        if ($pluck = $request->pluck) {
            $this->collection = $this->collection->pluck($pluck);
        }

        if ($page = $request->page) {
            $pagination = $this->paginate($this->collection, $perPage, $page);
            return response()->json($pagination);
        }

        return response()->json($this->collection);
    }

    /**
     * Run an insert statement
     *
     * @return \Illuminate\Http\Response
     */
    public function insert(Request $request)
    {
        $this->beforeQuery($request);

        if ($bindings = $request->bindings) {
            $result = DB::insert($request->sql, $bindings);
        } else {
            $result = DB::insert($request->sql);
        }

        return response()->json($result);
    }

    /**
     * Run an update statement
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $this->beforeQuery($request);

        if ($bindings = $request->bindings) {
//            if (false !== strpos($sql, '?')) {
//                $bindings = array_values($bindings);
//            }
            $affected = DB::update($request->sql, $bindings);
        } else {
            $affected = DB::update($request->sql);
        }

        return response()->json($affected);
    }

    /**
     * Run a delete statement
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $this->beforeQuery($request);

        if ($bindings = $request->bindings) {
            $deleted = DB::delete($request->sql, $bindings);
        } else {
            $deleted = DB::delete($request->sql);
        }

        return response()->json($deleted);
    }

    /**
     * Run a statement
     *
     * @return \Illuminate\Http\Response
     */
    public function execute(Request $request)
    {
        $this->beforeQuery($request);
        if (is_array($request->sql)) {
            $result = [];
            DB::beginTransaction();
            try {
                foreach ($request->sql as $sql) {
                    $result[] = DB::statement($sql);
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollback();
                throw $e;
            }
        } else {
            $result = DB::statement($request->sql);
        }
        return response()->json($result);
    }

    /**
     * Get a table schema and indices
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function schema(Request $request)
    {
        $results = [];
        $queries = [
            // columns
            'SELECT
                column_name,
                data_type AS type,
                character_maximum_length AS length,
                column_default AS default_value,
                is_nullable AS nullable,
                is_updatable AS mutable
            FROM
                information_schema.columns
            WHERE
                table_name = \''.$request->table.'\'',

            // primary keys
            'SELECT
                pg_attribute.attname,
                pg_attribute.attlen,
                format_type(pg_attribute.atttypid, pg_attribute.atttypmod)
            FROM
                pg_index, pg_class, pg_attribute, pg_namespace
            WHERE
                pg_class.oid = \''.$request->table.'\'::regclass
                AND indrelid = pg_class.oid
                AND nspname = \''.$this->schema.'\'
                AND pg_class.relnamespace = pg_namespace.oid
                AND pg_attribute.attrelid = pg_class.oid
                AND pg_attribute.attnum = any(pg_index.indkey)
                AND indisprimary',

            // foreign keys
            'SELECT
                tc.constraint_name,
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name
            FROM
                information_schema.table_constraints AS tc
            JOIN
                information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
            JOIN
                information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
            WHERE
                constraint_type = \'FOREIGN KEY\'
                AND tc.table_name=\''.$request->table.'\'',

			// indexes
			'SELECT * FROM pg_indexes WHERE tablename = \''.$request->table.'\''
        ];

        $this->beforeQuery($request);
        foreach ($queries as $sql) {
            $results[] = collect(DB::select($sql));
        }
        return response()->json($results);
    }

    /**
     * Paginate a laravel colletion or array of items.
     *
     * @param  array|Illuminate\Support\Collection $items   array to paginate
     * @param  int $perPage number of pages
     * @return Illuminate\Pagination\LengthAwarePaginator    new LengthAwarePaginator instance
     */
    protected function paginate($items, $perPage, $page = 1)
    {
        if (is_array($items)) {
            $items = collect($items);
        }

        if (! $page) {
            $page = Paginator::resolveCurrentPage();
        }

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(), $perPage,
            Paginator::resolveCurrentPage(),
            ['path' => Paginator::resolveCurrentPath()]
        );
    }
}
