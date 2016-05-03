<?php

namespace BitWasp\Utxo;


use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Transaction\OutPointInterface;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\BufferInterface;

class UtxoDb
{
    /**
     * UtxoDb constructor.
     * @param \PDO $pdo
     * @param BlockHeaderInterface $genesis
     */
    public function __construct(\PDO $pdo, BlockHeaderInterface $genesis) 
    {
        $this->db = new Db($pdo);

        $genesisHash = $genesis->getHash();
        if (!$this->db->getBlockByHash($genesisHash)) {
            $this->db->insertBlock($genesisHash, $genesis, 0);
        }
    }

    /**
     * @param BufferInterface $hash
     * @return array|false
     */
    public function getBlockByHash(BufferInterface $hash)
    {
        return $this->db->getBlockByHash($hash);
    }

    /**
     * @return array|false
     */
    public function getBestBlock()
    {
        return $this->db->getBestBlock();
    }

    /**
     * @param int $height
     * @return array|false
     */
    public function getBlockByHeight($height)
    {
        return $this->db->getBlockByHeight($height);
    }

    /**
     * @param BufferInterface $hash
     * @param BlockHeaderInterface $header
     * @param int $height
     * @return bool
     */
    public function insertBlock(BufferInterface $hash, BlockHeaderInterface $header, $height)
    {
        return $this->db->insertBlock($hash, $header, $height);
    }

    /**
     * @param Utxo[] $utxos
     * @return bool
     */
    public function addUtxos(array $utxos)
    {
        return $this->db->addUtxos($utxos);
    }

    /**
     * @param OutPointInterface[] $outpoints
     * @return bool
     */
    public function deleteOutpoints(array $outpoints)
    {
        return $this->db->deleteOutpoints($outpoints);
    }

    /**
     * @param ScriptInterface $script
     * @return bool
     */
    public function addScript(ScriptInterface $script)
    {
        return $this->db->addScript($script);
    }


    /**
     * @return array
     */
    public function getScripts()
    {
        return $this->db->getScripts();
    }

    /**
     * @return array
     */
    public function getUtxos()
    {
        return $this->db->getUtxos();
    }

    /**
     * @return array
     */
    public function getOutpoints()
    {
        return $this->db->getOutpoints();
    }
}