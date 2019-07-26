<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\FrontEnd\Bank;
use App\Models\FrontEnd\FrontSetting;
use Config;
use App\Models\FrontEnd\Game;
use App\Models\FrontEnd\Market;
use App\Models\FrontEnd\MarketGameSetting;
use Auth;

class FrontEndController extends Controller {

    public function getBank() {
        $getBankList = Bank::getAllRecord(0, 1)->get();
        return response($getBankList->jsonSerialize());
    }

    protected function guard() {
        return Auth::guard('api');
    }

    public function getSetting() {
        $getGeneralSetting = FrontSetting::getGeneralSetting();
        $getArrayCurrency = Config('sysconfig.currency_code');
        $getLocaleCode = $getArrayCurrency[$getGeneralSetting->currency];
        $getSymbol = isset($getLocaleCode['symbol']) ? $getLocaleCode['symbol'] : '';
        $setGeneralSetting = [
            'currency' => $getGeneralSetting->currency,
            'logo' => $getGeneralSetting->logo,
            'favicon' => $getGeneralSetting->icon,
            'timezone' => $getGeneralSetting->timezone,
            'symbol' => $getSymbol
        ];
        //\Log::info($setGeneralSetting);
        $market = Market::getAllRecord(0, 1)->get();
        $getBankList = Bank::getAllRecord(0, 1)->get();
        $getGameList = Game::getAllRecord(0)->get();

        return response()->json(['general' => $setGeneralSetting, 'market' => $market->jsonSerialize(), 'bank' => $getBankList->jsonSerialize(), 'gameitem' => $getGameList->jsonSerialize()], 200);
    }
    public function getMarketGameSetting(Request $request){
         $market = $request->input('market');
         $game = $request->input('game');
         $getMarketGameSetting = MarketGameSetting::where('market', $market)->where('game_name', $game)->first();
         return response()->json($getMarketGameSetting->jsonSerialize());
    }
}
