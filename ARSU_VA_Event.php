<?php

class ARSU_VA_Event
{
    public function process_event($event, $userid, $handle, $cookieid, $params)
    {
        if ($event === 'a_post' || $event === 'a_edit') {
            $postid = $params['postid'];
            $isVideo = qa_post_text('arsu_va_is_video') ? '1' : '0';
            qa_db_postmeta_set($postid, ARSU_VA_Constants::META_IS_VIDEO_ANSWER, $isVideo);
        }
    }
}
