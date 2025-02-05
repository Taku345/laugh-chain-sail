<?php

namespace App\Services;

use Exception;
use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Models\PublicKey;
use Illuminate\Support\Facades\Log;
use SymbolSdk\Symbol\Models\MosaicFlags;
use SymbolSdk\Symbol\IdGenerator;
use SymbolSdk\Symbol\Models\NetworkType;

use SymbolSdk\Symbol\Models\MosaicSupplyRevocationTransactionV1;
use SymbolSdk\Symbol\Models\TransferTransactionV1;
use SymbolSdk\Symbol\Models\MosaicNonce;
use SymbolSdk\Symbol\Models\BlockDuration;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Models\UnresolvedMosaic;
use SymbolSdk\Symbol\Models\MosaicSupplyChangeAction;
use SymbolSdk\Symbol\Models\Amount;
use SymbolSdk\Symbol\Models\EmbeddedMosaicDefinitionTransactionV1;
use SymbolSdk\Symbol\Models\EmbeddedMosaicSupplyChangeTransactionV1;
use SymbolSdk\Symbol\Models\EmbeddedTransferTransactionV1;
use SymbolSdk\Symbol\Models\MosaicId;
use SymbolSdk\Symbol\Models\AggregateCompleteTransactionV2;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolRestClient\Configuration;
use SymbolRestClient\Api\TransactionRoutesApi;
use SymbolRestClient\Api\TransactionStatusRoutesApi;
use SymbolRestClient\Api\AccountRoutesApi;
use SymbolRestClient\Api\MosaicRoutesApi;
use SymbolSdk\Symbol\Address;

/**
 * NFT関連のサービスクラス
 */
class NFTService
{
    /**
     * 指定アドレスアカウントのNFT一覧を取得
     * 存在しない場合は null, NFT がない場合は空配列を返す
     *
     * @param string $addressStr
     * @return array|null
     */
    public static function getAccountLaughChainNFTs(String $addressStr)
    {
        // ServiceProviderからsymbol操作用クラスを取得
        $symbol = app('symbol.config');
        $facade = $symbol['facade'];
        $accountRoutesApi = $symbol['accountRoutesApi'];
        $mosaicRoutesApi = $symbol['mosaicRoutesApi'];

        try {
            $accountInfo = $accountRoutesApi->getAccountInfo($addressStr);
        } catch (\Exception $e) {
            Log::error($e);
            return null; // アカウントが存在しない場合はnullを返すのでこれで判別してください
        }

        $accountNFTs = [];
        foreach($accountInfo->getAccount()->getMosaics() as $mosaic) {

            //getMosaics()で得られるモザイク情報はid, amountしかないため詳細情報を取得
            $mosaicInfo = $mosaicRoutesApi->getMosaic($mosaic->getId());

            if (true) { // TODO:LaughChainのNFTのみを取得する条件を追加
                throw new Exception('まだ未完成の関数だよ');
                $accountNFTs[] = $mosaicInfo;
            }
        }
        return $accountNFTs;
    }

    /**
     * @param $StoryAddress URL or Symbolアドレス、Unsolvedかどうかわかんないので両方入れてる
     * @param $AccountAddress Accountクラスに保持してあるアドレスはUnsolvedAddress
     *
     * @return Hash256 トランザクションハッシュ
     */
    public static function mintNFT(
        Address|UnresolvedAddress|String $StoryAddress,
        UnresolvedAddress $AccountAddress
        ){

        // ServiceProviderからsymbol操作用クラスを取得
        $symbol = app('symbol.config');
        $facade = $symbol['facade'];
        $transactionRoutesApi = $symbol['transactionRoutesApi'];
        $officialAccount = $symbol['officialAccount'];

        $f = MosaicFlags::NONE;
        // $f += MosaicFlags::SUPPLY_MUTABLE; // 供給量変更可能
        $f += MosaicFlags::TRANSFERABLE; // 第三者への譲渡可否
        // $f += MosaicFlags::RESTRICTABLE; //制限設定の可否
        // $f += MosaicFlags::REVOKABLE; //発行者からの還収可否
        $flags = new MosaicFlags($f);

        $mosaicId = IdGenerator::generateMosaicId($officialAccount->address);

        // モザイク定義
        $mosaicDefTx = new EmbeddedMosaicDefinitionTransactionV1(
            network: new NetworkType(NetworkType::TESTNET),
            signerPublicKey: $officialAccount->publicKey, // 署名者公開鍵
            id: new MosaicId($mosaicId['id']), // モザイクID
            divisibility: 0, // 分割可能性
            duration: new BlockDuration(0), //duration:有効期限
            nonce: new MosaicNonce($mosaicId['nonce']),
            flags: $flags,
        );

        // モザイク変更
        $mosaicChangeTx = new EmbeddedMosaicSupplyChangeTransactionV1(
            network: new NetworkType(NetworkType::TESTNET),
            signerPublicKey: $officialAccount->publicKey, // 署名者公開鍵
            mosaicId: new UnresolvedMosaicId($mosaicId['id']),
            delta: new Amount(1),
            action: new MosaicSupplyChangeAction(MosaicSupplyChangeAction::INCREASE),
        );

        //NFTデータ
        $nftTx = new EmbeddedTransferTransactionV1(
            network: new NetworkType(NetworkType::TESTNET),
            signerPublicKey: $officialAccount->publicKey,  // 署名者公開鍵
            recipientAddress: new UnresolvedAddress($AccountAddress),  // 受信者アドレス
            message: "\0$StoryAddress", //NFTデータ実態
        );

        // マークルハッシュの算出
        $embeddedTransactions = [$mosaicDefTx, $mosaicChangeTx, $nftTx];
        $merkleHash = $facade->hashEmbeddedTransactions($embeddedTransactions);

        // モザイクの生成とNFTデータをアグリゲートしてブロックに登録
        $aggregateTx = new AggregateCompleteTransactionV2(
            network: new NetworkType(NetworkType::TESTNET),
            signerPublicKey: $officialAccount->publicKey,
            deadline: new Timestamp($facade->now()->addHours(2)),
            transactionsHash: $merkleHash,
            transactions: $embeddedTransactions
        );
        $facade->setMaxFee($aggregateTx, 100);  // 手数料

        // 署名
        $sig = $officialAccount->signTransaction($aggregateTx);
        $payload = $facade->attachSignature($aggregateTx, $sig);

        /**
         * アナウンス
         */
        try {
            $result = $transactionRoutesApi->announceTransaction($payload);
            echo $result . PHP_EOL;
        } catch (Exception $e) {
            echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
        }
        return $facade->hashTransaction($aggregateTx);
    }
}


