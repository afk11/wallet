<?php


require "vendor/autoload.php";
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;

$pubHex = '04ae1a62fe09c5f51b13905f07f06b99a2f7159b2225f374cd378d71302fa28414e7aab37397f554a7df5f142c21c1b7303b8a0626f1baded5c72a704f7e6cd84c';
$key = PublicKeyFactory::fromHex($pubHex);
$script = ScriptFactory::scriptPubKey()->payToPubKey($key);
echo $script->getHex().PHP_EOL;die();
$loop = \React\EventLoop\Factory::create();
$pdo = new \PDO('mysql:host=127.0.0.1;dbname=utxo', 'root', 'sugarpop101');

$genesis = new BlockHeader(
    1,
    new Buffer('',32),
    Buffer::hex('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b',32),
    '1452831101',
    Buffer::hex('1e01ffff', 4),
    '0'
);

$utxoDb = new \BitWasp\Utxo\UtxoDb($pdo, $genesis);
$utxoDb->addScript($script);


