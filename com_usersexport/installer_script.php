<?php
defined('_JEXEC') or die('Restricted access');

class InstallerScript
{
    public function preflight($type, $parent)
    {
        $this->logMessage("preflight method called for {$type}.");

        if ($type === 'install' || $type === 'update') {
            $this->logMessage("Starting cleanBundleDirectory process for {$type}.");
            $this->cleanBundleDirectory($type);
            $this->logMessage("Finished cleanBundleDirectory process for {$type}.");
        }
        $this->createMarkerFile("preflight_{$type}");
    }

    public function install($parent)
    {
        $this->createMarkerFile("install");
    }

    public function update($parent)
    {
        $this->createMarkerFile("update");
    }

    public function uninstall($parent)
    {
        $this->createMarkerFile("uninstall");
    }

    public function postflight($type, $parent)
    {
        $this->logMessage("postflight method called for {$type}.");
        $this->logFilesInDirectory();
        $this->createMarkerFile("postflight_{$type}");
    }

    private function cleanBundleDirectory($type)
    {
        $bundleDir = JPATH_ADMINISTRATOR . '/components/com_usersexport/assets/bundle';
        $logEntries = [];

        if (is_dir($bundleDir)) {
            $logEntries[] = "Cleaning directory: {$bundleDir}";
            $this->logMessage("Cleaning directory: {$bundleDir}");
            $logEntries = array_merge($logEntries, $this->deleteFiles($bundleDir));
        } else {
            $logEntries[] = "Directory does not exist: {$bundleDir}";
            $this->logMessage("Directory does not exist: {$bundleDir}");
        }

        $this->createMarkerFile($bundleDir, $logEntries, "{$type}_clean");
    }

    private function deleteFiles($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        $logEntries = [];

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_file($path)) {
                unlink($path);
                $logEntries[] = "Removed file: {$path}";
                $this->logMessage("Removed file: {$path}");
            }
        }

        return $logEntries;
    }

    private function createMarkerFile($type)
    {
        $markerFilePath = JPATH_ADMINISTRATOR . "/components/com_usersexport/assets/{$type}_marker.txt";
        file_put_contents($markerFilePath, "Marker file for event: {$type}");
        $this->logMessage("Created marker file: {$markerFilePath}");
    }

    private function logFilesInDirectory()
    {
        $dir = JPATH_ADMINISTRATOR . '/components/com_usersexport/assets/bundle';
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            if (empty($files)) {
                $this->logMessage("Directory {$dir} is empty.");
            } else {
                foreach ($files as $file) {
                    $this->logMessage("File in directory: {$file}");
                }
            }
        } else {
            $this->logMessage("Directory does not exist: {$dir}");
        }
    }

    private function logMessage($message)
    {
        JLog::add($message, JLog::INFO, 'com_usersexport');
    }
}
