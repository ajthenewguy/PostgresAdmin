<?php

namespace App\Http\Controllers;

use Setting;
use Illuminate\Http\Request;

class SettingsController extends AdminController
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
                    if ($value = $this->getSetting($key)) {
                        $values[$key] = $value;
                    }
                }
                if (! empty($values)) {
                    return response()->json($values);
                } else {
                    abort(404);
                }
            } else {
                if ($value = $this->getSetting($request->key)) {
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
        if ($request->has('key') && $request->has('value')) {
            Setting::set($request->key, $request->value);
            return response('ok', 200);
        }
        abort(400);
    }

    private function getSetting($key)
    {
        if (Setting::has($key)) {
            if ($value = Setting::get($key)) {
                return $value;
            } else {
                return false;
            }
        }
    }
}
