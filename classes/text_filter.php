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
        $class_prefix = 'cssinject_';
        $box_prefix = "${class_prefix}box_";

        $style = "";
        $class = "";
        $box = "";
        $page = "";
        if (preg_match_all($pattern, $text, $matches)) {
            for($i = 0; $i < count($matches[0]); $i++) {
                $type = $matches[2][$i];
                $css = $matches[3][$i];
                $css = preg_replace('/[\x{202F}\\x{00A0}]/u', '', $css); // remove no break spaces
                $css = preg_replace('/<\/?\s*\w+\s*>/', '', $css); // remove html tags

                if(in_array($type, ['style', 'class', 'page'])){
                    $$type .= $css;
                }
                else if($type == 'box'){
                    $css = preg_replace_callback('/\b(\w+)\b/', function($matches) use ($box_prefix) {
                        return $box_prefix . $matches[0];
                    }, $css);
                    $box .= $css;
                    if(!$box){ // enable box without classes
                        $box = ' ';
                    }
                }
            }
        }

        $text = preg_replace($pattern, '', $text);
        $text = preg_replace($demo_pattern, '[!$2:$3!]', $text);

        if($box){
            $text = "<div style=\"$style\" class=\"${box_prefix}container $box $class\">
                <div class=\"${box_prefix}area_header\"></div>
                <div class=\"${box_prefix}area_content\">$text</div>
            </div>";
        }
        else if($style || $class){
            $text = "<div style=\"$style\" class=\"$class\">$text</div>";
        }

        if($page){
            $text = "<style>$page</style>".$text;
        }

        return $text;
    }
}