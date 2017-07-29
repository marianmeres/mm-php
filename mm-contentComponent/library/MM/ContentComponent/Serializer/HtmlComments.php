<?php
/**
 * Created by PhpStorm.
 * User: mm
 * Date: 10/07/15
 * Time: 16:09
 */

namespace MM\ContentComponent\Serializer;

use MM\ContentComponent\Model;

/**
 * Class Markdown
 * @package MM\ContentComponent\Serializer
 *
 * This is a custom format, saving all k/v pairs
 * as magic strings (as custom html comments) except "main" key, which is just
 * appended as plain text.
 *
 * Intended for quick-n-dirty human easily readable/editable content files...
 * typically markdown files
 */
class HtmlComments extends AbstractSerializer
{
    /**
     * @var string
     */
    public $mainKeyName = 'main';

    /**
     * @param Model $model
     * @param array $options
     * @return mixed
     */
    public function serialize(Model $model, array $options = array())
    {
        $data = $model->toArray();
        $main = $meta = '';

        foreach ($data as $k => $v) {
            if ($k == $this->mainKeyName) {
                $main = trim($v);
            } else {
                // embed meta as html comments
                $meta .= "<!--:$k\n$v\n-->\n\n";
            }
        }

        return "$meta\n$main\n";
    }

    /**
     * @param $data
     * @param array $options
     * @return array
     */
    public function unserialize($data, array $options = array())
    {
        $_data = [];

        // cut out embeded meta
        $data = preg_replace_callback(
            "/(<!--:(\w+)\s(.*)-->)/sU", // s - multiline, U - ungreedy
            function($m) use (&$_data) {$_data[$m[2]] = trim($m[3]); return '';},
            $data
        );

        // save rest as "main"
        $_data[$this->mainKeyName] = trim($data);

        return [
            '_data' => $_data,
            '_attrs' => [] // attrs not supported here
        ];
    }

}