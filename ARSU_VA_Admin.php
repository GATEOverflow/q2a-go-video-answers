<?php

class ARSU_VA_Admin
{
    public function option_default($option)
    {
        $prefix = ARSU_VA_Constants::PLUGIN_ID . '_';

        if ($option === $prefix . ARSU_VA_Constants::OPT_VIDEO_TEXT_MAX_LENGTH) {
            return ARSU_VA_Constants::OPT_VIDEO_TEXT_MAX_LENGTH_DEFAULT;
        }

        return null;
    }

    public function admin_form(&$qa_content)
    {
        $saved = false;
        $util = arsu_va()->util();

        if (qa_clicked('arsu_va_save')) {
            $util->setSetting(
                ARSU_VA_Constants::OPT_VIDEO_TEXT_MAX_LENGTH,
                (int)qa_post_text('arsu_va_video_text_max_length')
            );
            $saved = true;
        }

        return [
            'ok' => $saved ? 'Settings saved.' : null,
            'fields' => [
                [
                    'label' => 'Max non-video text length to auto-classify as video answer:',
                    'type' => 'number',
                    'tags' => 'name="arsu_va_video_text_max_length" min="0" max="500"',
                    'value' => $util->getSetting(
                        ARSU_VA_Constants::OPT_VIDEO_TEXT_MAX_LENGTH,
                        ARSU_VA_Constants::OPT_VIDEO_TEXT_MAX_LENGTH_DEFAULT
                    ),
                    'note' => 'If an answer contains a YouTube link and the remaining text is at most this many characters, it is auto-classified as a video answer. Users can still override this per answer.',
                ],
            ],
            'buttons' => [
                [
                    'label' => 'Save',
                    'tags' => 'name="arsu_va_save"',
                ],
            ],
        ];
    }
}
