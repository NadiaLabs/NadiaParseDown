<?php

namespace NadiaParseDown\CodeBlockRenderers;

use Exception;
use NadiaParseDown\BlockTreeNode;
use NadiaParseDown\NadiaParseDown;

class TocTreeRenderer implements RendererInterface
{
    const CODE_BLOCK_LANGUAGE = 'toc-tree';

    public static $parsedFiles = [];
    /**
     * @var TocTreeNode[]
     */
    public static $parsedTocTrees = [];

    /**
     * @var NadiaParseDown
     */
    private $parseDown;
    /**
     * @var string
     */
    private $docRootDir;

    /**
     * TocTreeRenderer constructor.
     *
     * @param NadiaParseDown $parseDown
     * @param string $docRootDir
     */
    public function __construct(NadiaParseDown $parseDown, $docRootDir)
    {
        $this->parseDown = $parseDown;
        $this->docRootDir = $docRootDir;
    }

    /**
     * @param array $element
     *
     * @return string
     *
     * @throws Exception
     */
    public function render(array $element)
    {
        $docRootDir = $this->getDocRootDir();
        if (!file_exists($docRootDir)) {
            return '';
        }

        $maxDepth = empty($element['extra_attributes']['max_depth']) ? 1 : $element['extra_attributes']['max_depth'];
        if ($maxDepth < 1) {
            return '';
        }

        $tocTree = $this->parseTocTree($element['text']);

        if (!$tocTree->hasNodes()) {
            return '';
        }

        static::$parsedTocTrees[] = $tocTree;

        return $this->renderTocTree($tocTree, $maxDepth) . PHP_EOL;
    }

    /**
     * @param string $tocText
     *
     * @return TocTreeNode
     *
     * @throws Exception
     */
    private function parseTocTree($tocText)
    {
        $docRootDir = $this->getDocRootDir();
        $filePaths = $this->parseFilePaths($tocText);

        $tocTree = new TocTreeNode(0, 'root');

        foreach ($filePaths as $filePath) {
            $parseDown = clone $this->parseDown;

            $parseDown->setBlockIdPrefix($filePath);

            # Standardize line breaks
            $text = str_replace(array("\r\n", "\r"), "\n", file_get_contents($filePath));
            # Remove surrounding line breaks
            $text = trim($text, "\n");
            # Split text into lines
            $lines = explode("\n", $text);

            $blockTree = $parseDown->parseBlocks($lines);
            $relatedFilePath = str_replace($docRootDir, '', $filePath);

            if (!isset(static::$parsedFiles[$relatedFilePath])) {
                static::$parsedFiles[$relatedFilePath] = [
                    'filePath' => $relatedFilePath,
                    'targetId' => $parseDown->getOption('block_id_prefix'),
                    'html' => $parseDown->renderBlocks($blockTree),
                ];
            }

            $this->buildTocTreeNodes($blockTree, $tocTree);
        }

        return $tocTree;
    }

    /**
     * @param BlockTreeNode $blockTree
     * @param TocTreeNode $tocTree
     */
    private function buildTocTreeNodes(BlockTreeNode $blockTree, TocTreeNode $tocTree)
    {
        foreach ($blockTree->getNodes() as $blockTreeNode) {
            if ($blockTreeNode->hasText()) {
                $newTocTreeNode = new TocTreeNode(
                    $blockTreeNode->getLevel(),
                    $blockTreeNode->getText(),
                    $tocTree,
                    '#' . $blockTreeNode->getId()
                );
                $tocTree->setTargetIdPrefix($blockTreeNode->getIdPrefix());
                $tocTree->addNode($newTocTreeNode);

                $this->buildTocTreeNodes($blockTreeNode, $newTocTreeNode);
            }
        }
    }

    /**
     * @param TocTreeNode $tree
     * @param int $maxDepth
     *
     * @return string
     */
    private function renderTocTree(TocTreeNode $tree, $maxDepth)
    {
        if ($maxDepth < 1) {
            return '';
        }

        $indent1 = str_repeat(' ', 4 * $tree->getLevel() + 2);
        $indent2 = str_repeat(' ', 4 * $tree->getLevel() + 4);
        $indent3 = str_repeat(' ', 4 * $tree->getLevel() + 6);
        $html = $indent1 . '<ul>' . PHP_EOL;

        foreach ($tree->getNodes() as $node) {
            $nodeHtml = $indent2 . '<li>' . PHP_EOL;
            $nodeHtml .= $indent3 .
                '<a href="' . NadiaParseDown::escape($node->getUrl()) . '">' .
                $node->getText() .
                '</a>';

            if ($maxDepth > 1) {
                --$maxDepth;

                $nodeHtml .= PHP_EOL . $this->renderTocTree($node, $maxDepth);
            }

            $nodeHtml .= PHP_EOL . $indent2 . '</li>' . PHP_EOL;
            $html .= $nodeHtml;
        }

        $html .= $indent1 . '</ul>';

        return $html;
    }

    /**
     * @param string $tocText
     *
     * @return array
     */
    private function parseFilePaths($tocText)
    {
        $docRootDir = $this->getDocRootDir();
        $filePaths = [];

        foreach (explode("\n", $tocText) as $line) {
            $line = trim($line, "/ \t\n\r\0\x0B");
            if (empty($line)) {
                continue;
            }

            $filePath = $docRootDir . '/' . $line;
            if (!file_exists($filePath)) {
                continue;
            }

            if (is_dir($filePath)) {
                foreach (scandir($filePath) as $filename) {
                    $subFilePath = $filePath . '/' . $filename;

                    if (is_file($subFilePath)) {
                        $filePaths[] = $subFilePath;
                    }
                }
            } else {
                $filePaths[] = $filePath;
            }
        }

        return $filePaths;
    }

    /**
     * @return string
     */
    private function getDocRootDir()
    {
        return $this->docRootDir;
    }
}
