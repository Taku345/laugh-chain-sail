<?php
require_once(__DIR__ . '/util.php');

use SymbolSdk\Symbol\Models\MosaicSupplyRevocationTransactionV1;
use SymbolSdk\Symbol\Models\TransferTransactionV1;
use SymbolSdk\Symbol\Models\MosaicFlags;
use SymbolSdk\Symbol\Models\MosaicNonce;
use SymbolSdk\Symbol\Models\BlockDuration;
use SymbolSdk\Symbol\Models\UnresolvedMosaicId;
use SymbolSdk\Symbol\Models\UnresolvedMosaic;
use SymbolSdk\Symbol\Models\MosaicSupplyChangeAction;
use SymbolSdk\Symbol\Models\Amount;
use SymbolSdk\Symbol\IdGenerator;
use SymbolSdk\Symbol\Models\EmbeddedMosaicDefinitionTransactionV1;
use SymbolSdk\Symbol\Models\EmbeddedMosaicSupplyChangeTransactionV1;
use SymbolSdk\Symbol\Models\EmbeddedTransferTransactionV1;
use SymbolSdk\Symbol\Models\MosaicId;
use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolSdk\Symbol\Models\AggregateCompleteTransactionV2;
use SymbolSdk\Symbol\Models\NetworkType;
use SymbolSdk\Symbol\Models\Timestamp;
use SymbolSdk\Symbol\Models\UnresolvedAddress;
use SymbolRestClient\Configuration;
use SymbolRestClient\Api\TransactionRoutesApi;
use SymbolRestClient\Api\TransactionStatusRoutesApi;
use SymbolRestClient\Api\AccountRoutesApi;
use SymbolRestClient\Api\MosaicRoutesApi;


/**
 * 秘密鍵からアカウント生成
 */
$aliceKey = $facade->createAccount(new PrivateKey($alicePrivateKey));

/**
 * 5.1 モザイク生成
 */

$f = MosaicFlags::NONE;
$f += MosaicFlags::SUPPLY_MUTABLE; // 供給量変更可能
// $f += MosaicFlags::TRANSFERABLE; // 第三者への譲渡可否
$f += MosaicFlags::RESTRICTABLE; //制限設定の可否
$f += MosaicFlags::REVOKABLE; //発行者からの還収可否
$flags = new MosaicFlags($f);

$mosaicId = IdGenerator::generateMosaicId($aliceKey->address);
var_dump($mosaicId);
// 桁数のチェック（15桁なら先頭に0を付ける）
$hexMosaicId = strtoupper(dechex($mosaicId['id']));
if (strlen($hexMosaicId) === 15) {
    $hexMosaicId = '0' . $hexMosaicId;
}
echo $hexMosaicId . PHP_EOL;

// モザイク定義
$mosaicDefTx = new EmbeddedMosaicDefinitionTransactionV1(
  network: new NetworkType(NetworkType::TESTNET),
  signerPublicKey: $aliceKey->publicKey, // 署名者公開鍵
  id: new MosaicId($mosaicId['id']), // モザイクID
  divisibility: 2, // 分割可能性
  duration: new BlockDuration(0), //duration:有効期限
  nonce: new MosaicNonce($mosaicId['nonce']),
  flags: $flags,
);

//モザイク変更
$mosaicChangeTx = new EmbeddedMosaicSupplyChangeTransactionV1(
  network: new NetworkType(NetworkType::TESTNET),
  signerPublicKey: $aliceKey->publicKey, // 署名者公開鍵
  mosaicId: new UnresolvedMosaicId($mosaicId['id']),
  delta: new Amount(10000),
  action: new MosaicSupplyChangeAction(MosaicSupplyChangeAction::INCREASE),
);

// マークルハッシュの算出
$embeddedTransactions = [$mosaicDefTx, $mosaicChangeTx];
$merkleHash = $facade->hashEmbeddedTransactions($embeddedTransactions);

// アグリゲートTx作成
$aggregateTx = new AggregateCompleteTransactionV2(
  network: new NetworkType(NetworkType::TESTNET),
  signerPublicKey: $aliceKey->publicKey,
  deadline: new Timestamp($facade->now()->addHours(2)),
  transactionsHash: $merkleHash,
  transactions: $embeddedTransactions
);
$facade->setMaxFee($aggregateTx, 100);  // 手数料

// 署名
$sig = $aliceKey->signTransaction($aggregateTx);
$payload = $facade->attachSignature($aggregateTx, $sig);

/**
 * アナウンス
 */
$config = new Configuration();
$config->setHost($NODE_URL);
$client = new GuzzleHttp\Client();
$apiInstance = new TransactionRoutesApi($client, $config);

try {
  $result = $apiInstance->announceTransaction($payload);
  echo $result . PHP_EOL;
} catch (Exception $e) {
  echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
}

sleep(5);
/**
 * ステータスの確認
 */
$hash = $facade->hashTransaction($aggregateTx);
echo "\n===トランザクションハッシュ===" . PHP_EOL;
echo $hash . PHP_EOL;

$txStatusApi = new TransactionStatusRoutesApi($client, $config);

try {
  $txStatus = $txStatusApi->getTransactionStatus($hash);
  echo "\n===ステータスの確認===" . PHP_EOL;
  var_dump($txStatus);
} catch (Exception $e) {
  echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
}

return

/**
 * 確認
 */
// 3.3 アカウント情報の確認 - 所有モザイク一覧の取得 を事前に実施する
$accountApiInstance = new AccountRoutesApi($client, $config);
$mosaicApiInstance = new MosaicRoutesApi($client, $config);

$account = $accountApiInstance->getAccountInfo($aliceKey->address);
foreach($account->getAccount()->getMosaics() as $mosaic) {
  $mocaisInfo = $mosaicApiInstance->getMosaic($mosaic->getId());
  echo "\n===モザイク情報===" . PHP_EOL;
  var_dump($mocaisInfo);
}

/**
 * モザイク送信
 */
// 受信アカウント作成
$bobKey = $facade->createAccount(PrivateKey::random());
$bobAddress = $bobKey->address;

$tx = new TransferTransactionV1(
  network: new NetworkType(NetworkType::TESTNET),
  signerPublicKey: $aliceKey->publicKey,  // 署名者公開鍵
  recipientAddress: $bobAddress,  // 受信者アドレス
  mosaics: [
    new UnresolvedMosaic(
      mosaicId: new UnresolvedMosaicId("0x12679808DC2A1493"),  //5.1で作成したモザイクID
      amount: new Amount(100) //過分性が2のため、100を指定することで送信量が1モザイクとなる
    ),
    new UnresolvedMosaic(
      mosaicId: new UnresolvedMosaicId('0x72C0212E67A08BCE'), // モザイクID
      amount: new Amount(1000000) // 金額(1XYM)
    )
  ],
  message: "\0モザイク送信",
  deadline: new Timestamp($facade->now()->addHours(2)),
);
$facade->setMaxFee($tx, 100); // 手数料

// 署名とアナウンス
$sig = $aliceKey->signTransaction($tx);
$payload = $facade->attachSignature($tx, $sig);

try {
  $result = $apiInstance->announceTransaction($payload);
  echo $result . PHP_EOL;
} catch (Exception $e) {
  echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
}
$hash = $facade->hashTransaction($tx);

sleep(35);

/**
 * 送信確認
 */
$txInfo = $apiInstance->getConfirmedTransaction(
  $hash  // 送信トランザクションのハッシュ
);
echo "\n===送信トランザクション===" . PHP_EOL;
var_dump($txInfo);

/**
 * NFT
 */
$f = MosaicFlags::NONE;
// $f += MosaicFlags::SUPPLY_MUTABLE; // 供給量変更可能
$f += MosaicFlags::TRANSFERABLE; // 第三者への譲渡可否
$f += MosaicFlags::RESTRICTABLE; //制限設定の可否
$f += MosaicFlags::REVOKABLE; //発行者からの還収可否
$flags = new MosaicFlags($f);

$mosaicId = IdGenerator::generateMosaicId($aliceKey->address);

// モザイク定義
$mosaicDefTx = new EmbeddedMosaicDefinitionTransactionV1(
  network: new NetworkType(NetworkType::TESTNET),
  signerPublicKey: $aliceKey->publicKey, // 署名者公開鍵
  id: new MosaicId($mosaicId['id']), // モザイクID
  divisibility: 0, // 分割可能性
  duration: new BlockDuration(0), //duration:有効期限
  nonce: new MosaicNonce($mosaicId['nonce']),
  flags: $flags,
);

//モザイク変更
$mosaicChangeTx = new EmbeddedMosaicSupplyChangeTransactionV1(
  network: new NetworkType(NetworkType::TESTNET),
  signerPublicKey: $aliceKey->publicKey, // 署名者公開鍵
  mosaicId: new UnresolvedMosaicId($mosaicId['id']),
  delta: new Amount(1),
  action: new MosaicSupplyChangeAction(MosaicSupplyChangeAction::INCREASE),
);

//NFTデータ
$nftTx = new EmbeddedTransferTransactionV1(
  network: new NetworkType(NetworkType::TESTNET),
  signerPublicKey: $aliceKey->publicKey,  // 署名者公開鍵
  recipientAddress: $bobAddress,  // 受信者アドレス
  message: "\0NFT送信", //NFTデータ実態
);

// マークルハッシュの算出
$embeddedTransactions = [$mosaicDefTx, $mosaicChangeTx, $nftTx];
$merkleHash = $facade->hashEmbeddedTransactions($embeddedTransactions);

// モザイクの生成とNFTデータをアグリゲートしてブロックに登録
$aggregateTx = new AggregateCompleteTransactionV2(
  network: new NetworkType(NetworkType::TESTNET),
  signerPublicKey: $aliceKey->publicKey,
  deadline: new Timestamp($facade->now()->addHours(2)),
  transactionsHash: $merkleHash,
  transactions: $embeddedTransactions
);
$facade->setMaxFee($aggregateTx, 100);  // 手数料

// 署名
$sig = $aliceKey->signTransaction($aggregateTx);
$payload = $facade->attachSignature($aggregateTx, $sig);

/**
 * アナウンス
 */
try {
  $result = $apiInstance->announceTransaction($payload);
  echo $result . PHP_EOL;
} catch (Exception $e) {
  echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
}

/**
 * 回収トランザクション
 */
$revocationTx = new MosaicSupplyRevocationTransactionV1(
  network: new NetworkType(NetworkType::TESTNET),
  signerPublicKey: $aliceKey->publicKey,
  deadline: new Timestamp($facade->now()->addHours(2)),
  mosaic: new UnresolvedMosaic(
    mosaicId: new UnresolvedMosaicId("0x12679808DC2A1493"),  //5.1で作成したモザイクID
    amount: new Amount(100) //過分性が2のため、100を指定することで送信量が1モザイクとなる
  ),
  sourceAddress: new UnresolvedAddress("TDZ46RYMP6XTRQLOGI3AWULOHV56LBUE7M43MCI"),
);
$facade->setMaxFee($revocationTx, 100); // 手数料

// 署名とアナウンス
$sig = $aliceKey->signTransaction($revocationTx);
$payload = $facade->attachSignature($revocationTx, $sig);

try {
  $result = $apiInstance->announceTransaction($payload);
  echo $result . PHP_EOL;
} catch (Exception $e) {
  echo 'Exception when calling TransactionRoutesApi->announceTransaction: ', $e->getMessage(), PHP_EOL;
}