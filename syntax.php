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
          msg('Syntax of xssnipper detected but parameter missing.', -1);          
        }
        elseif($params[0] == ''){
          //             0      1            2              3
          // {(xssnipper>,[start line],[type] [file],<c>[content]</c>)}
          $params = explode(",",$match,4);
          $xssnipper                = array();
          $xssnipper['filepath']    = '';
          $xssnipper['from']        = $params[1];
          $alpha                    = explode(' ',$params[2]);
          $xssnipper['type']        = $alpha[0];
          $xssnipper['file']        = $alpha[2];
          $xssnipper['block']       = $alpha[1];
          $xssnipper['code']        = $params[3];
        }
        else { 
          // Values
          $xssnipper                = array();
          $xssnipper['filepath']    = $params[0];
          $xssnipper['from']        = $params[1];
          $xssnipper['until']       = $params[2];
          $alpha                    = explode(' ',$params[3]);
          $xssnipper['type']        = $alpha[0];
          $xssnipper['file']        = $alpha[2];
          $xssnipper['block']       = $alpha[1];
        }        
        return $xssnipper;
     }
/******************************************************************************/
/* render output
* @author Taggic <taggic@t-online.de>
*/   
    function render($mode, &$renderer, $xssnipper) {
        global $ID;
        if(!$xssnipper['type'])  $xssnipper['type']='txt';
        if($this->_codeblock<1)  $this->_codeblock=1;

        if($xssnipper['filepath']=='') {
           $code_lines = $xssnipper['code'];
        }
        else {
          if(!$xssnipper['file'])  $xssnipper['file']= basename($xssnipper['filepath']);
          if(!$xssnipper['file'])  $xssnipper['file']='snippet.'.$xssnipper['type'];
      
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
      }
      $geshi = new GeSHi($code_lines, $xssnipper['type']);
      $geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);
      $geshi->set_overall_class('xssnipper');
      $geshi->set_header_type(GESHI_HEADER_DIV);
      //        $geshi->set_header_type(GESHI_HEADER_PRE_TABLE);
      $geshi->start_line_numbers_at($xssnipper['from']);
      
      $xs_path = '?do=export_code&id='.$ID;
      $text = $geshi->parse_code();
      
      $code_block .= NL.NL.'
      <dl class="code">
        <dt>
          <a href="'.$xs_path.'&codeblock='.$this->_codeblock.'" title="Download Snippet" class="mediafile mf_'.$xssnipper['type'].'">'.$xssnipper['file'].'</a>
        </dt>';
       
        // returns the javascript function for clip-clap of block if downloadblock is used
        $clipclap_flag = false;
        if($xssnipper['block']) {
            $code_block .= '<br />'.$this->__scripts_html();
            $clipclap_id   = microtime();
            $img_ID        = 'img_'.$clipclap_id;
            $clipclap_img .= '<img id="'.$img_ID.'"
                                   src="'.DOKU_BASE.'lib/plugins/xssnipper/images/enfold.png" 
                                   alt="show" />'.NL;
                    
            $code_block .= '<span id="'.$clipclap_id.'" style="display : none;">'.NL;
            $clipclap_flag = true;    
        }
  
        $code_block .= '<dd style="display : none;">'.$code_lines.'</dd>'.$text.NL;
        
        if($clipclap_flag == true) {
            $code_block .= '</span>'.NL;
            $code_block .= '<div class="img_clipclap" onClick="span_open(\''.$clipclap_id.'\',\''.$img_ID.'\')"></div>'.NL;
        }
      $code_block .= '</dl>'.NL;         
       
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
/******************************************************************************/
    function __scripts_html()  {
        $ret .=  '<span><script>
             function span_open(blink_id, img_id) 
              {   if (document.getElementById(blink_id).style.display == "block")
                  {   document.getElementById(blink_id).style.display = "none";
                      document.getElementById(img_id).style.backgroundPosition = "0px 0px";
                  }
                  else
                  {   document.getElementById(blink_id).style.display = "block";
                      document.getElementById(img_id).style.backgroundPosition = "0px -19px";
                  }
              } 
        </script></span>'.NL;
        return $ret;
    }
}
?>