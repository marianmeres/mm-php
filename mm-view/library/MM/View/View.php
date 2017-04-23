<?php
/**
 * @author Marian Meres
 */
namespace MM\View;

/**
 * Class View
 * @package MM\View
 */
class View extends ViewAbstract
{
    /**
     * @var mixed
     */
    public $__scriptIncludeReturn;

    /**
     * Trick to make clean scoped template. Taken from ZF1.
     */
    protected function _run()
    {
        // http://php.net/manual/en/function.include.php
        // ... It is possible to execute a return statement inside an included file
        // in order to terminate processing in that file and return to the script
        // which called it. Also, it's possible to return values from included files.
        // You can take the value of the include call as you would for a normal
        // function. ...
        //
        // Important here is the actual include... return value is saved only
        // for special cases, should they ever be needed
        $this->__scriptIncludeReturn = include func_get_arg(0);
    }

    /**
     * @param null $strings
     * @param string $method
     * @param null $escape
     * @return \MM\View\Helper\HeadTitle
     * @throws Exception
     */
    public function headTitle($strings = null, $method = 'append', $escape = null)
    {
        /** @var \MM\View\Helper\HeadTitle $helper */
        $name = 'HeadTitle';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($strings, $method, $escape);
    }

    /**
     * @param null $strings
     * @param string $method
     * @param null $escape
     * @return \MM\View\Helper\HeadScriptSrc
     * @throws Exception
     */
    public function headScriptSrc($strings = null, $method = 'append', $escape = null)
    {
        /** @var \MM\View\Helper\HeadScriptSrc $helper */
        $name = 'HeadScriptSrc';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($strings, $method, $escape);
    }

    /**
     * @param null $strings
     * @param string $method
     * @param null $escape
     * @return \MM\View\Helper\HeadScript
     * @throws Exception
     */
    public function headScript($strings = null, $method = 'append', $escape = null)
    {
        /** @var \MM\View\Helper\HeadScript $helper */
        $name = 'HeadScript';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($strings, $method, $escape);
    }

    /**
     * @param null $strings
     * @param string $method
     * @param null $escape
     * @return \MM\View\Helper\HeadCssSrc
     * @throws Exception
     */
    public function headCssSrc($strings = null, $method = 'append', $escape = null)
    {
        /** @var \MM\View\Helper\HeadCssSrc $helper */
        $name = 'HeadCssSrc';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($strings, $method, $escape);
    }

    /**
     * @param null $strings
     * @param string $method
     * @param null $escape
     * @return \MM\View\Helper\HeadCssSrcNonBlocking
     */
    public function HeadCssSrcNonBlocking($strings = null, $method = 'append', $escape = null)
    {
        /** @var \MM\View\Helper\HeadCssSrcNonBlocking $helper */
        $name = 'HeadCssSrcNonBlocking';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($strings, $method, $escape);
    }

    /**
     * @param null $strings
     * @param string $method
     * @param null $escape
     * @return \MM\View\Helper\HeadCss
     * @throws Exception
     */
    public function headCss($strings = null, $method = 'append', $escape = null)
    {
        /** @var \MM\View\Helper\HeadCss $helper */
        $name = 'HeadCss';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($strings, $method, $escape);
    }

    /**
     * @return Helper\Breadcrumbs
     */
    public function breadcrumbs()
    {
        /** @var \MM\View\Helper\Breadcrumbs $helper */
        $name = 'Breadcrumbs';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper;
    }

    /**
     * @param $strings
     * @return Helper\HtmlTagClass
     * @throws Exception
     */
    public function htmlTagClass($strings = null)
    {
        /** @var \MM\View\Helper\HtmlTagClass $helper */
        $name = 'HtmlTagClass';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($strings, 'append', null);
    }

    /**
     * @param $strings
     * @return Helper\BodyTagClass
     * @throws Exception
     */
    public function bodyTagClass($strings = null)
    {
        /** @var \MM\View\Helper\BodyTagClass $helper */
        $name = 'BodyTagClass';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($strings, 'append', null);
    }

    /**
     * @param null $href
     * @param null $target
     * @return Helper\HtmlBaseTag
     */
    public function htmlBaseTag($href = null, $target = null)
    {
        /** @var \MM\View\Helper\HtmlBaseTag $helper */
        $name = 'HtmlBaseTag';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($href, $target);
    }

    /**
     * @param null $href
     * @return Helper\LinkRelCanonical
     */
    public function linkRelCanonical($href = null)
    {
        /** @var \MM\View\Helper\LinkRelCanonical $helper */
        $name = 'LinkRelCanonical';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($href);
    }

    /**
     * @param null $href
     * @return Helper\LinkRelPrev
     */
    public function linkRelPrev($href = null)
    {
        /** @var \MM\View\Helper\LinkRelPrev $helper */
        $name = 'LinkRelPrev';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($href);
    }

    /**
     * @param null $href
     * @return Helper\LinkRelNext
     */
    public function linkRelNext($href = null)
    {
        /** @var \MM\View\Helper\LinkRelNext $helper */
        $name = 'LinkRelNext';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper->__invoke($href);
    }

    /**
     * @return Helper\LinkRel
     */
    public function linkRel()
    {
        /** @var \MM\View\Helper\LinkRel $helper */
        $name = 'LinkRel';
        $helper = $this->getHelper($name, "\MM\View\Helper\\$name");
        return $helper;
    }

    /**
     * @param null $name
     * @param null $content
     * @return Helper\MetaNameTags
     */
    public function metaNameTags($name = null, $content = null)
    {
        /** @var \MM\View\Helper\MetaNameTags $helper */
        $_name = 'MetaNameTags';
        $helper = $this->getHelper($_name, "\MM\View\Helper\\$_name");
        if ($name) {
            $helper->set($name, $content);
        }
        return $helper;
    }

    /**
     * @param null $propertyOrData
     * @param null $content
     * @param bool|true $overwrite
     * @return Helper\OpenGraphData
     */
    public function openGraphData($propertyOrData = null, $content = null, $overwrite = true)
    {
        /** @var \MM\View\Helper\OpenGraphData $helper */
        $_name = 'OpenGraphData';
        $helper = $this->getHelper($_name, "\MM\View\Helper\\$_name");
        if ($propertyOrData) {
            $helper->add($propertyOrData, $content, $overwrite);
        }
        return $helper;
    }

    /**
     * @param $url
     * @return string
     */
    public function canonicalize($url = null)
    {
        /** @var \MM\View\Helper\Canonicalize $helper */
        $_name = 'Canonicalize';
        $helper = $this->getHelper($_name, "\MM\View\Helper\\$_name");
        return $url ? $helper->__invoke($url) : $helper;
    }

    /**
     * @param $label
     * @param null $classes
     * @return Helper\ContainerOfStrings
     */
    public function cssClassFor($label, $classes = null)
    {
        /** @var \MM\View\Helper\LabeledContainersOfStrings $helper */
        $_name = 'LabeledContainersOfStrings';
        $helper = $this->getHelper($_name, "\MM\View\Helper\\$_name");
        return $helper->__invoke($label, $classes);
    }
}