<?php
/**
 * @author Marian Meres
 */
namespace MM\Util;

class Html
{
    /**
     * toString helper
     *
     * Utilitka vyskladavajuca tag s atributmi. Defaultne escapuje
     * via htmlspecialchars. Bude escapuje vsetko (kluce, hodnoty, value) alebo
     * nic.
     *
     * @param $name
     * @param null $attribs
     * @param null $value
     * @param bool $close
     * @param bool $escape
     * @return string
     */
    public static function renderTag($name, $attribs = null, $value = null,
                                     $close = true, $escape = true)
    {
        $tag  = "<$name";
        $attr = $attribs;

        // iba pole atributov budeme procesovat, ostatne nechavame as is
        if (is_array($attribs)) {
            $attr = '';
            foreach ($attribs as $key => $val) {
                $key = $escape
                     ? htmlspecialchars($key, ENT_NOQUOTES, 'UTF-8') : $key;
                $val = $escape
                     ? htmlspecialchars($val, ENT_NOQUOTES, 'UTF-8') : $val;
                $quote = false !== strpos($val, '"') ? "'" : '"';
                $attr .= " $key=$quote$val$quote";
            }
        }

        if ('' != ($_attr = trim($attr))) {
            $tag .= " $_attr";
        }

        // value
        $value = (string) $value;
        $value = $escape
               ? htmlspecialchars($value, ENT_NOQUOTES, 'UTF-8') : $value;

        if ('' == $value && $close) {
            if ($name == 'textarea') { // something else?
                $tag .= "></$name>";
            } else {
                $tag .= "/>";
            }
        } else if ('' != $value && $close) {
            $tag .= ">$value</$name>";
        } else { // no close
            $tag .= ">$value";
        }

        return $tag;
    }
}