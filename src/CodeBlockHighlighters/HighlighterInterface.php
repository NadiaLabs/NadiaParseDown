<?php

namespace NadiaParseDown\CodeBlockHighlighters;

interface HighlighterInterface
{
    /**
     * @param array $element
     *
     * @return string
     */
    public function highlight(array $element);
}
