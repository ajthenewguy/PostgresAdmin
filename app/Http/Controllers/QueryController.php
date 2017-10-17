<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Pagination\Paginator;

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
        return $this->selectDatabase($request);
    }

    /**
     * Run a select statement
     *
     * @return \Illuminate\Http\Response
     */
    public function select(Request $request)
    {
        $perPage = $request->perPage ?: 30;
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
        $sql = $request->sql;

        $this->beforeQuery($request);

        if ($bindings = $request->bindings) {
            $result = DB::insert($sql, $bindings);
        } else {
            $result = DB::insert($sql);
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
        $sql = $request->sql;

        $this->beforeQuery($request);

        if ($bindings = $request->bindings) {
//            if (false !== strpos($sql, '?')) {
//                $bindings = array_values($bindings);
//            }
            $affected = DB::update($sql, $bindings);
        } else {
            $affected = DB::update($sql);
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
        $sql = $request->sql;

        $this->beforeQuery($request);

        if ($bindings = $request->bindings) {
            $deleted = DB::delete($sql, $bindings);
        } else {
            $deleted = DB::delete($sql);
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
        $result = DB::statement($request->sql);
        return response()->json($result);
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
