<?php

require "vendor/autoload.php";

use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Block\BlockHeader;
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
$bitcoind = new \BitWasp\Utxo\BitcoindObserver($loop, $utxoDb);

$loop->run();
