<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AccountService;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolSdk\Symbol\Models\Hash256;
use SymbolSdk\Symbol\Models\TransferTransactionV1;
use Mockery;

class AccountServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // symbol.configのモック設定
        $this->mockSymbolConfig();
    }

    /** @test */
    public function すでにモザイクを持っているユーザーの場合はfalseを返す()
    {
        // モックの設定
        // $this->mockHasUserCredentialMosaic(true);
        // $address = Mockery::mock(UnresolvedAddress::class);

        $symbol = app('symbol.config');
        $result = AccountService::sendUserCredentialMosaic($symbol['testUserAccount']->address);

        $this->assertFalse($result);
    }

    /** @test */
    public function モザイクを持っていないユーザーの場合は送信してハッシュを返す()
    {
        // モックの設定
        $this->mockHasUserCredentialMosaic(false);
        $expectedHash = Mockery::mock(Hash256::class);

        $address = Mockery::mock(UnresolvedAddress::class);


        $transaction = Mockery::mock(TransferTransactionV1::class);

        // トランザクション作成と送信のモック
        $this->mockTransactionCreationAndAnnouncement($transaction, $expectedHash);

        $result = AccountService::sendUserCredentialMosaic($address);

        $this->assertSame($expectedHash, $result);
    }

    private function mockSymbolConfig()
    {
        $symbolConfig = [
            'facade' => Mockery::mock(),
            'transactionRoutesApi' => Mockery::mock(),
            'accountRoutesApi' => Mockery::mock(),
            'officialAccount' => Mockery::mock(),
        ];

        $this->app->instance('symbol.config', $symbolConfig);
    }

    private function mockHasUserCredentialMosaic(bool $hasCredential)
    {
        $accountInfo = Mockery::mock();
        $account = Mockery::mock();
        $mosaic = Mockery::mock();

        $accountInfo->shouldReceive('getAccount')->andReturn($account);
        $account->shouldReceive('getMosaics')->andReturn([$mosaic]);
        $mosaic->shouldReceive('getId')->andReturn($hasCredential ? env('USER_CREDENTIAL_MOSAIC_ID') : 'different_id');

        $this->app->make('symbol.config')['accountRoutesApi']
            ->shouldReceive('getAccountInfo')
            ->andReturn($accountInfo);
    }

    private function mockTransactionCreationAndAnnouncement($transaction, $hash)
    {
        $facade = $this->app->make('symbol.config')['facade'];
        $transactionRoutesApi = $this->app->make('symbol.config')['transactionRoutesApi'];

        $facade->shouldReceive('now->addHours')->andReturn(now());
        $facade->shouldReceive('setMaxFee');
        $facade->shouldReceive('attachSignature')->andReturn('payload');
        $facade->shouldReceive('hashTransaction')->andReturn($hash);

        $transactionRoutesApi->shouldReceive('announceTransaction')->andReturn('success');
    }
}
