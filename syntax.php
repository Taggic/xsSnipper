<?php
 
/**
 * Plugin xssnipper: provides rendered code snippeds from files to be displayed in a code block
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Taggic <taggic@t-online.de>
 */
 
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
if(!defined('DOKU_DATA')) define('DOKU_DATA',DOKU_INC.'data/pages/');
require_once(DOKU_PLUGIN.'syntax.php');  
require_once(DOKU_INC.'inc/parser/xhtml.php');
require_once(DOKU_INC.'inc/parser/renderer.php');


include_once(DOKU_INC.'inc/geshi.php');
 
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_xssnipper extends DokuWiki_Syntax_Plugin {

/******************************************************************************/
/* return some info
*/
    function getInfo(){
        return confToHash(dirname(__FILE__).'/plugin.info.txt');
    }

    function getType(){ return 'substition';}
    function getPType(){ return 'block';}
    function getSort(){ return 999;}
    
/******************************************************************************/
/* Connect pattern to lexer
*/   
    function connectTo($mode){
        $this->Lexer->addSpecialPattern('\{\(xssnipper\>.*?\)\}',$mode,'plugin_xssnipper');
    }                                   

/******************************************************************************/
/* handle the match
*/   
    function handle($match, $state, $pos, &$handler) {
        global $ID;
        $match = substr($match,strlen('{(xssnipper>'),-2); //strip markup from start and end

        //handle params
        $data = array();
    /******************************************************************************/
    /*      parameters can be:
            {(xssnipper>[file path],[from line],[to line],[type])}
            [file path]   ... path to the file (either it is in DokuWiki media directory or windows fileshare, etc.)
            [from line]   ... the first line, which should be displayed
            [to line]     ... the last line, which should be displayed
            [type] [file] ... the type of content to tell the syntax higlighter how to interprete and set colors
                              and pass a file for download the code block 
    /******************************************************************************/

        $params = explode(",",$match);  // if you will have more parameters and choose ',' to delim them
        
        
        if (!$params) {
          msg('Syntax of xssnipper detected but an unknown parameter was attached.', -1);          
        }
        else { 
          // Values
          $xssnipper                = array();
          $xssnipper['filepath']    = $params[0];
          $xssnipper['from']        = $params[1];
          $xssnipper['until']       = $params[2];
          $alpha                    = explode(' ',$params[3]);
          $xssnipper['type']        = $alpha[0];
          $xssnipper['file']        = $alpha[1];
          return $xssnipper;
        }        
     }
/******************************************************************************/
/* render output
* @author Taggic <taggic@t-online.de>
*/   
    function render($mode, &$renderer, $xssnipper) {
        global $ID;

        if(!$xssnipper['type']) $xssnipper['type']='txt';
        if(!$xssnipper['file']) $xssnipper['file']= basename($xssnipper['filepath']);
        if(!$xssnipper['file']) $xssnipper['file']='snippet.'.$xssnipper['type'];
        if(!$simplesnipper['until']) $simplesnipper['until'] = count($records);
        if($this->_codeblock<1) $this->_codeblock=1;

    // 1. check if $xssnipper['filepath'] exist, else error message
        if(!file_exists($xssnipper['filepath'])) {
          msg('file '.$xssnipper['filepath'].' not found',-1);
          return false;
        }
    
    // 2. open the file in read mode
        $records    = file($xssnipper['filepath']);

    // 3. load the file content from line = $xssnipper['from'] , to line = $xssnipper['until']  into $code_lines
        if(!$xssnipper['until']) $xssnipper['until']=count($records);
        foreach ($records as $line_num => $line) {
            if(($line_num>=$xssnipper['from']-1) && ($line_num<=$xssnipper['until']-2))
                $code_lines .= $line;
            if ($line_num>$xssnipper['until']) break;
        }

        $geshi = new GeSHi($code_lines, $xssnipper['type']);
        $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
        $geshi->set_overall_class('xssnipper');
        $geshi->set_header_type(GESHI_HEADER_DIV);
        $geshi->start_line_numbers_at($xssnipper['from']);

        $xs_path = '?do=export_code&id='.$ID;
        $text = $geshi->parse_code();
        
        $code_block .= NL.NL.'
        <dl class="code">
          <dt>
            <a href="'.$xs_path.'&codeblock='.$this->_codeblock.'" title="Download Snippet" class="mediafile mf_'.$xssnipper['type'].'">'.$xssnipper['file'].'</a>
          </dt>
          <dd>'.$text.'</dd>
        </dl>'.NL.NL;         
    
       $renderer->doc .= $code_block;

       if($this->_codeblock == $_REQUEST['codeblock']){
          header("Content-Type: text/plain; charset=utf-8");
          header("Content-Disposition: attachment; filename=".trim($xssnipper['file']));
          header("X-Robots-Tag: noindex");
          header("Pragma: public"); 
          echo trim($code_lines,"\r\n");
          exit;
        }
       $this->_codeblock++; 

    }
}
?>