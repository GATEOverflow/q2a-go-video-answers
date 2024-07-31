<?php

class ARSU_VA_Util
{
    private $pluginPath;
    private $urlToRoot;

    private $pluginId;
    private $publicDir;

    private $version;

    public function __construct($directory, $urlToRoot, $pluginId, $publicDir)
    {
        $this->pluginPath = $directory;
        $this->urlToRoot = $urlToRoot;

        $this->pluginId = $pluginId;
        $this->publicDir = $publicDir;
    }

    public function getPublicUrlToRoot($path = '')
    {
        return $this->urlToRoot . $this->publicDir . '/' . $path;
    }

    public function getPublicDirectoryPath($path = '')
    {
        return $this->pluginPath . $this->publicDir . $path;
    }

    public function lang($id, $parameters = array())
    {
        $result = qa_lang($this->pluginId . '/' . $id);

        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        return empty($parameters) ? $result : $this->fillPlaceholders($result, $parameters);
    }

    public function langHtml($id, $parameters = array())
    {
        $result = qa_html($this->lang($id));

        if (!is_array($parameters)) {
            $parameters = array($parameters);
        }

        return empty($parameters) ? $result : $this->fillPlaceholders($result, $parameters);
    }

    /** All ^X, where X is an integer number greater than or equal to 1, are replaced with the appropriate parameter
     * documented before the constant.
     * All ^X:Y:Z:, where X is the same as above and Y and Z are anything but colons (:) sequences of characters.
     * They are replaced applying the following algorithm: If X is equal to 1 then display Y. Otherwise, display Z.
     * If $fillValues can contain arrays with 'value' and 'formatted_value' keys. If the value needs to be output in a
     * formatted way (e.g.: '1,232.05'), then that value would go in the 'formatted_value' key while the actual value
     * (e.g.: 1232) used for singular/plural form comparison would go in the 'value' key.
     */
    private function fillPlaceholders($text, $fillValues, $placeHolder = '^')
    {
        if (!is_array($fillValues)) {
            $fillValues = array($fillValues);
        }

        $placeHolder = preg_quote($placeHolder);
        foreach ($fillValues as $index => $fillValue) {
            if (is_array($fillValue)) {
                $actualValue = $fillValue['value'];
                $formattedValue = $fillValue['formatted_value'];
            } else {
                $actualValue = $fillValue;
                $formattedValue = $fillValue;
            }
            $indexedPlaceHolder = $placeHolder . ($index + 1); // EG: ^1
            $text = preg_replace('/' . $indexedPlaceHolder . '(?!\:(?:[^\:]*?\:){2})/', $formattedValue, $text);
            $text = preg_replace('/' . $indexedPlaceHolder . '\:([^\:]*?)\:([^\:]*?)\:/', $actualValue == 1 ? '$1' : '$2', $text);
        }

        return $text;
    }

    public function getSetting($id, $defaultOnEmptyString = null)
    {
        $value = qa_opt($this->addPrefix($id));

        return $value === '' && isset($defaultOnEmptyString) ? $defaultOnEmptyString : $value;
    }

    public function setSetting($id, $value)
    {
        qa_opt($this->addPrefix($id), $value);
    }

    public function addPrefix($text, $separator = '_')
    {
        return $this->pluginId . $separator . $text;
    }

    public function removePrefix($text, $separator = '_')
    {
        $prefix = $this->addPrefix('', $separator);
        if (substr($text, 0, strlen($prefix)) === $prefix) {
            $text = substr($text, strlen($prefix));
        }

        return $text;
    }

    public function getSettingFromDb($id)
    {
        return qa_db_read_one_value(qa_db_query_sub(
            'SELECT `content` FROM `^options` ' .
            'WHERE `title` = $',
            $this->addPrefix($id)
        ), true);
    }

    // $qa_content manipulation

    private function fetchVersion()
    {
        if (is_null($this->version)) {
            $metadata = (new Q2A_Util_Metadata())->fetchFromAddonPath($this->pluginPath);
            $this->version = isset($metadata['version']) ? $metadata['version'] : '';
        }
    }

    /* Adds the CSS file to the $qa_content array */
    public function addCssFileAsReference(&$qa_content, $cssFile, $local = true, $addVersion = true)
    {
        if (!isset($qa_content['css_src'])) {
            $qa_content['css_src'] = array();
        }

        $cssUrl = $local ? $this->getPublicUrlToRoot($cssFile) : $cssFile;

        if ($addVersion) {
            $this->fetchVersion();
            if (!empty($this->version)) {
                $cssUrl .= '?' . $this->version;
            }
        }

        $qa_content['css_src'][] = qa_html($cssUrl);
    }

    /** Adds the CSS file inline to the $qa_content array.
     *
     * @param array $qa_content
     * @param string $fileName
     */
    public function addCssFileInline(array &$qa_content, $fileName)
    {
        $html = $this->readPublicFileContent($fileName);
        $html = '<style>' . $html . '</style>';

        $this->appendToHeadScript($qa_content, $html);
    }

    /** Adds the JS file name to the $qa_content array. If $local is true the urlToRoot is added in the
     *  URL. If $body is true then it is added to the body footer. Otherwise, it is added to the head */
    public function addJsFileAsReference(&$qa_content, $fileName, $local = true, $body = true, $addVersion = true)
    {
        if ($addVersion) {
            $this->fetchVersion();
            if (!empty($this->version)) {
                $fileName .= '?' . $this->version;
            }
        }

        $tag = $this->getJavascriptTag($fileName, $local);
        if ($body) {
            $this->appendToContentBodyFooter($qa_content, $tag);
        } else {  // Assumed head
            $this->appendToHeadScript($qa_content, $tag);
        }
    }

    /** Adds the JS file inline to the $qa_content array. If $body is true then it is added to the body
     * footer. Otherwise, it is added to the head.
     *
     * @param array $qa_content
     * @param string $fileName
     * @param bool $body
     */
    public function addJsFileInline(array &$qa_content, $fileName, $body = true)
    {
        $html = $this->readPublicFileContent($fileName);

        $html = '<script>' . $html . '</script>';
        if ($body) {
            $this->appendToContentBodyFooter($qa_content, $html);
        } else {  // Assumed head
            $this->appendToHeadScript($qa_content, $html);
        }
    }

    /**
     * Append content to the head tag.
     *
     * @param array $qa_content
     * @param string $html
     */
    public function appendToHead(&$qa_content, $html)
    {
        if (!isset($qa_content['head_lines'])) {
            $qa_content['head_lines'] = array();
        }

        $qa_content['head_lines'][] = $html;
    }

    /* Appends a piece of HTML to the body_footer string in the content array */
    public function appendToHeadScript(array &$qa_content, $html)
    {
        if (!isset($qa_content['script'])) {
            $qa_content['script'] = array();
        }
        $qa_content['script'][] = $html;
    }

    /* Appends a piece of HTML to the body footer in the content array */
    public function appendToContentBodyFooter(&$qa_content, $html)
    {
        if (!isset($qa_content['body_footer'])) {
            $qa_content['body_footer'] = '';
        }
        $qa_content['body_footer'] .= $html;
    }

    /* Appends a piece of HTML to the body header array in the content array */
    public function appendToContentBodyHeader(&$qa_content, $html)
    {
        if (!isset($qa_content['body_header'])) {
            $qa_content['body_header'] = '';
        }
        $qa_content['body_header'] .= $html;
    }

    /**
     * @param array $qa_content
     * @param string $file
     * @param bool $checkHead
     * @param bool $checkHeadScript
     * @param bool $checkBodyHeader
     * @param bool $checkBodyFooter
     *
     * @return bool
     */
    public function isJsFileReferenced(&$qa_content, $file, $checkHead = true, $checkHeadScript = true, $checkBodyHeader = true, $checkBodyFooter = true)
    {
        if ($checkBodyHeader && isset($qa_content['body_header']) && (strpos($qa_content['body_header'], $file) !== false)) {
            return true;
        }

        if ($checkBodyFooter && isset($qa_content['body_footer']) && (strpos($qa_content['body_footer'], $file) !== false)) {
            return true;
        }

        if ($checkHead && isset($qa_content['head_lines'])) {
            foreach ($qa_content['head_lines'] as $line) {
                if (strpos($line, $file) !== false) {
                    return true;
                }
            }
        }

        if ($checkHeadScript && isset($qa_content['script'])) {
            foreach ($qa_content['script'] as $line) {
                if (strpos($line, $file) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array $qa_content
     * @param string $file
     *
     * @return bool
     */
    public function isCssFileReferenced(&$qa_content, $file)
    {
        if (isset($qa_content['css_src'])) {
            foreach ($qa_content['css_src'] as $line) {
                if (strpos($line, $file) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /* Returns a script HTML tag with the given url */
    public function getJavascriptTag($url, $local)
    {
        $location = $local ? $this->getPublicUrlToRoot($url) : $url;

        return sprintf('<script src="%s"></script>', $location);
    }

    /**
     * Return the content of a file relative to the public directory.
     *
     * @param string $path
     *
     * @return string
     */
    public function readPublicFileContent($path)
    {
        $jsFilePath = $this->getPublicDirectoryPath('/' . $path);

        return file_get_contents($jsFilePath);
    }

    /**
     * Return a numeric parameter from the $_GET array or null if not present or not numeric.
     *
     * @param string $name
     * @param mixed $defaultValue
     *
     * @return mixed
     */
    public function getParamNumeric($name = 'id', $defaultValue = null)
    {
        $value = qa_get($name);

        return isset($value) && is_numeric($value) ? (int)$value : $defaultValue;
    }

    /**
     * Return if there is a widget with the given class name being displayed
     *
     * @param array $qa_content
     * @param string $widgetClassName
     *
     * @return bool
     */
    public function isWidgetForClassnameDisplayed($qa_content, $widgetClassName)
    {
        if (is_array($qa_content)) {
            foreach ($qa_content as $key => $value) {
                if (is_array($value)) {
                    $result = $this->isWidgetForClassnameDisplayed($value, $widgetClassName);
                    if ($result) {
                        return true;
                    }
                } else {
                    if (is_object($value) && get_class($value) === $widgetClassName) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param mixed $data
     * @param bool $encodeHtml
     * @param bool $numericCheck
     *
     * @return string
     */
    public function jsonEncode($data, $encodeHtml = false, $numericCheck = true)
    {
        $result = json_encode($data, $numericCheck ? JSON_NUMERIC_CHECK : 0);

        return $encodeHtml ? htmlentities($result) : $result;
    }

    /**
     * @param string $data
     *
     * @return mixed
     */
    public function jsonDecode($data)
    {
        return json_decode($data, true);
    }
}
