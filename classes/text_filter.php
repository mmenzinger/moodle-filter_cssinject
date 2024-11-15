<?php
namespace filter_cssinject;

defined('MOODLE_INTERNAL') || die;


if (class_exists('\core_filters\text_filter')) {
    class_alias('\core_filters\text_filter', 'base_text_filter');
} else {
    // compatibility for moodle 4.4
    class_alias('\moodle_text_filter', 'base_text_filter');
}

class text_filter extends \base_text_filter {
    #[\Override]
    public function filter($text, array $options = []): string {
        // Replace [!style: ...!]
        $pattern = '/(\s*<p>)?\s*\[\!(style|class|page):([^\]]+)\!\]\s*(<\/p>\s*)?/';
        $style = "";
        $class = "";
        $page = "";

        if (preg_match_all($pattern, $text, $matches)) {
            for($i = 0; $i < count($matches[0]); $i++) {
                $type = $matches[2][$i];
                $css = $matches[3][$i];
                $css = preg_replace('/[\x{202F}\\x{00A0}]/u', "", $css); // remove no break spaces
                $css = preg_replace('/<\/?\s*\w+\s*>/', "", $css); // remove html tags
                if($type == "style"){
                    $style .= $css;
                }
                else if($type == "class"){
                    $class .= $css;
                }
                else if($type == "page"){
                    $page .= $css;
                }
            }
        }

        if($style || $class || $page){
            $text = preg_replace($pattern, "", $text);
        }
        if($style || $class){
            $text = "<div style=\"$style\" class=\"$class\">$text</div>";
        }
        if($page){
            $text = "<style>$page</style>".$text;
        }

        return $text;
    }
}