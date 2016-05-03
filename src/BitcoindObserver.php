<?php
/**
 * Created by PhpStorm.
 * User: tk
 * Date: 01/05/16
 * Time: 18:03
 */

namespace BitWasp\Utxo;

use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Rpc\RpcFactory;
use BitWasp\Bitcoin\Serializer\Transaction\OutPointSerializer;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\BufferInterface;
use BitWasp\Buffertools\Buffer;
use React\EventLoop\LoopInterface;

class BitcoindObserver
{
    /**
     * @var UtxoDb
     */
    private $utxoDb;
    /**
     * @var \BitWasp\Bitcoin\Rpc\Client\Bitcoind
     */
    private $rpc;

    public function __construct(LoopInterface $loop, UtxoDb $utxoDb)
    {
        $context = new \React\ZMQ\Context($loop);
        $sub = $context->getSocket(\ZMQ::SOCKET_SUB);
        $sub->connect('tcp://127.0.0.1:28332');
        $sub->subscribe('raw');
        $sub->subscribe('hash');

        $this->utxoDb = $utxoDb;
        $this->bestBlock = $this->utxoDb->getBestBlock();

        $sub->on('messages', [$this, 'processMessage']);

        $this->rpc = RpcFactory::bitcoind('127.0.0.1', 8332, 'bitcoinrpc','bitcoinrpc2')->getRpcClient();
        $this->doInitialSync();
    }

    /**
     * @param array $msgs
     */
    public function processMessage(array $msgs)
    {
        $topic = $msgs[0];
        $buffer = new Buffer($msgs[1]);
        if ($topic == 'rawblock') {
            $block = BlockFactory::fromHex($buffer);
            $this->checkBlock($block);
        }
    }

    /**
     *
     */
    public function doInitialSync()
    {
        $bitcoindBest = $this->rpc->execute('getblockheader', [$this->rpc->execute('getbestblockhash')]);
        $startHeight = $this->bestBlock['height'] == '0' ? 0 : $this->bestBlock['height'] + 1;

        for ($i = $startHeight; $i < $bitcoindBest['height']; $i++) {
            echo "\n";
            echo "Do block $i\n";
            $hash = $this->rpc->execute('getblockhash', [(int)$i]);
            $blockHex = $this->rpc->execute('getblock', [$hash, false]);
            $block = BlockFactory::fromHex($blockHex);
            $this->processBlock($block, $i);
            if ($i == 169) {
                die();
            }
        }
    }

    /**
     * @param BlockInterface $block
     * @param $height
     */
    public function processBlock(BlockInterface $block, $height)
    {
        $start = microtime(true);
        $hash = $block->getHeader()->getHash();
        $this->updateSet($block);
        $this->utxoDb->insertBlock($hash, $block->getHeader(), $height);

        echo "Time: " . (microtime(true) - $start) . " seconds" . PHP_EOL;
    }

    /**
     * @param BlockInterface $block
     */
    public function updateSet(BlockInterface $block)
    {
        $outpoints = $this->utxoDb->getOutpoints();
        $scripts = $this->utxoDb->getScripts();
        
        list ($required, $created) = $this->parseWalletEffects($block, $scripts, $outpoints);

        echo "Delete UTXOS: " . count($required) . PHP_EOL;
        echo "New UTXOS: " . count($created) . PHP_EOL;

        $this->utxoDb->deleteOutpoints($required);
        $this->utxoDb->addUtxos($created);
    }

    /**
     * @param BlockInterface $block
     * @param array $scripts
     * @param array $outpoints
     * @return array
     */
    public function parseWalletEffects(BlockInterface $block, array $scripts, array $outpoints)
    {
        list ($required, $created) = $this->parseUtxos($block);

        $walletSpend = [];
        foreach ($outpoints as $txid) {
            if (isset($required[$txid])) {
                $walletSpend[$txid] = $required[$txid];
            }
        }

        $walletReceive = [];
        foreach ($scripts as $script) {
            if (isset($created[$script])) {
                $walletReceive[$script] = $created[$script];
            }
        }

        return [$walletSpend, $walletReceive];
    }

    /**
     * @param BlockInterface $block
     * @return array
     */
    public function parseUtxos(BlockInterface $block)
    {
        /**
         * @var Utxo[] $created
         * @var OutPointInterface[] $required
         */
        $created = [];
        $required = [];
        $outpointSerializer = new OutPointSerializer();
        foreach ($block->getTransactions() as $transaction) {
            if ($transaction->isCoinbase()) {
                continue;
            }

            foreach ($transaction->getInputs() as $i => $input) {
                $lookup = $outpointSerializer->serialize($input->getOutPoint());
                $required[$lookup->getBinary()] = $input->getOutPoint();
            }
        }

        foreach ($block->getTransactions() as $transaction) {
            $hash = $transaction->getTxId();
            foreach ($transaction->getOutputs() as $i => $output) {
                $v = pack("N", $i);
                $lookup = $hash->getBinary() . $v;
                if (isset($required[$lookup])) {
                    unset($required[$lookup]);
                } else {
                    $utxo = new Utxo(new OutPoint($hash, $i), $output);
                    $created[$output->getScript()->getBinary()] = $utxo;
                }
            }
        }

        return [$required, $created];
    }

    /**
     * @param BlockInterface $block
     */
    public function checkBlock(BlockInterface $block)
    {
        $hash = $block->getHeader()->getHash();
        $dbBlock = $this->utxoDb->getBlockByHash($hash);
        if (!$dbBlock) {
            echo "Didn't have this";
        }
    }
}