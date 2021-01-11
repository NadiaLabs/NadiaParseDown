<?php

namespace NadiaParseDown\CodeBlockRenderers;

class TocTreeNode
{
    /**
     * @var int
     */
    private $level;
    /**
     * @var string
     */
    private $text;
    /**
     * @var TocTreeNode
     */
    private $parent;
    /**
     * @var TocTreeNode[]
     */
    private $nodes = [];
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $targetIdPrefix = '';

    /**
     * TocTreeNode constructor.
     *
     * @param int $level
     * @param string $text
     * @param TocTreeNode|null $parent
     * @param string $url
     */
    public function __construct($level, $text, $parent = null, $url = '')
    {
        $this->parent = $parent;
        $this->level = $level;
        $this->text = $text;
        $this->url = $url;
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
     * @return TocTreeNode
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
     * @return TocTreeNode
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return TocTreeNode
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return bool
     */
    public function hasParent()
    {
        return $this->parent instanceof TocTreeNode;
    }

    /**
     * @param TocTreeNode $parent
     *
     * @return TocTreeNode
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return TocTreeNode[]
     */
    public function getNodes()
    {
        return $this->nodes;
    }

    /**
     * @param TocTreeNode[] $nodes
     *
     * @return TocTreeNode
     */
    public function setNodes(array $nodes)
    {
        $this->nodes = $nodes;

        return $this;
    }

    /**
     * @param TocTreeNode $node
     *
     * @return TocTreeNode
     */
    public function addNode(TocTreeNode $node)
    {
        $this->nodes[] = $node;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasNodes()
    {
        return !empty($this->nodes);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return TocTreeNode
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getTargetIdPrefix()
    {
        return $this->targetIdPrefix;
    }

    /**
     * @param string $targetIdPrefix
     *
     * @return TocTreeNode
     */
    public function setTargetIdPrefix($targetIdPrefix)
    {
        $this->targetIdPrefix = $targetIdPrefix;

        return $this;
    }
}
