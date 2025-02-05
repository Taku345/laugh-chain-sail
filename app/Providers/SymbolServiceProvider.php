<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SymbolRestClient\Api\NodeRoutesApi;
use SymbolRestClient\Api\NetworkRoutesApi;
use SymbolRestClient\Configuration;
use SymbolSdk\Facade\SymbolFacade;
use SymbolRestClient\Api\TransactionRoutesApi;
use SymbolRestClient\Api\AccountRoutesApi;
use SymbolRestClient\Api\MosaicRoutesApi;
use GuzzleHttp\Client;
use SymbolSdk\CryptoTypes\PrivateKey;


class SymbolServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('symbol.config', function () {
            $NODE_URL = env('NODE_URL');

            $config = new Configuration();
            $config->setHost($NODE_URL);
            $client = new Client();

            // ! 使用テストノード http://sym-test-03.opening-line.jp:3000 固定前提
            if(env('NODE_URL') == "http://sym-test-03.opening-line.jp:3000"){
                $facade = new SymbolFacade('testnet'); //NetworkType::TEST_NET使えなかったので文字列で
            }else{
                $facade = new SymbolFacade('ここなんて書くか謎');
            }

            // 設定とAPIインスタンスをまとめて返す
            return [
                'config' => $config,
                'client' => $client,
                'facade' => $facade,
                'transactionRoutesApi' => new TransactionRoutesApi($client, $config),
                'accountRoutesApi' => new AccountRoutesApi($client, $config),
                'mosaicRoutesApi' => new MosaicRoutesApi($client, $config),
                'nodeRoutesApi' => new NodeRoutesApi($client, $config),
                'networkRoutesApi' => new NetworkRoutesApi($client, $config),
                'officialAccount' => $facade->createAccount(new PrivateKey(env('OFFICIAL_ACCOUNT_PRIVATE_KEY'))),
                'testUserAccount' => $facade->createAccount(new PrivateKey(env('TEST_USER_ACCOUNT_PRIVATE_KEY'))),
            ];
        });
    }

    public function boot()
    {
        //
    }
}
