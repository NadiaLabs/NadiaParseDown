<?php

namespace NadiaParseDown\CodeBlockHighlighters;

use NadiaParseDown\NadiaParseDown;

class DefaultHighlighter implements HighlighterInterface
{
    public function highlight(array $element)
    {
        $codeAttributes = '';

        if (!empty($element['attributes'])) {
            foreach ($element['attributes'] as $key => $value) {
                $codeAttributes .= $key . '="' . NadiaParseDown::escape($value) . '"';
            }
        }

        $html =  '<pre>';
        $html .= '<code ' . $codeAttributes .'>';
        $html .= NadiaParseDown::escape($element['text'], true);
        $html .= '</code>';
        $html .= '</pre>';

        return $html;
    }
}
