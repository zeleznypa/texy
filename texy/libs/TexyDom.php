<?php

/**
 * Texy! universal text -> html converter
 * --------------------------------------
 *
 * This source file is subject to the GNU GPL license.
 *
 * @author     David Grudl aka -dgx- <dave@dgx.cz>
 * @link       http://texy.info/
 * @copyright  Copyright (c) 2004-2007 David Grudl
 * @license    GNU GENERAL PUBLIC LICENSE v2
 * @package    Texy
 * @category   Text
 * @version    $Revision$ $Date$
 */

// security - include texy.php, not this file
if (!defined('TEXY')) die();



/**
 * Texy DOM element base class
 */
abstract class TexyDomElement
{
    /** @var Texy */
    public $texy;

    /** @var array of TexyHtml */
    public $tags;



    public function __construct($texy)
    {
        $this->texy = $texy;
    }



    /**
     * Returns element's content
     * @return string
     */
    abstract protected function contentToHtml();



    /**
     * Converts to "HTML" string
     * @return string
     */
    public function toHtml()
    {
        $start = $end = '';
        if ($this->tags)
            foreach ($this->tags as $el) {
                $start .= $el->startTag();
                $end = $el->endTag() . $end;
            }
        return $start . $this->contentToHtml() . $end;
    }



    /**
     * Undefined property usage prevention
     */
    function __get($nm) { throw new Exception("Undefined property '" . get_class($this) . "::$$nm'"); }
    function __set($nm, $val) { $this->__get($nm); }

}









/**
 * This element represents array of TexyDomElement
 */
class TexyBlockElement extends TexyDomElement
{
    /** @var array of TexyDomElement */
    public $children = array();



    protected function contentToHtml()
    {
        $s = '';
        foreach ($this->children as $child)
            $s .= $child->toHtml();

        return $s;
    }



    /**
     * Parses text as block
     * @param string
     * @return void
     */
    public function parse($text)
    {
        $parser = new TexyBlockParser($this->texy);
        $this->children = $parser->parse($text);
    }

}










/**
 * This element represents one paragraph of text
 */
class TexyTextualElement extends TexyDomElement
{
    /** @var string */
    public $content = '';

    /** @var bool  is content already HTML encoded? */
    public $protect = FALSE;


    protected function contentToHtml()
    {
        if ($this->protect) {
            return $this->content;
        } else {
            $s = Texy::decode($this->content);

            foreach ($this->texy->getLineModules() as $module)
                $s = $module->linePostProcess($s);

            return Texy::encode($s);
        }
    }



    /**
     * Parses text as single line
     * @param string
     * @return void
     */
    public function parse($text)
    {
        $parser = new TexyLineParser($this->texy);
        $this->content = $parser->parse($text);
    }

}





/**
 * Generic paragraph / div / transparent created by TexyGenericBlock
 */
class TexyParagraphElement extends TexyTextualElement
{
}
