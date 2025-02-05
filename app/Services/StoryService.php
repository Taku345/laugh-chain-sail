<?php

namespace App\Services;

use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Models\PublicKey;
use Illuminate\Support\Facades\Log;


class StoryService
{
    public static function allOfficialAccountMosaics()
    {
        // global $facade, $accountRoutesApi, $mosaicRoutesApi;
        $symbol = app('symbol.config');
        $facade = $symbol['facade'];
        $accountRoutesApi = $symbol['accountRoutesApi'];
        $mosaicRoutesApi = $symbol['mosaicRoutesApi'];
        Log::debug( "cccc".print_r($facade, true));

        $officialAccount = $facade->createAccount(new PrivateKey(env('OFFICIAL_ACCOUNT_PRIVATE_KEY')));
        $accountInfo = $accountRoutesApi->getAccountInfo($officialAccount->address);
        $allOfficialAccountMosaicsAry = [];
        foreach($accountInfo->getAccount()->getMosaics() as $mosaic) {
            $mosaicInfo = $mosaicRoutesApi->getMosaic($mosaic->getId());
            $allOfficialAccountMosaicsAry[] = $mosaicInfo;
            // echo "\n===モザイク情報===" . PHP_EOL;
            // var_dump($mosaicInfo);
        }
        return $allOfficialAccountMosaicsAry;
    }
}

// var_dump(StoryService::allOfficialAccountMosaics());
