<?php

namespace NadiaParseDown;

use Exception;
use NadiaParseDown\CodeBlockHighlighters\DefaultHighlighter;
use NadiaParseDown\CodeBlockHighlighters\HighlighterInterface;
use NadiaParseDown\CodeBlockRenderers\RendererInterface;
use ParsedownExtra;

class NadiaParseDown extends ParsedownExtra
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var int Count all blocks
     */
    private $blockCount = 0;

    /**
     * Setup custom code-block renderers
     *
     * Array key: code block's language name
     * Array value: renderer instance that implements RendererInterface
     *
     * @var RendererInterface[]
     */
    private $customCodeBlockRenderers = array();

    /**
     * The highlighter instance for code blocks
     *
     * @var HighlighterInterface
     */
    private $highlighter;

    /**
     * @var array
     */
    const DEFAULT_OPTIONS = array(
        'block_id_prefix' => '',
        // Use with `{{variableName}}` markup
        'variables' => array(),
        // Use with `{{variableName}}` markup
        'variable_replaces' => array(),
    );

    /**
     * NadiaParseDown constructor.
     *
     * @param array $options
     *
     * @throws Exception
     */
    public function __construct(array $options = array())
    {
        parent::__construct();

        $this->BlockTypes[':'][] = 'FencedCode';
        $this->BlockTypes[':'] = array_unique($this->BlockTypes[':']);

        $this->options = array_merge(self::DEFAULT_OPTIONS, $options);

        if (!empty($this->options['variables'])) {
            $replaces = [];

            foreach ($this->options['variables'] as $key => $value) {
                $replaces['{{$' . $key . '}}'] = $value;
            }

            $this->options['variable_replaces'] = $replaces;
        }
    }

    /**
     * Cloning instance
     */
    public function __clone()
    {
        $this->blockCount = 0;
    }

    /**
     * @param string $language
     * @param RendererInterface $renderer
     *
     * @return NadiaParseDown
     */
    public function addCustomCodeBlockRenderer($language, RendererInterface $renderer)
    {
        $this->customCodeBlockRenderers[$language] = $renderer;

        return $this;
    }

    /**
     * @return HighlighterInterface
     */
    public function getHighlighter()
    {
        if (is_null($this->highlighter)) {
            $this->highlighter = new DefaultHighlighter();
        }

        return $this->highlighter;
    }

    /**
     * @param HighlighterInterface $highlighter
     *
     * @return NadiaParseDown
     */
    public function setHighlighter(HighlighterInterface $highlighter)
    {
        $this->highlighter = $highlighter;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return NadiaParseDown
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Render a markdown file
     *
     * @param string $filePath
     *
     * @return RenderResult|null
     */
    public function render($filePath)
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);

        $this->setBlockIdPrefix($filePath);

        // Copy from Parsedown.php
        $this->DefinitionData = array();
        $text = str_replace(array("\r\n", "\r"), "\n", $content);
        $text = trim($text, "\n");
        $lines = explode("\n", $text);
        // Replace original `$markup = $this->lines($lines);`
        $markup = $this->renderBlocks($blockTree = $this->parseBlocks($lines));
        $markup = trim($markup, "\n");
        // Copy from ParsedownExtra.php
        $markup = preg_replace('/<\/dl>\s+<dl>\s+/', '', $markup);
        if (isset($this->DefinitionData['Footnote'])) {
            $markup .= "\n" . $this->element($this->buildFootnoteElement());
        }

        return new RenderResult($markup, $blockTree);
    }

    /**
     * @param string $filePath
     *
     * @return NadiaParseDown
     */
    public function setBlockIdPrefix($filePath)
    {
        $this->setOption('block_id_prefix', md5($filePath) . '-');

        return $this;
    }

    /**
     * Wrapper for element method
     *
     * @param array $element
     *
     * @return string
     */
    public function renderElement(array $element)
    {
        return $this->element($element);
    }

    /**
     * Overwrite original `lines` method
     *
     * @param array $lines
     *
     * @return BlockTreeNode
     */
    public function parseBlocks(array $lines)
    {
        $CurrentBlock = null;

        foreach ($lines as $line) {
            if (chop($line) === '') {
                if (isset($CurrentBlock)) {
                    $CurrentBlock['interrupted'] = true;
                }

                continue;
            }

            if (strpos($line, "\t") !== false) {
                $parts = explode("\t", $line);

                $line = $parts[0];

                unset($parts[0]);

                foreach ($parts as $part) {
                    $shortage = 4 - mb_strlen($line, 'utf-8') % 4;

                    $line .= str_repeat(' ', $shortage);
                    $line .= $part;
                }
            }

            $indent = 0;

            while (isset($line[$indent]) and $line[$indent] === ' ') {
                $indent ++;
            }

            $text = $indent > 0 ? substr($line, $indent) : $line;

            # ~

            $Line = array('body' => $line, 'indent' => $indent, 'text' => $text);

            # ~

            if (isset($CurrentBlock['continuable'])) {
                $Block = $this->{'block' . $CurrentBlock['type'] . 'Continue'}($Line, $CurrentBlock);

                if (isset($Block)) {
                    $CurrentBlock = $Block;

                    continue;
                } else {
                    if ($this->isBlockCompletable($CurrentBlock['type'])) {
                        $CurrentBlock = $this->{'block' . $CurrentBlock['type'] . 'Complete'}($CurrentBlock);
                    }
                }
            }

            # ~

            $marker = $text[0];

            # ~

            $blockTypes = $this->unmarkedBlockTypes;

            if (isset($this->BlockTypes[$marker])) {
                foreach ($this->BlockTypes[$marker] as $blockType) {
                    $blockTypes []= $blockType;
                }
            }

            #
            # ~

            foreach ($blockTypes as $blockType) {
                $Block = $this->{'block' . $blockType}($Line, $CurrentBlock);

                if (isset($Block)) {
                    $Block['type'] = $blockType;

                    if ( ! isset($Block['identified'])) {
                        $Blocks []= $CurrentBlock;

                        $Block['identified'] = true;
                    }

                    if ($this->isBlockContinuable($blockType)) {
                        $Block['continuable'] = true;
                    }

                    $CurrentBlock = $Block;

                    continue 2;
                }
            }

            # ~

            if (isset($CurrentBlock) and ! isset($CurrentBlock['type']) and ! isset($CurrentBlock['interrupted'])) {
                $CurrentBlock['element']['text'] .= "\n" . $text;
            } else {
                $Blocks []= $CurrentBlock;

                $CurrentBlock = $this->paragraph($Line);

                $CurrentBlock['identified'] = true;
            }
        }

        # ~

        if (isset($CurrentBlock['continuable']) and $this->isBlockCompletable($CurrentBlock['type'])) {
            $CurrentBlock = $this->{'block' . $CurrentBlock['type'] . 'Complete'}($CurrentBlock);
        }

        # ~

        $Blocks []= $CurrentBlock;

        unset($Blocks[0]);

        // Overwrite part
        return $this->buildBlockTree($Blocks);
    }

    /**
     * @param BlockTreeNode $tree
     * @param int $indent
     *
     * @return string
     */
    public function renderBlocks(BlockTreeNode $tree, $indent = 1)
    {
        $spaces = str_repeat('  ', $indent);
        $markup = '';

        if (1 === $indent) {
            $markup .= '<div id="' . $this->getOption('block_id_prefix') . '" class="parsed-html-container">' . PHP_EOL;
        }

        if ($tree->hasBlock()) {
            if (1 || $tree->hasNodes()) {
                $markup .= $spaces . '<div';

                if ($tree->hasId()) {
                    $markup .= ' id="' . self::escape($tree->getId()) . '"';
                }

                $markup .= ' class="md-block"';
                $markup .= ' data-text="' . self::escape($tree->getText()) . '"';
                $markup .= '>' . "\n";
            }

            $markup .= $spaces . '  ' . $this->renderBlock($tree->getBlock()) . "\n";
        }

        foreach ($tree->getNodes() as $node) {
            $markup .= $this->renderBlocks($node, $indent + 1);
        }

        if ($tree->hasBlock()) {
            if (1 || $tree->hasNodes()) {
                $markup .= $spaces . '</div>' . "\n";
            }
        }

        if (1 === $indent) {
            $markup .= '</div>' . PHP_EOL;
        }

        return $markup;
    }

    /**
     * @param string $text
     * @param bool $allowQuotes
     *
     * @return string
     */
    public static function escape($text, $allowQuotes = false)
    {
        return parent::escape($text, $allowQuotes);
    }

    /**
     * @param array $blocks
     *
     * @return BlockTreeNode
     */
    protected function buildBlockTree(array $blocks)
    {
        $tree = new BlockTreeNode();
        $currentNode = $tree;
        $previousLevel = 0;

        foreach ($blocks as $index => $block) {
            // When a h1 ~ h2 block is found, create a new tree node
            if (preg_match('/^h[1-6]$/', $block['element']['name'])) {
                $level = (int) substr($block['element']['name'], 1, 1);
                $text = $this->renderElement($block['element']);
                $text = preg_match('/<h[1-6][^>]*>(.*)<\/h[1-6]>$/', $text, $matches) ? $matches[1] : '';

                $idPrefix = $this->getOption('block_id_prefix');
                $id = $idPrefix . ++$this->blockCount . '-' . md5($text);

                $newNode = (new BlockTreeNode())
                    ->setBlock($block)
                    ->setId($id)
                    ->setIdPrefix($idPrefix)
                    ->setLevel($level)
                    ->setText($text);

                if ($level < $previousLevel) {
                    while ($currentNode->hasParent()) {
                        $currentNode = $currentNode->getParent();

                        if ($level > $currentNode->getLevel()) {
                            $previousLevel = $currentNode->getLevel();
                            break;
                        }
                    }
                }
                if ($level > $previousLevel) {
                    $newNode->setParent($currentNode);
                    $currentNode->addNode($newNode);
                    $currentNode = $newNode;
                } elseif ($level === $previousLevel) {
                    $newNode->setParent($currentNode->getParent());
                    $currentNode->getParent()->addNode($newNode);
                    $currentNode = $newNode;
                }

                $previousLevel = $level;
            } else {
                $newNode = (new BlockTreeNode())
                    ->setBlock($block)
                    ->setLevel($previousLevel + 1);

                $currentNode->addNode($newNode);
            }
        }

        return $tree;
    }

    /**
     * @param array $block
     *
     * @return string
     */
    protected function renderBlock(array $block)
    {
        if (!isset($block['hidden'])) {
            return isset($block['markup']) ? $block['markup'] : $this->element($block['element']);
        }

        return '';
    }

    /**
     * Overwrite original `lines` method
     *
     * @param array $lines
     *
     * @return string
     */
    protected function lines(array $lines)
    {
        return $this->renderBlocks($this->parseBlocks($lines));
    }

    /**
     * Overwrite original `blockFencedCode` method
     *
     * @param array $Line
     *
     * @return array
     */
    protected function blockFencedCode($Line)
    {
        if (!preg_match('/^[' . $Line['text'][0] . ']{3,}[ ]*([^`]+)?[ ]*$/', $Line['text'], $matches)) {
            return null;
        }

        $Element = array(
            'name' => 'div',
            'text' => '',
            'language' => '',
            'attributes' => array('class' => 'plaintext'),
        );

        if (isset($matches[1]))
        {
            /**
             * https://www.w3.org/TR/2011/WD-html5-20110525/elements.html#classes
             * Every HTML element may have a class attribute specified.
             * The attribute, if specified, must have a value that is a set
             * of space-separated tokens representing the various classes
             * that the element belongs to.
             * [...]
             * The space characters, for the purposes of this specification,
             * are U+0020 SPACE, U+0009 CHARACTER TABULATION (tab),
             * U+000A LINE FEED (LF), U+000C FORM FEED (FF), and
             * U+000D CARRIAGE RETURN (CR).
             */
            $language = substr($matches[1], 0, strcspn($matches[1], " \t\n\f\r"));

            $Element['attributes'] = array('class' => 'language-' . $language);
            $Element['language'] = $language;

            if (preg_match('/{(' . $this->regexAttribute . '+)}[ ]*$/', $matches[1], $matches2)) {
                foreach ($this->parseAttributeData($matches2[1]) as $name => $value) {
                    if ('class' === $name) {
                        $Element['attributes']['class'] .= ' ' . $value;
                    } else if ('id' === $name) {
                        $Element['attributes']['id'] = $value;
                    }
                }
            }

            $attributesPart = trim(substr($matches[1], strlen($language)));
            if (!empty($attributesPart)) {
                // Attributes part can be JSON format for other usages
                $attributes = @json_decode($attributesPart, true);

                if (is_array($attributes)) {
                    $Element['extra_attributes'] = $attributes;

                    if (!empty($attributes['class'])) {
                        $Element['attributes']['class'] = $attributes['class'];
                    }
                    if (!empty($attributes['id'])) {
                        $Element['attributes']['id'] = $attributes['id'];
                    }
                }
            }
        }

        return array(
            'char' => $Line['text'][0],
            'element' => array(
                'name' => 'div',
                'handler' => 'renderFencedCode',
                'text' => $Element,
            ),
        );
    }

    protected function blockFencedCodeComplete($Block)
    {
        if (isset($this->customCodeBlockRenderers[$Block['element']['text']['language']])) {
            $Block['element']['name'] = 'div';
            $Block['element']['handler'] = 'renderCustomCodeBlock';
        }

        return $Block;
    }

    protected function renderFencedCode(array $element)
    {
        return $this->getHighlighter()->highlight($element);
    }

    protected function renderCustomCodeBlock(array $element)
    {
        if (!empty($element['language'])
            && isset($this->customCodeBlockRenderers[$element['language']])) {
            $renderer = $this->customCodeBlockRenderers[$element['language']];

            return $renderer->render($element);
        }

        return '';
    }

    protected function blockCodeComplete($Block)
    {
        $block['element']['handler'] = 'renderElement';

        return $Block;
    }

    /**
     * Overwrite original `inlineCode` method
     *
     * @param array $Excerpt
     *
     * @return array|null
     */
    protected function inlineCode($Excerpt)
    {
        $marker = $Excerpt['text'][0];

        if (preg_match('/^('.$marker.'+)[ ]*(.+?)[ ]*(?<!'.$marker.')\1(?!'.$marker.')/s', $Excerpt['text'], $matches))
        {
            $name = 'code';
            $text = $matches[2];
            $text = preg_replace("/[ ]*\n/", ' ', $text);

            // Render with `{{variableName}}` markup
            if (preg_match('/^{{\$[a-zA-z0-9_]+}}$/', $text) && !empty($this->options['variable_replaces'])) {
                $name = 'span';
                $text = str_replace(
                    array_keys($this->options['variable_replaces']),
                    array_values($this->options['variable_replaces']),
                    $text
                );
            }

            return array(
                'extent' => strlen($matches[0]),
                'element' => array(
                    'name' => $name,
                    'text' => $text,
                ),
            );
        }

        return null;
    }
}
