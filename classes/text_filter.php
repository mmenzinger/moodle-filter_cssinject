<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Implementation of the filter_cssinject plugin.
 *
 * @package    filter_cssinject
 * @copyright  2025 Manuel Menzinger
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_cssinject;

defined('MOODLE_INTERNAL') || die;

if (class_exists('\core_filters\text_filter')) {
    class_alias('\core_filters\text_filter', 'base_text_filter');
} else {
    // compatibility for moodle 4.4
    class_alias('\moodle_text_filter', 'base_text_filter');
}

/**
 * A text filter to easily inject css into the content as well as providing
 * buildin styled boxes.
 *
 * This filter replaces special tags in the text with css styles and classes.
 * The following tags are supported:
 * - [!box: ...!] to apply css classes to a box around the whole content
 * - [!box-start: ...!] ... [!box-end!] create a limited box from start to end
 * - [!style: ...!] to apply css styles to the surrounding div
 * - [!class: ...!] to apply css classes to the surrounding div
 * - [!page: ...!] to apply css styles to the whole page
 * - [!: ...!] ... [!!] to apply css styles to a span surrounding the content
 *
 * @package    filter_cssinject
 * @copyright  2025 Manuel Menzinger
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends \base_text_filter {
    const PATTERN = '/(\s*<p>)?\s*\[\!(style|class|box|page):([^\]]*)\!\]\s*(<\/p>\s*)?/';
    // To be able to show the pattern, [\!...!] can be used and the \ will be removed.
    const DEMO_PATTERN = '/\[(\\\\)\!([^\]]*)\!\]/';
    // Replace [!box-start: ...!] ... [!box-end!] and also surrounding <p> tags.
    const NESTABLE_BLOCK_PATTERN = '/(\s*<p>)?\s*\[\!(style|class|box)-start(\d*):([^\]]*)\!\]\s*(<\/p>\s*)?(.*?)(\s*<p>)?\s*\[\!\2-end\3\!\]\s*(<\/p>\s*)?/s';
    // Replace [!:...!] ... [!!] with a <span style="..."> ... </span>.
    const NESTABLE_INLINE_PATTERN = '/\[\!(\d*):([^\]]*)\!\](.*?)\[\!\1\!\]/s';
    const CLASS_PREFIX = 'cssinject_';
    const BOX_PREFIX = "cssinject_box_";

    #[\Override]
    public function filter($text, array $options = []): string {
        // Replace [!style: ...!] and also surrounding <p> tags.
        // $pattern = '/(\s*<p>)?\s*\[\!(style|class|box|page):([^\]]*)\!\]\s*(<\/p>\s*)?/';
        // // To be able to show the pattern, [\!...!] can be used and the \ will be removed.
        // $demo_pattern = '/\[(\\\\)\!([^\]]*)\!\]/';
        // // Replace [!box-start: ...!] ... [!box-end!] and also surrounding <p> tags.
        // $nestable_block_pattern = '/(\s*<p>)?\s*\[\!(style|class|box)-start(\d*):([^\]]*)\!\]\s*(<\/p>\s*)?(.*?)(\s*<p>)?\s*\[\!\2-end\3\!\]\s*(<\/p>\s*)?/s';
        // // Replace [!:...!] ... [!!] with a <span style="..."> ... </span>.
        // $nestable_inline_pattern = '/\[\!(\d*):([^\]]*)\!\](.*?)\[\!\1\!\]/s';
        // $class_prefix = 'cssinject_';
        // $box_prefix = "{$class_prefix}box_";

        $style = "";
        $class = "";
        $box = "";
        $page = "";

        // Extract css for style, class, box and page from text.
        $text = preg_replace_callback(self::PATTERN, function($matches) use (&$box, &$style, &$class, &$page) {
            $type = $matches[2];
            $css = $matches[3];
            $css = preg_replace('/[\x{202F}\\x{00A0}]/u', '', $css); // Remove no break spaces.
            $css = preg_replace('/<\/?\s*\w+\s*>/', '', $css); // Remove html tags.

            if(in_array($type, ['style', 'class', 'page'])){
                if($type == 'style'){
                    $css = $this->parseCssAbbreviations($css);
                }
                $$type .= $css;
                return '';
            }
            else if($type == 'box'){
                $css = preg_replace_callback('/\b(\w+)\b/', function($matches) {
                    return self::BOX_PREFIX . $matches[0];
                }, $css);
                $box .= $css;
                if(!$box){ // Enable box without classes.
                    $box = ' ';
                }
                return '';
            }
            else {
                // Return the original text if the type is not recognized.
                return $matches[0];
            }
        }, $text);

        $text = $this->replaceNestableBlockElements($text);
        $text = $this->replaceNestableInlineElements($text);

        // Correct demo paddern (remove \).
        $text = preg_replace(self::DEMO_PATTERN, '[!$2!]', $text);

        // Apply container with header and content if it is a box.
        if($box){
            $text = "<div style=\"{$style}\" class=\"".self::BOX_PREFIX."container {$box} {$class}\">
                <div class=\"".self::BOX_PREFIX."area_header\"></div>
                <div class=\"".self::BOX_PREFIX."area_content\">{$text}</div>
            </div>";
        }
        // Else simply apply style and class to surrounding div.
        else if($style || $class){
            $text = "<div style=\"{$style}\" class=\"{$class}\">{$text}</div>";
        }

        // Apply page css at the end of the text.
        if($page){
            $text .= "<style>{$page}</style>";
        }

        return $text;
    }

    private function parseCssAbbreviations($css) {
        $css = preg_replace('/\bb\b\s*;?/', 'font-weight:bold;', $css);
        $css = preg_replace('/\bi\b\s*;?/', 'font-style:italic;', $css);
        $css = preg_replace('/\bu\b\s*;?/', 'text-decoration:underline;', $css);
        $css = preg_replace('/\bs\b\s*;?/', 'text-decoration:line-through;', $css);
        $css = preg_replace('/\bc:([^;]+);?/', 'color:$1;', $css);
        $css = preg_replace('/\bbg:([^;]+);?/', 'background-color:$1;', $css);
        $css = preg_replace('/([:;\s])(\d+[^;]+);?/', '$1font-size:$2;', $css);
        return $css;
    }

    private function replaceNestableInlineElements($text) {
        return preg_replace_callback(self::NESTABLE_INLINE_PATTERN, function($matches) {
            $css = $this->parseCssAbbreviations($matches[2]);
            $content = $this->replaceNestableInlineElements($matches[3]);
            return "<span style=\"{$css}\">{$content}</span>";
        }, $text);
    }

    private function replaceNestableBlockElements($text) {
        return preg_replace_callback(self::NESTABLE_BLOCK_PATTERN, function($matches) {
            $content = $this->replaceNestableBlockElements($matches[6]);
            $css = $matches[4];
            $element = $matches[2];
            if($element == 'box'){
                $css = preg_replace_callback('/\b(\w+)\b/', function($matches) {
                    return self::BOX_PREFIX . $matches[0];
                }, $css);

                return "<div class=\"".self::BOX_PREFIX."container {$css}\">
                    <div class=\"".self::BOX_PREFIX."area_header\"></div>
                    <div class=\"".self::BOX_PREFIX."area_content\">{$content}</div>
                </div>";
            }
            else if($element == 'class'){
                return "<div class=\"{$css}\">{$content}</div>";
            }
            else if($element == 'style'){
                $css = $this->parseCssAbbreviations($css);
                return "<div style=\"{$css}\">{$content}</div>";
            }
            else {
                // Return the original text if the type is not recognized.
                return $matches[0];
            }
        }, $text);
    }
}