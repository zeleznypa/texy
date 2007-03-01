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
 * Ordered / unordered nested list module
 */
class TexyListModule extends TexyModule
{
    protected $default = array('list' => TRUE);

    public $bullets = array(
        '*'  => TRUE,
        '-'  => TRUE,
        '+'  => TRUE,
        '1.' => TRUE,
        '1)' => TRUE,
        'I.' => TRUE,
        'I)' => TRUE,
        'a)' => TRUE,
        'A)' => TRUE,
    );

    private $translate = array(
                    //  rexexp       list-style-type  tag
        '*'  => array('\*',          '',              'ul'),
        '-'  => array('[\x{2013}-]', '',              'ul'),
        '+'  => array('\+',          '',              'ul'),
        '1.' => array('\d+\.\ ',     '',              'ol'),
        '1)' => array('\d+\)',       '',              'ol'),
        'I.' => array('[IVX]+\.\ ',  'upper-roman',   'ol'),   // place romans before alpha
        'I)' => array('[IVX]+\)',    'upper-roman',   'ol'),
        'a)' => array('[a-z]\)',     'lower-alpha',   'ol'),
        'A)' => array('[A-Z]\)',     'upper-alpha',   'ol'),
    );



    public function init()
    {
        $bullets = array();
        foreach ($this->bullets as $bullet => $allowed)
            if ($allowed) $bullets[] = $this->translate[$bullet][0];

        $this->texy->registerBlockPattern(
            array($this, 'processBlock'),
            '#^(?:'.TEXY_MODIFIER_H.'\n)?'                    // .{color: red}
          . '('.implode('|', $bullets).')(\n?)\ +\S.*$#mUu',  // item (unmatched)
            'list'
        );
    }



    /**
     * Callback function (for blocks)
     *
     *            1) .... .(title)[class]{style}>
     *            2) ....
     *                + ...
     *                + ...
     *            3) ....
     *
     */
    public function processBlock($parser, $matches)
    {
        list(, $mMod1, $mMod2, $mMod3, $mMod4, $mBullet, $mNewLine) = $matches;
        //    [1] => (title)
        //    [2] => [class]
        //    [3] => {style}
        //    [4] => >
        //    [5] => bullet * + - 1) a) A) IV)

        $tx = $this->texy;

        $el = TexyHtml::el();

        $bullet = '';
        foreach ($this->translate as $type => $desc)
            if (preg_match('#'.$desc[0].'#Au', $mBullet)) {
                $bullet = $desc[0];
                $el->elName = $desc[2];
                $el->style['list-style-type'] = $desc[1];
		if ($el->elName === 'ol' && $type[0] === '1') {
                    $el->start = (int) $mBullet > 1 ? (int) $mBullet : NULL;            
                }
                break;
            }

        $mod = new TexyModifier;
        $mod->setProperties($mMod1, $mMod2, $mMod3, $mMod4);
        $mod->decorate($tx, $el);

        $parser->moveBackward($mNewLine ? 2 : 1);

        $count = 0;
        while ($elItem = $this->processItem($parser, $bullet, FALSE, 'li')) {
            $el->childNodes[] = $elItem;
            $count++;
        }

        if (!$count) return FALSE;

        $parser->children[] = $el;
    }



    public function processItem($parser, $bullet, $indented, $tag)
    {
        $tx =  $this->texy;
        $spacesBase = $indented ? ('\ {1,}') : '';
        $patternItem = "#^\n?($spacesBase)$bullet(\n?)(\\ +)(\\S.*)?".TEXY_MODIFIER_H."?()$#mAUu";

        // first line (with bullet)
        if (!$parser->receiveNext($patternItem, $matches)) {
            return FALSE;
        }
        list(, $mIndent, $mNewLine, $mSpace, $mContent, $mMod1, $mMod2, $mMod3, $mMod4) = $matches;
            //    [1] => indent
            //    [2] => \n
            //    [3] => space
            //    [4] => ...
            //    [5] => (title)
            //    [6] => [class]
            //    [7] => {style}
            //    [8] => >

        $elItem = TexyHtml::el($tag);
        $mod = new TexyModifier;
        $mod->setProperties($mMod1, $mMod2, $mMod3, $mMod4);
        $mod->decorate($tx, $elItem);

        // next lines
        $spaces = $mNewLine ? strlen($mSpace) : '';
        $content = ' ' . $mContent; // trick
        while ($parser->receiveNext('#^(\n*)'.$mIndent.'(\ {1,'.$spaces.'})(.*)()$#Am', $matches)) {
            list(, $mBlank, $mSpaces, $mContent) = $matches;
            //    [1] => blank line?
            //    [2] => spaces
            //    [3] => ...

            if ($spaces === '') $spaces = strlen($mSpaces);
            $content .= "\n" . $mBlank . $mContent;
        }

        // parse content
        $tmp = $tx->_paragraphMode;
        $tx->_paragraphMode = FALSE;
        $elItem->parseBlock($tx, $content);
        $tx->_paragraphMode = $tmp;

        if ($elItem->childNodes[0] instanceof TexyHtml) {
            $elItem->childNodes[0]->elName = '';
        }

        return $elItem;
    }

} // TexyListModule