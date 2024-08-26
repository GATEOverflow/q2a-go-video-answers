<?php

class qa_html_theme_layer extends qa_html_theme_base
{
	const YOUTUBE_REGEX = '/(?:<a\s+[^>]*href=["\'](?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:embed|v|watch|live|shorts)?(?:\S*?[?&]v=|\S*?[?&]list=)?|youtu\.be\/)([a-zA-Z0-9_-]+)([?&]\S*)?["\'][^>]*>[^<]*<\/a>|(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:embed|v|watch|live|shorts)?(?:\S*?[?&]v=|\S*?[?&]list=)?|youtu\.be\/)([a-zA-Z0-9_-]+)([?&]\S*)?|<iframe.*?src=["\'](?:https?:)?\/\/(?:www\.)?(?:youtube\.com\/(?:embed|v|watch|live|shorts)?(?:\S*?[?&]v=|\S*?[?&]list=)?|youtu\.be\/)([a-zA-Z0-9_-]+)([?&]\S*)?["\'].*?<\/iframe>)/';

	/*const ANCHOR_REMOVE_REGEX = '/<a href="([^"]*)".*?<\/a>/';*/

	public function initialize()
	{
		parent::initialize();

		$this->categorizeAnswers();
	}

	private function categorizeAnswers()
	{
		if ($this->template !== 'question') {
			return;
		}
		if (empty($this->content['a_list']['as'])) {
			return;
		}

		require_once QA_INCLUDE_DIR . 'app/format.php';

		$answers = [];
		foreach ($this->content['a_list']['as'] as &$answer) {
			$isVideoAnswer = $this->isVideoAnswer($answer);

			$domId = 'a' . $answer['raw']['postid'];
			$answers[$domId] = $isVideoAnswer ? 'video' : 'standard';
		}

		$options = [
			'answers' => $answers,
			'lang' => [
				'standard_answers_tab_label' => arsu_va()->util()->lang(ARSU_VA_Constants::LANG_ID_STADARD_ANSWERS_TAB_LABEL),
				'video_answers_tab_label' => arsu_va()->util()->lang(ARSU_VA_Constants::LANG_ID_VIDEO_ANSWERS_TAB_LABEL),
			]
		];

		$jsonOptions = json_encode($options);

		$html = sprintf('<script>const arsu_va_options = %s;</script>', $jsonOptions);
		arsu_va()->util()->appendToContentBodyHeader($this->content, $html);

		$html = arsu_va()->util()->readPublicFileContent('video-split.min.js');
		arsu_va()->util()->appendToContentBodyFooter($this->content, sprintf('<script>%s</script>', $html));

		$html = arsu_va()->util()->readPublicFileContent('video-split.min.css');
		arsu_va()->util()->appendToHead($this->content, sprintf('<style>%s</style>', $html));
	}

	private function isVideoAnswer($answer)
	{
		$textContent = qa_viewer_text($answer['raw']['content'], $answer['raw']['format']);

		return qa_strlen($textContent) <= 300 && preg_match(self::YOUTUBE_REGEX, $textContent);
	}

	private function replaceLinkWithIframe($text)
	{
		// Define a replacement function
		$replacement = function ($matches) {

			// The video ID will be in $matches[1], $matches[3], or $matches[5]
			// The query parameters (like ?t=3200) will be in $matches[2], $matches[4], or $matches[6]
			$videoId = $matches[1] ?? $matches[3] ?? $matches[5];
			$queryParams = $matches[2] ?? $matches[4] ?? $matches[6];

			// Ensure the query parameters start with a valid character if they exist
			$queryParams = $queryParams ? '?' . ltrim($queryParams, '?&') : '';
			$queryParams = str_replace("?t=", "?start=", $queryParams); 
			return '<div class="arsu_va_iframe-container"><iframe src="https://www.youtube.com/embed/'.$videoId.$queryParams.'" class="arsu_va_iframe" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>';
		};

		// Use preg_replace_callback to replace each match with the custom format
		$result = preg_replace_callback(self::YOUTUBE_REGEX, $replacement, $text);
		return $result;
	}

	private function replaceAnchorWithText($text)
	{
		//return preg_replace(self::ANCHOR_REMOVE_REGEX, '$1', $text);
		return $text;
	}

	public function a_list_item($a_item)
	{
		$isVideoAnswer = $this->isVideoAnswer($a_item);
		if ($isVideoAnswer) {
			$this->performVideoHtmlReplacements($a_item);
		}

		$tagValue = $isVideoAnswer ? 'video' : 'standard';

		$a_item['tags'] ??= '';
		$a_item['tags'] .= sprintf(' data-answer-type="%s"', $tagValue);

		parent::a_list_item($a_item);
	}

	/**
	 * @param $answer
	 *
	 * @return mixed
	 */
	private function performVideoHtmlReplacements(&$answer)
	{
		if (qa_opt('show_url_links') and false) {
			$answer['content'] = $this->replaceAnchorWithText($answer['content']);
		}
		$answer['content'] = $this->replaceLinkWithIframe($answer['content']);
	}

}
