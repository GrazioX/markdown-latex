<?php
/**
 * @copyright Copyright (c) 2014 Carsten Brandt
 * @license https://github.com/cebe/markdown/blob/master/LICENSE
 * @link https://github.com/cebe/markdown#readme
 */

namespace cebe\markdown\latex;

/**
 * Markdown parser for github flavored markdown.
 *
 * @author Carsten Brandt <mail@cebe.cc>
 */
class GithubMarkdown extends Markdown
{
	/**
	 * @var boolean whether to interpret newlines as `<br />`-tags.
	 * This feature is useful for comments where newlines are often meant to be real new lines.
	 */
	public $enableNewlines = false;

	/**
	 * @inheritDoc
	 */
	protected function inlineMarkers()
	{
		$markers = [
			'http'  => 'parseUrl',
			'ftp'   => 'parseUrl',
			'~~'    => 'parseStrike',
		];
		return array_merge(parent::inlineMarkers(), $markers);
	}


	// block parsing


	/**
	 * @inheritDoc
	 */
	protected function identifyLine($lines, $current)
	{
		if (isset($lines[$current]) && strncmp($lines[$current], '```', 3) === 0) {
			return 'fencedCode';
		}
		return parent::identifyLine($lines, $current);
	}

	/**
	 * Consume lines for a fenced code block
	 */
	protected function consumeFencedCode($lines, $current)
	{
		// consume until ```
		$block = [
			'type' => 'code',
			'content' => [],
		];
		$line = rtrim($lines[$current]);
		$fence = substr($line, 0, $pos = strrpos($line, '`') + 1);
		$language = substr($line, $pos);
		if (!empty($language)) {
			$block['language'] = $language;
		}
		for ($i = $current + 1, $count = count($lines); $i < $count; $i++) {
			if (rtrim($line = $lines[$i]) !== $fence) {
				$block['content'][] = $line;
			} else {
				break;
			}
		}
		return [$block, $i];
	}


	// inline parsing


	/**
	 * Parses the strikethrough feature.
	 */
	protected function parseStrike($markdown)
	{
		if (preg_match('/^~~(.+?)~~/', $markdown, $matches)) {
			return [
				'\sout{' . $this->parseInline($matches[1]) . '}',
				strlen($matches[0])
			];
		}
		return [$markdown[0] . $markdown[1], 2];
	}

	/**
	 * Parses urls and adds auto linking feature.
	 */
	protected function parseUrl($markdown)
	{
		$pattern = <<<REGEXP
			/(?(R) # in case of recursion match parentheses
				 \(((?>[^\s()]+)|(?R))*\)
			|      # else match a link with title
				^(https?|ftp):\/\/(([^\s()]+)|(?R))+(?<![\.,:;\'"!\?\s])
			)/x
REGEXP;

		if (preg_match($pattern, $markdown, $matches)) {
			return [
				'\url{' . $matches[0] . '}',
				strlen($matches[0])
			];
		}
		return [substr($markdown, 0, 4), 4];
	}

	/**
	 * @inheritdocs
	 *
	 * Parses a newline indicated by two spaces on the end of a markdown line.
	 */
	protected function parsePlainText($text)
	{
		if ($this->enableNewlines) {
			return preg_replace("/(  \n|\n)/", '\\\\', $this->escapeLatex($text));
		} else {
			return parent::parsePlainText($text);
		}
	}
}
