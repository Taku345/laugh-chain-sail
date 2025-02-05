<?php

namespace Tests\Feature;

use App\Services\AccountService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use SymbolSdk\CryptoTypes\PrivateKey;
class AccountServiceTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_send_user_credential_mosaic_to_new_address()
    {
        // $this->expectException(Exception::class);

        $newAccount = app('symbol.config')['facade']->createAccount(PrivateKey::random());

        $result = AccountService::sendUserCredentialMosaic($newAccount->address);
        echo "txHash = {$result}";

        $this->assertNotNull($result, '返り値がnullです');
        $this->assertInstanceOf('SymbolSdk\CryptoTypes\Hash256', $result, 'Transaction hash should be an instance of Hash256');
    }
}
