<?php

namespace BitWasp\Utxo;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\BufferInterface;

class Db
{
    private $pdo;
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->stmtGetBlock = $pdo->prepare('SELECT * FROM chain where hashKey=?');
        $this->stmtBestBlock = $pdo->prepare('SELECT * FROM chain order by id desc limit 1');
        $this->stmtGetBlockByHeight = $pdo->prepare('SELECT * FROM chain where height=?');
        $this->stmtInsertBlock = $pdo->prepare('INSERT INTO chain (hashKey, version, prev, merkle, ntime, nbits, nonce, height) values (?,?,?,?,?,?,?,?)');
        $this->stmtGetScripts = $pdo->prepare('SELECT scriptPubKey FROM script');
        $this->stmtGetScript = $pdo->prepare('SELECT * FROM script WHERE id = ?');
        $this->stmtGetUtxos = $pdo->prepare('SELECT * FROM script');
        $this->stmtGetOutpoints = $pdo->prepare('SELECT outpoint FROM utxo');
        $this->stmtInsertScript = $pdo->prepare('INSERT INTO script (scriptPubKey) values (?)');
    }

    /**
     * @return array|false
     */
    public function getBestBlock() {
        $this->stmtBestBlock->execute();
        $result = $this->stmtBestBlock->fetch(\PDO::FETCH_ASSOC);
        if (!$result) {
            throw new \RuntimeException('Db not initialized, nothing found in chain');
        }

        return $result;
    }

    /**
     * @param BufferInterface $hash
     * @return array|false
     */
    public function getBlockByHash(BufferInterface $hash) {
        $this->stmtGetBlock->execute([$hash->getBinary()]);
        $block = $this->stmtGetBlock->fetch(\PDO::FETCH_ASSOC);
        return $block;
    }

    /**
     * @param int $height
     * @return array|false
     */
    public function getBlockByHeight($height)
    {
        $this->stmtGetBlock->execute([$height]);
        $block = $this->stmtGetBlock->fetch(\PDO::FETCH_ASSOC);
        return $block;
    }

    /**
     * @param BufferInterface $hash
     * @param BlockHeaderInterface $header
     * @param int $height
     * @return bool
     */
    public function insertBlock(BufferInterface $hash, BlockHeaderInterface $header, $height)
    {
        $this->stmtInsertBlock->execute([
            $hash->getBinary(),
            $header->getVersion(),
            $header->getPrevBlock()->getBinary(),
            $header->getMerkleRoot()->getBinary(),
            $header->getTimestamp(),
            $header->getBits()->getInt(),
            $header->getNonce(),
            $height
        ]);

        return true;
    }

    /**
     * @param Utxo[] $utxos
     * @return bool
     */
    public function addUtxos(array $utxos)
    {
        if (0 === count($utxos)) {
            return true;
        }

        $queryValues = [];
        $queryBind = [];
        foreach ($utxos as $utxo) {
            $queryBind[] = "(?,?,?)";
            $queryValues[] = $utxo->getOutPoint()->getBinary();
            $queryValues[] = $utxo->getOutput()->getScript()->getBinary();
            $queryValues[] = $utxo->getOutput()->getValue();
        }

        $sql = "INSERT INTO utxo (outpoint, scriptPubKey, value) VALUES ".implode(",", $queryBind);
        $insert = $this->pdo->prepare($sql);
        return $insert->execute($queryValues);
    }

    /**
     * @param OutPointInterface[] $outpoints
     * @return bool
     */
    public function deleteOutpoints(array $outpoints)
    {
        if (0 === count($outpoints)) {
            return true;
        }

        $queryValues = [];
        $queryBind = [];
        foreach ($outpoints as $outpoint) {
            $queryBind[] = "'?'";
            $queryValues[] = $outpoint->getBinary();
        }

        $sql = "DELETE FROM utxo WHERE outpoint in (".implode(",", $queryBind).")";
        $insert = $this->pdo->prepare($sql);
        return $insert->execute($queryValues);
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function addScript(ScriptInterface $script)
    {
        return $this->stmtInsertScript->execute([$script->getBinary()]);
    }

    /**
     * @return array
     */
    public function getScripts()
    {
        $this->stmtGetScripts->execute();
        $v = $this->stmtGetScripts->fetchAll(\PDO::FETCH_COLUMN);
        return $v;
    }

    /**
     * @return array
     */
    public function getUtxos()
    {
        $this->stmtGetUtxos->execute();
        return $this->stmtGetUtxos->fetchAll();
    }

    /**
     * @return array
     */
    public function getOutpoints()
    {
        $this->stmtGetOutpoints->execute();
        $v = $this->stmtGetOutpoints->fetchAll(\PDO::FETCH_COLUMN);
        return $v;
    }
}