<?php

namespace NadiaParseDown;

class BlockTreeNode
{
    /**
     * @var array Block data
     */
    private $block = [];
    /**
     * @var string Tree node id attribute
     */
    private $id = '';
    /**
     * @var string Tree node id prefix
     */
    private $idPrefix = '';
    /**
     * @var int Tree nested level
     */
    private $level = 0;
    /**
     * @var string Node text
     */
    private $text = '';
    /**
     * @var BlockTreeNode|null Parent node
     */
    private $parent = null;
    /**
     * @var BlockTreeNode[] Child nodes
     */
    private $nodes = [];

    /**
     * @return array
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @param array $block
     *
     * @return $this
     */
    public function setBlock(array $block)
    {
        $this->block = $block;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasBlock()
    {
        return !empty($this->block);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdPrefix()
    {
        return $this->idPrefix;
    }

    /**
     * @param string $idPrefix
     *
     * @return $this
     */
    public function setIdPrefix($idPrefix)
    {
        $this->idPrefix = $idPrefix;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasId()
    {
        return !empty($this->id);
    }

    /**
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @param int $level
     *
     * @return $this
     */
    public function setLevel($level)
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     *
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasText()
    {
        return !empty($this->text);
    }

    /**
     * @return BlockTreeNode|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param BlockTreeNode $parent
     *
     * @return BlockTreeNode
     */
    public function setParent(BlockTreeNode $parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return !is_null($this->parent);
    }

    /**
     * @param BlockTreeNode $node
     *
     * @return $this
     */
    public function addNode(BlockTreeNode $node)
    {
        $this->nodes[] = $node;

        return $this;
    }

    /**
     * @return BlockTreeNode[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * @return bool
     */
    public function hasNodes()
    {
        return !empty($this->nodes);
    }
}
