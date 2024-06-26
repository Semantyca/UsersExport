<?php
/**
 * @package     Usersexport
 * @subpackage  Administrator
 *
 * @copyright   Copyright (C) 2024 Absolute Management SIA. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;
?>
<div id="app">Loading...</div>
<?php if (isset($this->js_bundle) && $this->js_bundle): ?>
    <script type="module" src="<?php echo JUri::root() . 'administrator/components/com_usersexport/assets/' . $this->js_bundle; ?>"></script>
<?php else: ?>
    <?php echo '<!-- JS Bundle is not set or is empty -->'; ?>
<?php endif; ?>
