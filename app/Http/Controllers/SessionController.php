<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionController extends AdminController
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

    public function get(Request $request)
    {
        if ($request->has('key')) {
            if (is_array($request->key)) {
                $values = [];
                foreach ($request->key as $key) {
                    if ($value = $this->getKey($request, $key)) {
                        $values[$key] = $value;
                    }
                }
                if (! empty($values)) {
                    return response()->json($values);
                } else {
                    abort(404);
                }
            } else {
                if ($value = $this->getKey($request, $request->key)) {
                    return response()->json($value);
                } else {
                    abort(404);
                }
            }
            abort(404);
        }
        abort(400);
    }

    public function set(Request $request)
    {
        if (is_array($request->data)) {
            foreach ($request->data as $key => $value) {
                $request->session()->put($key, $value);
            }
            return response('ok', 200);
        } else {
            if ($request->has('key') && $request->has('value')) {
                $request->session()->put($request->key, $request->value);
                return response('ok', 200);
            } else {
                abort(400);
            }
        }
    }

    private function getKey(Request $request, $key)
    {
        if ($request->session()->has($key)) {
            if ($value = $request->session()->get($key)) {
                return $value;
            } else {
                return false;
            }
        }
    }
}
