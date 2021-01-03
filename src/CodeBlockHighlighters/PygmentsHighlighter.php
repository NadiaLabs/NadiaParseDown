<?php

namespace NadiaParseDown\CodeBlockHighlighters;

use InvalidArgumentException;
use NadiaParseDown\NadiaParseDown;
use Symfony\Component\Process\Process;

/**
 * A PHP wrapper for Pygments, the Python syntax highlighter
 */
class PygmentsHighlighter implements HighlighterInterface
{
    /**
     * @var string
     */
    private $pygmentize;

    /**
     * @var array
     */
    private $options = [
        'encoding' => 'utf-8',
        'startinline' => true,
    ];

    /**
     * Constructor
     *
     * @param string $pygmentize The path to execute pygmentize command (e.g. /path/to/bin/pygmentize)
     * @param array $options The command options for pygmentize
     */
    public function __construct($pygmentize = 'pygmentize', $options = [])
    {
        $this->pygmentize = $pygmentize;
        $this->options = array_merge($this->options, $options);
    }

    public function highlight(array $element)
    {
        $options = $this->options;
        $attributes = '';

        if (!empty($element['language'])) {
            $options['linenos'] = 1;
            $attributes = 'class="pygments language-' . NadiaParseDown::escape($element['language']) . '"';
        }

        $html =  '<div ' . $attributes . '>';
        $html .= $this->pygmentize($element['text'], $element['language'], 'html', $options);
        $html .= '</div>';

        return $html;
    }

    /**
     * Highlight the input code
     *
     * @param string $code      The code to highlight
     * @param string $language  The name of the language (e.g.: php, html, ...)
     * @param string $formatter The name of the output formatter (e.g.: html, ansi, ...)
     * @param array  $options   An array of options
     *
     * @return string
     */
    public function pygmentize($code, $language, $formatter = 'html', $options = [])
    {
        $command = [$this->pygmentize];

        $language = empty($language) ? 'text' : $language;

        // Use customized lexers
        if (in_array($language, ['terminal'])) {
            $language = $this->getLexerFromFile('terminal.py', 'terminal');
        }

        if ($language) {
            $command[] = '-l';
            $command[] = $language;

            if (false !== strpos($language, ':')) {
                $command[] = '-x';
            }
        } else {
            $command[] = '-g';
        }

        if ($formatter) {
            $command[] = '-f';
            $command[] = $formatter;
        }

        if (count($options)) {
            foreach ($options as $key => $value) {
                $command[] = '-P';
                $command[] = sprintf('%s=%s', $key, $value);
            }
        }

        $process = new Process($command, null, null, $code);

        $process->run();

        if (!$process->isSuccessful()) {
            return $code;
        }

        return $process->getOutput();
    }

    /**
     * Gets a lexer from file
     *
     * @param string $filename Lexer filename (e.g. terminal.py)
     * @param string $lexer    Lexer language name (e.g. terminal)
     *
     * @return string
     */
    public function getLexerFromFile($filename, $lexer)
    {
        $filePath = __DIR__ . '/lexers/' . $filename;

        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('Lexer file "'. $filename .'" is not exists!');
        }

        $filePath = str_replace('\\', '/', realpath($filePath));
        $lexerMethod = ucfirst($lexer) . 'Lexer';

        return $filePath . ':' . $lexerMethod;
    }
}
