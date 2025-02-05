<?php
require_once(__DIR__ . '/../../vendor/autoload.php');
Dotenv\Dotenv::createImmutable(__DIR__ . '/../../')->load();

use SymbolRestClient\Api\NodeRoutesApi;
use SymbolRestClient\Api\NetworkRoutesApi;
use SymbolRestClient\Configuration;
use SymbolSdk\Facade\SymbolFacade;
use SymbolRestClient\Api\TransactionRoutesApi;
use SymbolRestClient\Api\AccountRoutesApi;
use SymbolRestClient\Api\MosaicRoutesApi;

$NODE_URL = env('NODE_URL');

$config = new Configuration();
$config->setHost($NODE_URL);
$client = new \GuzzleHttp\Client();
$transactionRoutesApi = new TransactionRoutesApi($client, $config);
$accountRoutesApi= new AccountRoutesApi($client, $config);
$mosaicRoutesApi = new MosaicRoutesApi($client, $config);

// /node/info
$nodeInfoApiInstance = new NodeRoutesApi($client, $config);
$nodeInfo = $nodeInfoApiInstance->getNodeInfo();
// /network/properties
$networkType = $nodeInfo->getNetworkIdentifier();
$generationHash = $nodeInfo->getNetworkGenerationHashSeed();

$networkApiInstance = new NetworkRoutesApi($client, $config);
$networkProperties = $networkApiInstance->getNetworkProperties();

$epochAdjustment = $networkProperties->getNetwork()->getEpochAdjustment();
$identifier = $networkProperties->getNetwork()->getIdentifier();
$facade = new SymbolFacade('testnet');
$epochAdjustment = 1667250467;
