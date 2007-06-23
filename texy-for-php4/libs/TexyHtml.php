<?php

/**
 * This file is part of the Texy! formatter (http://texy.info/)
 *
 * Copyright (c) 2004-2007 David Grudl aka -dgx- (http://www.dgx.cz)
 *
 * @version  $Revision$ $Date$
 * @package  Texy
 */


// security - include texy.php, not this file
if (!class_exists('Texy')) die();




/** @var bool  use XHTML syntax? */
$GLOBALS['TexyHtml::$xhtml'] = TRUE;

/** @var array  replaced elements + br */
$GLOBALS['TexyHtml::$replacedTags'] = array('br'=>1,'button'=>1,'iframe'=>1,'img'=>1,'input'=>1,
    'object'=>1,'script'=>1,'select'=>1,'textarea'=>1,'applet'=>1,'embed'=>1,'canvas'=>1);

/** @var array  empty elements */
$GLOBALS['TexyHtml::$emptyTags'] = array('img'=>1,'hr'=>1,'br'=>1,'input'=>1,'meta'=>1,'area'=>1,
    'base'=>1,'col'=>1,'link'=>1,'param'=>1,'basefont'=>1,'frame'=>1,'isindex'=>1,'wbr'=>1,'embed'=>1);

/** @var array  %inline; elements */
$GLOBALS['TexyHtml::$inline'] = array('ins'=>1,'del'=>1,'tt'=>1,'i'=>1,'b'=>1,'big'=>1,'small'=>1,'em'=>1,
    'strong'=>1,'dfn'=>1,'code'=>1,'samp'=>1,'kbd'=>1,'var'=>1,'cite'=>1,'abbr'=>1,'acronym'=>1,
    'sub'=>1,'sup'=>1,'q'=>1,'span'=>1,'bdo'=>1,'a'=>1,'object'=>1,'img'=>1,'br'=>1,'script'=>1,
    'map'=>1,'input'=>1,'select'=>1,'textarea'=>1,'label'=>1,'button'=>1,
    'u'=>1,'s'=>1,'strike'=>1,'font'=>1,'applet'=>1,'basefont'=>1, // transitional
    'embed'=>1,'wbr'=>1,'nobr'=>1,'canvas'=>1, // proprietary
); /* class static property */

/**
 * HTML helper
 *
 * usage:
 *       $anchor = TexyHtml::el('a')->href($link)->setText('Texy');
 *       $el->attrs['class'] = 'myclass';
 *
 *       echo $el->startTag(), $el->endTag();
 *
 */
class TexyHtml
{
    /** @var string  element's name */
    var $name;

    /** @var array  element's attributes */
    var $attrs = array();

    /**
     * @var mixed  element's content
     *   array of TexyHtml - child nodes
     *   string - content as string (text-node)
     */
    var $children;

    /** @var bool  is element empty? */
    var $isEmpty;

    /**
     * Static factory
     * @param string element name (or NULL)
     * @param array element's attributes
     * @return TexyHtml
     */
    function el($name=NULL, $attrs=NULL) /* static */
    {
        $el = new TexyHtml;

        if ($name !== NULL)
            $el->setName($name);

        if ($attrs !== NULL) {
            if (!is_array($attrs)) {
                trigger_error('Attributes must be array.', E_USER_WARNING);
                return FALSE;
            }

            $el->attrs = $attrs;
        }

        return $el;
    }


    /**
     * Static factory for textual element
     * @param string
     * @return TexyHtml
     */
    function text($text) /* static */
    {
        $el = new TexyHtml;
        $el->setText($text);
        return $el;
    }


    /**
     * Changes element's name
     * @param string
     * @return TexyHtml  itself
     */
    function setName($name)
    {
        if ($name !== NULL && !is_string($name)) {
            trigger_error('Name must be string or NULL.', E_USER_WARNING);
            return FALSE;
        }

        $this->name = $name;
        $this->isEmpty = isset($GLOBALS['TexyHtml::$emptyTags'][$name]);
        return $this;
    }


    /**
     * Returns element's name
     * @return string
     */
    function getName()
    {
        return $this->name;
    }


    /**
     * Is element empty?
     * @param optional setter
     * @return bool
     */
    function isEmpty($val=NULL)
    {
        if (is_bool($val)) $this->isEmpty = $val;
        return $this->isEmpty;
    }


    /**
     * Sets element's textual content
     * @param string
     * @return TexyHtml  itself
     */
    function setText($text)
    {
        if ($text === NULL)
            $text = '';
        elseif (!is_scalar($text)) {
            trigger_error('Content must be scalar.', E_USER_WARNING);
            return FALSE;
        }

        $this->children = $text;
        return $this;
    }



    /**
     * Gets element's textual content
     * @return string
     */
    function getText()
    {
        if (is_array($this->children)) return FALSE;

        return $this->children;
    }



    /**
     * Adds new element's child
     * @param TexyHtml object
     * @return TexyHtml  itself
     */
    function addChild(/*TexyHtml*/ $child)
    {
        $this->children[] = $child;
        return $this;
    }


    /**
     * Returns child node
     * @param mixed index
     * @return TexyHtml
     */
    function getChild($index)
    {
        if (isset($this->children[$index]))
            return $this->children[$index];

        return NULL;
    }


    /**
     * Adds and creates new TexyHtml child
     * @param string  elements's name
     * @param string optional textual content
     * @return TexyHtml
     */
    function add($name, $text=NULL)
    {
        $child = new TexyHtml;
        $child->setName($name);
        if ($text !== NULL) $child->setText($text);
        return $this->children[] = $child;
    }


    /**
    /**
     * Special setter for element's attribute
     * @param string path
     * @param array query
     * @return TexyHtml  itself
     */
    function href($path, $params=NULL)
    {
        if ($params) {
        	// missing http_build_query in PHP5
            //$query = http_build_query($params, NULL, '&');
            if ($query !== '') $path .= '?' . $query;
        }
        $this->attrs['href'] = $path;
        return $this;
    }


    /**
     * Renders element's start tag, content and end tag
     * @return string
     */
    function export($texy)
    {
        $ct = $this->getContentType();
        $s = $texy->protect($this->startTag(), $ct);

        // empty elements are finished now
        if ($this->isEmpty) return $s;

        // add content
        if (is_array($this->children)) {
            foreach ($this->children as $val)
                $s .= $val->export($texy);

        } else {
            $s .= $this->children;
        }

        // add end tag
        return $s . $texy->protect($this->endTag(), $ct);
    }


    /**
     * Returns element's start tag
     * @return string
     */
    function startTag()
    {
        if (!$this->name) return '';

        $s = '<' . $this->name;

        if (is_array($this->attrs))
        foreach ($this->attrs as $key => $value)
        {
            // skip NULLs and false boolean attributes
            if ($value === NULL || $value === FALSE) continue;

            // true boolean attribute
            if ($value === TRUE) {
                // in XHTML must use unminimized form
                if ($GLOBALS['TexyHtml::$xhtml']) $s .= ' ' . $key . '="' . $key . '"';
                // in HTML should use minimized form
                else $s .= ' ' . $key;
                continue;

            } elseif (is_array($value)) {

                // prepare into temporary array
                $tmp = NULL;
                foreach ($value as $k => $v) {
                    // skip NULLs & empty string; composite 'style' vs. 'others'
                    if ($v == NULL) continue;

                    if (is_string($k)) $tmp[] = $k . ':' . $v;
                    else $tmp[] = $v;
                }

                if (!$tmp) continue;
                $value = implode($key === 'style' ? ';' : ' ', $tmp);

            } elseif ($key === 'href' && substr($value, 0, 7) === 'mailto:') {
                // email-obfuscate hack
                $tmp = '';
                for ($i=0; $i<strlen($value); $i++) $tmp .= '&#' . ord($value[$i]) . ';'; // WARNING: no utf support
                $s .= ' href="' . $tmp . '"';
                continue;
            }

            // add new attribute
            $value = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $value);
            $s .= ' ' . $key . '="' . Texy::freezeSpaces($value) . '"';
        }

        // finish start tag
        if ($GLOBALS['TexyHtml::$xhtml']&& $this->isEmpty) return $s . ' />';
        return $s . '>';
    }


    /**
     * Returns element's end tag
     * @return string
     */
    function endTag()
    {
        if ($this->name && !$this->isEmpty)
            return '</' . $this->name . '>';
        return '';
    }


    /**
     * Is element textual node?
     * @return bool
     */
    function isTextual()
    {
        return !$this->isEmpty && is_scalar($this->children);
    }


    /**
     * Clones all children too
     */
    function __clone()
    {
        if (is_array($this->children)) {
            foreach ($this->children as $key => $val)
                $this->children[$key] = clone ($val);
        }
    }


    /**
     * @return int
     */
    function getContentType()
    {
        if (isset($GLOBALS['TexyHtml::$replacedTags'][$this->name])) return TEXY_CONTENT_REPLACED;
        if (isset($GLOBALS['TexyHtml::$inline'][$this->name])) return TEXY_CONTENT_MARKUP;

        return TEXY_CONTENT_BLOCK;
    }



    /**
     * Parses text as single line
     * @param Texy
     * @param string
     * @return void
     */
    function parseLine($texy, $s)
    {
        // TODO!
        // special escape sequences
        $s = str_replace(array('\)', '\*'), array('&#x29;', '&#x2A;'), $s);

        $parser = new TexyLineParser($texy, $this);
        $parser->parse($s);
    }



    /**
     * Parses text as block
     * @param Texy
     * @param string
     * @param bool
     * @return void
     */
    function parseBlock($texy, $s, $topLevel=FALSE)
    {
        $parser = new TexyBlockParser($texy, $this);
        $parser->topLevel = $topLevel;
        $parser->parse($s);
    }



    function TexyHtml()  /* PHP 4 constructor */
    {
        // generate references (see http://www.dgx.cz/trine/item/how-to-emulate-php5-object-model-in-php4)
        foreach ($this as $key => $foo) $GLOBALS['$$HIDDEN$$'][] = & $this->$key;

        // call php5 constructor
        if (method_exists($this, '__construct')) {
            $args = func_get_args();
            call_user_func_array(array(&$this, '__construct'), $args);
        }
    }
}