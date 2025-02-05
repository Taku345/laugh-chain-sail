<?php

namespace App\Http\Controllers;

use App\Services\AccountService;
use App\Services\NFTService;
use Illuminate\Http\Request;
use App\Services\StoryService;
use Illuminate\Support\Facades\Log;
use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
class TopController extends Controller
{
    public function toppage()
    {
        $symbol = app('symbol.config');
        $testUserAccount = $symbol['testUserAccount'];

        $accountMosaics = AccountService::getAccountMosaics($symbol['testUserAccount']->address); //とりあえずNFTに限らず全モザイクを取得してます


        // テスト用、ユーザーにクレデンシャルモザイクを送る
        $txHash = AccountService::sendUserCredentialMosaic(new UnresolvedAddress("TACGNHY75AELDXIM74CLVCHWZLF7YYBSEVT7RSI"));
        Log::debug("sendUserCredentialMosaic done");
        Log::debug($txHash);
        // テスト用ここまで

        // テスト用、ユーザーにNFTモザイクを送る
        // $txHash = NFTService::mintNFT("NFT送信テスト3だよ", $testUserAccount->address);
        // Log::debug("mintNFT done");
        // Log::debug($txHash);
        // テスト用ここまで

        return view('top', [
            'accountNFTs' => $accountMosaics,
        ]);
        // return view('top', StoryService::allOfficialAccountMosaics());
    }
}
