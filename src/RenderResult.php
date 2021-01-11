<?php

namespace NadiaParseDown;

class RenderResult
{
    /**
     * @var string
     */
    private $html;
    /**
     * @var BlockTreeNode
     */
    private $blockTree;

    /**
     * RenderResult constructor.
     *
     * @param string $html
     * @param BlockTreeNode $blockTree
     */
    public function __construct($html, BlockTreeNode $blockTree)
    {
        $this->html = $html;
        $this->blockTree = $blockTree;
    }

    /**
     * @return string
     */
    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @param string $html
     *
     * @return RenderResult
     */
    public function setHtml($html)
    {
        $this->html = $html;

        return $this;
    }

    /**
     * @return BlockTreeNode
     */
    public function getBlockTree()
    {
        return $this->blockTree;
    }

    /**
     * @param BlockTreeNode $blockTree
     *
     * @return RenderResult
     */
    public function setBlockTree($blockTree)
    {
        $this->blockTree = $blockTree;

        return $this;
    }
}
