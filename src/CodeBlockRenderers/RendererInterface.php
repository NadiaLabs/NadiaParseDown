<?php

namespace NadiaParseDown\CodeBlockRenderers;

interface RendererInterface
{
    /**
     * @param array $element
     *
     * @return string
     */
    public function render(array $element);
}
