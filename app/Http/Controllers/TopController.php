<?php

namespace App\Http\Controllers;

use App\Services\AccountService;
use App\Services\NFTService;
use Illuminate\Http\Request;
use App\Services\StoryService;
use Illuminate\Support\Facades\Log;
use SymbolSdk\CryptoTypes\PrivateKey;

class TopController extends Controller
{
    public function toppage()
    {
        $symbol = app('symbol.config');
        $accountNFTs = AccountService::getAccountMosaics($symbol['testUserAccount']->address); //とりあえずNFTに限らず全モザイクを取得してます


        //テスト用、ユーザーにクレデンシャルモザイクを送る
        $testUserAccount = $symbol['testUserAccount'];
        $txHash = AccountService::sendUserCredentialMosaic($testUserAccount->address);
        Log::debug("sendUserCredentialMosaic done");
        Log::debug(strval($txHash));
        //テスト用ここまで

        //テスト用、ユーザーにNFTモザイクを送る
        // $testUserAccount = $symbol['testUserAccount'];
        // $txHash = NFTService::mintNFT("NFT送信テスト2だよ", $testUserAccount->address);
        // Log::debug("mintNFT done");
        // Log::debug(strval($txHash));
        //テスト用ここまで

        return view('top', [
            'accountNFTs' => $accountNFTs,
        ]);
        // return view('top', StoryService::allOfficialAccountMosaics());
    }
}
