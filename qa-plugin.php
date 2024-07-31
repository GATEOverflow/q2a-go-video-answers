<?php

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

require_once 'ARSU_VA_Constants.php';

/**
 * @return ARSU_VA_Plugin
 */
function arsu_va()
{
    global $qa_modules;

    return $qa_modules['process']['ARSU_VA Plugin']['object'];
}

qa_register_plugin_module('process', 'ARSU_VA_Plugin.php', 'ARSU_VA_Plugin', 'ARSU_VA Plugin');

qa_register_plugin_layer('layers/ARSU_VA_AnswerCategorizer.php', 'ARSU_VA Answer Categorizer Layer');

qa_register_plugin_phrases(ARSU_VA_Constants::DIR_LANG . DIRECTORY_SEPARATOR . ARSU_VA_Constants::PLUGIN_ID . '_*.php', ARSU_VA_Constants::PLUGIN_ID);
