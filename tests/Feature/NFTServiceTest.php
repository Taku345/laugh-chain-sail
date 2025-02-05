<?php

namespace Tests\Feature;

use App\Services\NFTService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolSdk\Symbol\Address;

class NFTServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // テストに必要な初期設定があればここで行う
    }

    /**
     * getAccountLaughChainNFTsのテスト - 存在しないアドレスの場合
     */
    // public function test_get_account_laugh_chain_nfts_with_non_existent_address()
    // {
    //     $nonExistentAddress = 'TDZBCWHAVA62R4JFZJJUXQWXLC6N3VS3BGVHZ2Q'; // テスト用の存在しないアドレス
    //     $result = NFTService::getAccountLaughChainNFTs($nonExistentAddress);

    //     $this->assertNull($result, 'Non-existent address should return null');
    // }

    // /**
    //  * getAccountLaughChainNFTsのテスト - 有効なアドレスの場合
    //  */
    // public function test_get_account_laugh_chain_nfts_with_valid_address()
    // {
    //     // Note: このテストを実行する前に、このアドレスが実際にネットワーク上に存在することを確認してください
    //     $validAddress = config('symbol.official_address');
    //     $result = NFTService::getAccountLaughChainNFTs($validAddress);

    //         $this->assertIsArray($result, 'Valid address should return an array');
    //     }

    /**
     * mintNFTのテスト - 正常系
     */
    public function test_mint_nft_success()
    {
        $symbol = app('symbol.config');
        $storyAddress = 'https://example.com/story/1';
        $accountAddress = new UnresolvedAddress($symbol['testUserAccount']->address); // テスト用アドレス

        $result = NFTService::mintNFT($storyAddress, $accountAddress);

        $this->assertNotNull($result, 'Minting NFT should return a transaction hash');
        $this->assertInstanceOf('SymbolSdk\CryptoTypes\Hash256', $result, 'Transaction hash should be an instance of Hash256');
    }

    /**
     * mintNFTのテスト - 異常系（無効なアドレス）
     */
    public function test_mint_nft_with_invalid_address()
    {
        $this->expectException(Exception::class);

        $storyAddress = 'https://example.com/story/1';
        $invalidAddress = new UnresolvedAddress('invalid_address');

        NFTService::mintNFT($storyAddress, $invalidAddress);
    }
}
