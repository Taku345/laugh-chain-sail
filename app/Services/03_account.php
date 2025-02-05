<?php
require_once(__DIR__ . '/util.php');

use SymbolSdk\Symbol\KeyPair;
use SymbolSdk\Symbol\MessageEncoder;
use SymbolSdk\CryptoTypes\PrivateKey;
use SymbolRestClient\Api\AccountRoutesApi;
use SymbolRestClient\Configuration;
use SymbolSdk\Symbol\Models\PublicKey;
use SymbolSdk\Symbol\Address;
use SymbolSdk\Symbol\Verifier;

use SymbolSdk\Symbol\Models\PublicKey as SymbolPublicKey;

/**
 * 新規生成
 */

// $aliceKey = $facade->createAccount(PrivateKey::random());
// echo "===新規生成===" . PHP_EOL;
// var_dump($aliceKey);



/**
 * 秘密鍵からアカウント生成
 */
echo $_ENV['OFFICIAL_ACCOUNT_PRIVATE_KEY'];
$alicePrivateKey = $_ENV['OFFICIAL_ACCOUNT_PRIVATE_KEY'];
$aliceKey = $facade->createAccount(new PrivateKey($alicePrivateKey));
$aliceRawAddress = $aliceKey->address;
echo "\n===秘密鍵からアカウント生成===" . PHP_EOL;
echo $aliceRawAddress . PHP_EOL;

/**
 * 秘密鍵と公開鍵の導出
 */

echo  "===秘密鍵と公開鍵の導出===" . PHP_EOL;
echo  $aliceKey->publicKey . PHP_EOL;
echo  $aliceKey->keyPair->privateKey() . PHP_EOL;

$alicePublicKey = $aliceKey->publicKey;

/**
 * アドレスの導出
 */
echo "\n===アドレスの導出===" . PHP_EOL;
$aliceRawAddress = $aliceKey->address;
echo $aliceRawAddress . PHP_EOL;

/**
 * 公開鍵クラスの生成
 */

$alicePublicAccount = $facade->createPublicAccount(new PublicKey($alicePublicKey));

echo "\n===公開鍵クラスの生成===" . PHP_EOL;
var_dump($alicePublicAccount->address);
echo substr($alicePublicAccount->publicKey, 2, 66) . PHP_EOL;

// /**
//  * アドレスクラスの生成
//  */

$aliceAddress = new Address($aliceRawAddress);

echo "\n===アドレスクラスの生成===" . PHP_EOL;
echo $aliceAddress->__tostring() . PHP_EOL;

// /**
//  * 所有モザイク一覧の取得
//  */

$config = new Configuration();
$config->setHost($NODE_URL);
$client = new \GuzzleHttp\Client();
$accountApiInstance = new AccountRoutesApi($client, $config);

// echo $aliceRawAddress;
$account = $accountApiInstance->getAccountInfo($aliceAddress);

echo "\n===アカウント情報の確認===" . PHP_EOL;
echo $account . PHP_EOL;

// /**
//  * 暗号化と署名
//  */

// $bobKey = new KeyPair($bobPrivateKey);
$bobKey = new KeyPair(PrivateKey::random());

$message = "Hello Symbol!";
$encryptedMessage = $aliceKey->messageEncoder()->encode($bobKey->publicKey(), $message);
echo strtoupper(bin2hex($encryptedMessage)) . PHP_EOL;

// /**
//  * 暗号化
//  */
$bobMsgEncoder = new MessageEncoder($bobKey);
$decryptMessageData = $bobMsgEncoder->tryDecode($aliceKey->keyPair->publicKey(), $encryptedMessage);
var_dump($decryptMessageData);
if ($decryptMessageData['isDecoded']) {
    echo "\nDecoded message: " . PHP_EOL;
    echo $decryptMessageData["message"] . PHP_EOL;
} else {
    echo "\nFailed to decode message" . PHP_EOL;
}

// /**
//  * 署名
//  */
$payload = "Hellow Symbol!";
$signature = $aliceKey->keyPair->sign($payload);
echo "\n===署名===" . PHP_EOL;
echo $signature . PHP_EOL;

// /**
//  * 検証
//  */

// 追加: 公開鍵をSymbolPublicKeyに変換
// $alicePublicKeyCrypto = $aliceKey->keyPair->publicKey();
// $alicePublicKeyHex = $alicePublicKeyCrypto->hex(); // 16進数文字列として取得
$alicePublicKeySymbol = new SymbolPublicKey($aliceKey->keyPair->publicKey());
$bobPublicKeySymbol = new SymbolPublicKey($bobKey->publicKey());


echo "\n===検証===" . PHP_EOL;
$v = new Verifier($alicePublicKeySymbol);
$isVerified = $v->verify($payload, $signature);
echo "alice verified: " . PHP_EOL;
var_dump($isVerified);

$othersKey = new KeyPair(PrivateKey::random());
$v = new Verifier($bobPublicKeySymbol);
$isVerified = $v->verify($payload, $signature);
echo "bob verified: " . PHP_EOL;
var_dump($isVerified);
