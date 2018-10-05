<?php

namespace ESIK\Http\Controllers;

use Auth, Carbon, Input, Request, Session, Validator;
use ESIK\Models\Member;
use ESIK\Http\Controllers\{DataController, PortalController};

class AltController extends Controller
{
    public function __construct ()
    {
        $this->dataCont = new DataController;
        $this->portCont = new PortalController;
    }

    public function add ()
    {
        if (Request::isMethod('post')) {
            $validator = Validator::make(Request::all(), [
                'scopes' => "array|required|min:1"
            ]);
            if ($validator->failed()) {
                return redirect(route('welcome'))->withErrors($validator);
            }
            $selected = collect(Request::get('scopes'))->keys();
            $authorized = $selected->map(function($scope) {
                return config('services.eve.scopes')[$scope];
            });

            $authorized = $authorized->sort()->values()->implode(' ');

            $state_hash = str_random(16);
            $state = collect([
                "redirectTo" => "welcome",
                "additionalData" => collect([
                    'authorizedScopesHash' => hash('sha1', $authorized),
                    'storeRefreshToken' => Request::has('storeRefreshToken')
                ])
            ]);

            $parms = http_build_query();

            Session::put($state_hash, $state);
            $ssoUrl = config("services.eve.urls.sso.authorize")."?response_type=code&redirect_uri=" . route(config('services.eve.sso.callback')) . "&client_id=".config('services.eve.sso.id')."&state={$state_hash}&scope=".$authorized;
            return redirect($ssoUrl);
        }
        $scopes = collect(config('services.eve.scopes'))->keys();
        return view('portal.alts.add', [
            'scopes' => $scopes
        ]);
    }
}
