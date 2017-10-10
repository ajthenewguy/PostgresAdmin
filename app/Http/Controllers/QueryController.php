<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use \Illuminate\Pagination\Paginator;

class QueryController extends Controller
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

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function execute(Request $request)
    {
        $perPage = $request->perPage ?: 30;
        $sql = $request->input;
        $page = $request->page;
        $this->collection = collect(DB::select($sql));

        if ($pluck = $request->pluck) {
            $this->collection = $this->collection->pluck($pluck);
        }

        $pagination = $this->paginate($this->collection, $perPage, $page);
        return response()->json($pagination);
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
