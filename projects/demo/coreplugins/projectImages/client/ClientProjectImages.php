<?php
/**
 * @package CorePlugins
 * @version $Id$
 */

/**
 * @package CorePlugins
 */
class ClientProjectImages extends ClientImages {

    public function replacePlugin() {
        return 'images';
    }

    public function renderForm(Smarty $template) {
        $collapseKeymap = isset($_REQUEST['collapse_keymap']) ?
                          $_REQUEST['collapse_keymap'] : 0;
        $template->assign('collapseKeymap', $collapseKeymap);
        parent::renderForm($template);
    }
}
?>
