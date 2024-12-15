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
        // Replace [!style: ...!] and also surrounding <p> tags
        $pattern = '/(\s*<p>)?\s*\[\!(style|class|box|page):([^\]]*)\!\]\s*(<\/p>\s*)?/';
        // to be able to show the pattern, [\!style: ...!] can be used and the \ will be removed
        $demo_pattern = '/\[(\\\\)\!(style|class|box|page):([^\]]*)\!\]/';
        // Replace [!box-start: ...!] ... [!box-end!] and also surrounding <p> tags
        $inline_box_pattern = '/(\s*<p>)?\s*\[\!box-start:([^\]]*)\!\]\s*(<\/p>\s*)?(.*?)(\s*<p>)?\s*\[\!box-end\!\]\s*(<\/p>\s*)?/s';
        $class_prefix = 'cssinject_';
        $box_prefix = "${class_prefix}box_";

        $style = "";
        $class = "";
        $box = "";
        $page = "";
        // extract css for style, class, box and page from text
        $text = preg_replace_callback($pattern, function($matches) use ($box_prefix, &$box, &$style, &$class, &$page) {
            $type = $matches[2];
            $css = $matches[3];
            $css = preg_replace('/[\x{202F}\\x{00A0}]/u', '', $css); // remove no break spaces
            $css = preg_replace('/<\/?\s*\w+\s*>/', '', $css); // remove html tags

            if(in_array($type, ['style', 'class', 'page'])){
                $$type .= $css;
                return '';
            }
            else if($type == 'box'){
                $css = preg_replace_callback('/\b(\w+)\b/', function($matches) use ($box_prefix) {
                    return $box_prefix . $matches[0];
                }, $css);
                $box .= $css;
                if(!$box){ // enable box without classes
                    $box = ' ';
                }
                return '';
            }
            else {
                // return the original text if the type is not recognized
                return $matches[0];
            }
        }, $text);

        // replace inline box
        $text = preg_replace_callback($inline_box_pattern, function($matches) use ($box_prefix) {
            $content = $matches[4];
            $css = preg_replace_callback('/\b(\w+)\b/', function($matches) use ($box_prefix) {
                return $box_prefix . $matches[0];
            }, $matches[2]);
            return "<div class=\"${box_prefix}container $css\">
                <div class=\"${box_prefix}area_header\"></div>
                <div class=\"${box_prefix}area_content\">${content}</div>
            </div>";
        }, $text);

        // correct demo paddern (remove \)
        $text = preg_replace($demo_pattern, '[!$2:$3!]', $text);

        // apply container with header and content if it is a box
        if($box){
            $text = "<div style=\"$style\" class=\"${box_prefix}container $box $class\">
                <div class=\"${box_prefix}area_header\"></div>
                <div class=\"${box_prefix}area_content\">$text</div>
            </div>";
        }
        // else simply apply style and class to surrounding div
        else if($style || $class){
            $text = "<div style=\"$style\" class=\"$class\">$text</div>";
        }

        // apply page css at the end of the text
        if($page){
            $text .= "<style>$page</style>";
        }

        return $text;
    }
}