<?php
/**
 * Action Plugin: Inserts a button into the toolbar to add file tags
 *
 * @author Heiko Barth
 */
 
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');
 
class action_plugin_xssnipper extends DokuWiki_Action_Plugin {
 
    /**
     * Return some info
     */
    function getInfo() {
        return array (
            'author' => 'Heiko Barth',
            'date' => '2010-03-02',
            'name' => 'Toolbar Code Button',
            'desc' => 'Inserts a code button into the toolbar',
            'url' => 'http://www.heiko-barth.de/download.php?id=dw_codebutton',
        );
    }
 
    /**
     * Register the eventhandlers
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'insert_button', array ());
    }
 
    /**
     * Inserts the toolbar button
     */
    function insert_button(&$event, $param) {
        $event->data[] = array (
            'type' => 'picker',
            'title' => 'Code select',
            'icon' => '../../plugins/xssnipper/images/code.png',
	          'list' => array( 
                            array(
                              'type' => 'format',
                              'title' => 'xssnip',
                              'icon' => '../../plugins/xssnipper/images/code_xssnip.png',
                              'open' => '{(xssnipper>',
                              'close' => ')}',
                            ),
                            array(
                              'type' => 'format',
                              'title' => 'code',
                              'icon' => '../../plugins/xssnipper/images/code_code.png',
                              'open' => '<code>\n',
                              'close' => '\n</code>',
                            ),
                            array(
                              'type' => 'format',
                              'title' => 'php',
                              'icon' => '../../plugins/xssnipper/images/code_php.png',
                              'open' => '<code php>\n',
                              'close' => '\n</code>',
                            ),
                            array(
                              'type' => 'format',
                              'title' => 'html',
                              'icon' => '../../plugins/xssnipper/images/code_html.png',
                              'open' => '<code html>\n',
                              'close' => '\n</code>',
                            ),
                            array(
                              'type' => 'format',
                              'title' => 'js',
                              'icon' => '../../plugins/xssnipper/images/code_js.png',
                              'open' => '<code php>\n',
                              'close' => '\n</code>',
                            ),
                            array(
                              'type' => 'format',
                              'title' => 'code',
                              'icon' => '../../plugins/xssnipper/images/code_css.png',
                              'open' => '<code css>\n',
                              'close' => '\n</code>',
                            ),
                            array(
                              'type' => 'format',
                              'title' => 'txt',
                              'icon' => '../../plugins/xssnipper/images/code_txt.png',
                              'open' => '<code txt>\n',
                              'close' => '\n</code>',
                            ),
                            array(
                              'type' => 'format',
                              'title' => 'xml',
                              'icon' => '../../plugins/xssnipper/images/code_xml.png',
                              'open' => '<code xml>\n',
                              'close' => '\n</code>',
                            ),
                            array(
                              'type' => 'format',
                              'title' => 'file',
                              'icon' => '../../plugins/xssnipper/images/code_file.png',
                              'open' => '<file>\n',
                              'close' => '\n</file>',
                            )
                      )
        );
    }
 
}
