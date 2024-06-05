<?php

use Joomla\CMS\HTML\HTMLHelper;

HTMLHelper::_('form.csrf', '_csrf');
HTMLHelper::_('jquery.framework');

$app = Joomla\CMS\Factory::getApplication();
$doc = $app->getDocument();
global $smtca_assets;

$smtca_assets = "administrator/components/com_usersexport/assets/";
$rootUrl      = JUri::root();
$host         = JUri::getInstance()->toString(['host']);

$translations     = array(
	//'TEMPLATE'              => JText::_('TEMPLATE'),

);
$jsonTranslations = json_encode($translations);

?>
<script type="module">
    window.globalTranslations = <?php echo $jsonTranslations; ?>;
</script>

<div id="app">Loading...</div>


<?php if (isset($this->js_bundle) && $this->js_bundle): ?>
    <script type="module" src="<?php echo $rootUrl . $smtca_assets . $this->js_bundle; ?>"></script>
<?php endif; ?>




