<?php

namespace NadiaParseDown\CodeBlockRenderers;

use Exception;
use NadiaParseDown\NadiaParseDown;

/**
 * Render html for ```code-blocks```
 *
 * Example:
 *
 * ```code-blocks
 * ~~~php
 * // app/config/routing.php
 * return [
 *   'blog_list' => [
 *     'path' => '/blog',
 *     'defaults' => [
 *       '_controller' => 'AppBundle:Blog:list'
 *     ],
 *   ],
 * ];
 * ~~~
 *
 * ~~~yaml
 * # app/config/routing.yml
 * blog_list:
 *   path:     /blog
 *   defaults: { _controller: AppBundle:Blog:list }
 * ~~~
 * ```
 *
 * @param array $element
 * @param NadiaParseDown $parseDown
 *
 * @return string
 */
class MultipleCodeBlocksRenderer implements RendererInterface
{
    const CODE_BLOCK_LANGUAGE = 'code-blocks';

    /**
     * @var NadiaParseDown
     */
    private $parseDown;

    /**
     * Parameters for each language
     *
     * Setup key "title" for displaying language title for each code block.
     *
     * Setup key "language" as code language name for code highlight parser.
     *
     * @var array
     */
    private $defaultLanguageParameters;

    /**
     * @var int Count all code blocks
     */
    private $count = 0;

    /**
     * TocTreeRenderer constructor.
     *
     * @param NadiaParseDown $parseDown
     * @param array $defaultLanguageParameters
     */
    public function __construct(NadiaParseDown $parseDown, array $defaultLanguageParameters = [])
    {
        $this->parseDown = $parseDown;
        $this->defaultLanguageParameters = $defaultLanguageParameters;
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
        $languageParameters = $this->defaultLanguageParameters;

        $lines = explode("\n", $element['text']);
        $tree = $this->parseDown->parseBlocks($lines);

        $index = 0;
        $list = '';

        foreach ($tree->getNodes() as $node) {
            $block = $node->getBlock();
            $language = $originalLanguage = $block['element']['text']['language'];
            $parameters = ['title' => strtoupper($language), 'language' => $language];
            $parameters = isset($languageParameters[$language]) ? $languageParameters[$language] : $parameters;

            if (!empty($block['element']['text']['extra_attributes'])) {
                if (!empty($block['element']['text']['extra_attributes']['title'])) {
                    $parameters['title'] = $block['element']['text']['extra_attributes']['title'];
                }
                if (!empty($block['element']['text']['extra_attributes']['language'])) {
                    $parameters['language'] = $block['element']['text']['extra_attributes']['language'];
                }
            }

            $id = 'code-block-' . ++$this->count . '-' . $index;
            $active = 0 === $index ? 'active' : '';
            $language = $parameters['language'];

            if (empty($block['element']['attributes']['class'])) {
                $block['element']['attributes']['class'] = '';
            }

            if (!empty($block['element']['text']['attributes']['class']) && $originalLanguage !== $language) {
                $class = $block['element']['text']['attributes']['class'];
                $class = str_replace('language-' . $originalLanguage, 'language-' . $language, $class);

                $block['element']['text']['attributes']['class'] = $class;
            }

            $block['element']['attributes']['id'] = $id;
            $block['element']['language'] = $parameters['language'];
            $block['element']['text']['language'] = $parameters['language'];

            $list .= '<li class="' . $active . '">';
            $list .= '<em><a href="#' . $id . '">' . NadiaParseDown::escape($parameters['title']) . '</a></em>';
            $list .= $this->parseDown->renderElement($block['element']);
            $list .= '</li>';

            ++$index;
        }

        $list .= "\n";

        $attributes = '';
        foreach ($element['attributes'] as $key => $value) {
            $attributes .= $key . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
        }

        return <<<HTML
<div {$attributes}>
    <ul>
        {$list}
    </ul>
</div>
HTML;
    }
}
