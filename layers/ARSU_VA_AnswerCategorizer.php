<?php

class qa_html_theme_layer extends qa_html_theme_base
{
    const YOUTUBE_REGEX = '/(?:https?:\/\/)?(?:www\.)?' .
    '(?:youtube\.com\/' .
    '(?:' .
    '(?:v|embed|live)\/|' .
    '\S*?[?&]v=' .
    ')|' .
    'youtu\.be\/)' .
    '([a-zA-Z0-9_-]+)/';
    const ANCHOR_REMOVE_REGEX = '/<a href="([^"]*)".*?<\/a>/';

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

        // Batch-load video meta for all answers
        $postIds = [];
        foreach ($this->content['a_list']['as'] as &$answer) {
            $postIds[] = (int)$answer['raw']['postid'];
        }
        $videoMetas = $this->getVideoMetas($postIds);

        $answers = [];
        foreach ($this->content['a_list']['as'] as &$answer) {
            $postid = (int)$answer['raw']['postid'];
            $domId = 'a' . $postid;
            $answers[$domId] = (($videoMetas[$postid] ?? '') === '1') ? 'video' : 'standard';
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

    /**
     * Batch-load video answer meta for given post IDs.
     */
    private function getVideoMetas($postIds)
    {
        if (empty($postIds)) return [];

        $placeholders = implode(',', array_fill(0, count($postIds), '#'));
        $args = array_merge(
            ["SELECT postid, content FROM ^postmetas WHERE title=$ AND postid IN ($placeholders)",
             ARSU_VA_Constants::META_IS_VIDEO_ANSWER],
            $postIds
        );
        $rows = qa_db_read_all_assoc(call_user_func_array('qa_db_query_sub', $args));
        $metas = [];
        foreach ($rows as $row) {
            $metas[(int)$row['postid']] = $row['content'];
        }
        return $metas;
    }

    /**
     * Add "Mark as video answer" checkbox to the answer form.
     * Hidden by default; JS shows it when a YouTube link is detected in the editor.
     */
    public function a_form($a_form)
    {
        if (isset($a_form['fields'])) {
            $isEditing = isset($a_form['buttons']['save']);
            $currentValue = '';

            if ($isEditing && isset($a_form['hidden']['a_postid'])) {
                $postid = (int)$a_form['hidden']['a_postid'];
                $currentValue = qa_db_postmeta_get($postid, ARSU_VA_Constants::META_IS_VIDEO_ANSWER);
            }

            $checked = ($currentValue === '1') ? ' checked' : '';
            // Show immediately only if already marked as video (editing); otherwise hidden until JS detects a link
            $hideStyle = $checked ? '' : 'display:none;';

            $a_form['fields']['arsu_va_is_video'] = [
                'type' => 'custom',
                'html' => '<div class="arsu-va-toggle" style="' . $hideStyle . 'margin:8px 0;padding:8px 12px;border:1px solid var(--ex-border,#e0e0e0);border-radius:8px;background:var(--ex-bg-alt,#fafafa)">'
                    . '<label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:0.9em">'
                    . '<input type="checkbox" name="arsu_va_is_video" value="1"' . $checked . ' style="width:16px;height:16px">'
                    . '<span>&#127909; Mark as video answer</span>'
                    . '</label>'
                    . '<div style="font-size:0.78em;color:var(--ex-text-muted,#757575);margin-top:2px;padding-left:24px">Video answers are shown in a separate tab for easy access</div>'
                    . '</div>',
            ];

            // Inject JS once to auto-show/hide the toggle when a video link is detected
            if (!isset($this->content['script_arsu_va_detect'])) {
                $this->content['script_arsu_va_detect'] = true;
                arsu_va()->util()->appendToContentBodyFooter($this->content,
                    '<script>' . $this->getVideoDetectJs() . '</script>');
            }
        }

        parent::a_form($a_form);
    }

    /**
     * JS that monitors answer editor content for YouTube links and shows/hides the checkbox.
     */
    private function getVideoDetectJs()
    {
        return <<<'JS'
(function(){
var ytRe=/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:(?:v|embed|live)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]+)/;
function initForm(form){
    var tog=form.querySelector('.arsu-va-toggle');
    if(!tog) return;
    var ta=form.querySelector('textarea[name$="_content"],textarea[name="a_content"]');
    if(!ta) return;
    var cb=tog.querySelector('input[type="checkbox"]');
    function chk(){
        var val=ta.value||'';
        if(typeof CKEDITOR!=='undefined'){
            for(var id in CKEDITOR.instances){
                var inst=CKEDITOR.instances[id];
                if(form.contains(document.getElementById(id))||form.querySelector('[name="'+id+'"]')){
                    try{val=inst.getData()||val;}catch(e){}
                }
            }
        }
        var has=ytRe.test(val);
        tog.style.display=has?'':'none';
        if(!has) cb.checked=false;
    }
    ta.addEventListener('input',chk);
    ta.addEventListener('change',chk);
    ta.addEventListener('paste',function(){setTimeout(chk,100);});
    var pt=setInterval(function(){
        if(typeof CKEDITOR!=='undefined'){
            for(var id in CKEDITOR.instances){
                var inst=CKEDITOR.instances[id];
                if(form.contains(document.getElementById(id))||form.querySelector('[name="'+id+'"]')){
                    inst.on('change',chk);
                    clearInterval(pt);chk();return;
                }
            }
        }
    },500);
    setTimeout(function(){clearInterval(pt);},10000);
    chk();
}
function run(){document.querySelectorAll('form[method="post"]').forEach(initForm);}
if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',run); else run();
})();
JS;
    }

    private function replaceLinkWithIframe($text)
    {
        $replacement = '<div class="arsu_va_iframe-container"><iframe src="https://www.youtube.com/embed/$1" class="arsu_va_iframe" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe></div>';

        return preg_replace(self::YOUTUBE_REGEX, $replacement, $text);
    }

    private function replaceAnchorWithText($text)
    {
        return preg_replace(self::ANCHOR_REMOVE_REGEX, '$1', $text);
    }

    public function a_list_item($a_item)
    {
        $postid = (int)$a_item['raw']['postid'];
        $metaValue = qa_db_postmeta_get($postid, ARSU_VA_Constants::META_IS_VIDEO_ANSWER);
        $isVideoAnswer = ($metaValue === '1');

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
        if (qa_opt('show_url_links')) {
            $answer['content'] = $this->replaceAnchorWithText($answer['content']);
        }
        $answer['content'] = $this->replaceLinkWithIframe($answer['content']);
    }

}
