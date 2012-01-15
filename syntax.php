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
        $this->Lexer->addSpecialPattern('\{\(xssnipper>[^}]*\)\}',$mode,'plugin_xssnipper');
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
            [file path] ... path to the file (either it is in DokuWiki media directory or windows fileshare, etc.)
            [from line] ... the first line, which should be displayed
            [to line]   ... the last line, which should be displayed
            [type]      ... the type of content to tell the renderer how to interprete and set colors 
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
          $xssnipper['type']        = $params[3];
          return $xssnipper;
        }        
     }
/******************************************************************************/
/* render output
* @author Taggic <taggic@t-online.de>
*/   
    function render($mode, &$renderer, $xssnipper) {
    // 1. check if $xssnipper['filepath'] exist, else error message
    //    a) the file is in media directory and path is a relative one
       if(!file_exists($xssnipper['filepath'])) {
          msg('file not found',-1);
          echo $xssnipper['filepath'].'<br />';
       }
    //    b) file is stored somwhere else and full qualified path is necessary
    
    // 2. open the file in read mode
       
       $records    = file($xssnipper['filepath']);
       
    // 3. load the file content from line = $xssnipper['from'] , to line = $xssnipper['until']  into $code_lines
        foreach ($records as $line_num => $line) {
            if(($line_num>=$xssnipper['from']) && ($line_num<=$xssnipper['until']))
            $code_lines .= $line;
        }
    
    // 4. output
       $info = array();
       $code_block = '<code ' . $xssnipper['type'] . '>' . $code_lines . '</code>';
       $code_block = p_render('xhtml',p_get_instructions($code_block),$info);
       $renderer->doc .= $code_block;
    }
}
?>