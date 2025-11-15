<?php

/**
 * SMSTemplateRenderer
 * Central helper to render SMS templates with {placeholders}
 */
class SMSTemplateRenderer
{
    /**
     * Render a template string by replacing {placeholders} with provided data.
     *
     * @param string $content Template content containing placeholders like {name}
     * @param array $data Associative array of key => value
     * @return string
     */
    public static function render(string $content, array $data): string
    {
        if (empty($data)) {
            return $content;
        }

        $search = [];
        $replace = [];

        foreach ($data as $key => $value) {
            $search[]  = '{' . $key . '}';
            $replace[] = (string)$value;
        }

        return str_replace($search, $replace, $content);
    }
}
