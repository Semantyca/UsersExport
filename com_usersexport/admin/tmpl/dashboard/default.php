<?php
defined('_JEXEC') or die;
?>
<h1>Dashboard View</h1>
<div id="app">Loading...</div>
<?php if (isset($this->js_bundle) && $this->js_bundle): ?>
    <script type="module" src="<?php echo JUri::root() . 'administrator/components/com_usersexport/assets/' . $this->js_bundle; ?>"></script>
<?php endif; ?>




