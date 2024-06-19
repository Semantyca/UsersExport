<?php
defined('_JEXEC') or die('Restricted access');

class com_usersexportInstallerScript
{
    public function preflight($type, $parent)
    {
        $this->logMessage("preflight method called for {$type}.");

        if ($type === 'install' || $type === 'update') {
            $this->logMessage("Starting cleanBundleDirectory process for {$type}.");
            $this->cleanBundleDirectory();
            $this->logMessage("Finished cleanBundleDirectory process for {$type}.");
        }
    }

    public function install($parent)
    {
    }

    public function update($parent)
    {
    }

    public function uninstall($parent)
    {
    }

    public function postflight($type, $parent)
    {
        $this->logMessage("postflight method called for {$type}.");
        $this->logFilesInDirectory();
    }

    private function cleanBundleDirectory()
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

        $this->createMarkerFile($bundleDir, $logEntries);
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

    private function createMarkerFile($dir, $logEntries)
    {
        $markerFilePath = $dir . '/deletion_log.txt';
        if (!empty($logEntries)) {
            file_put_contents($markerFilePath, implode("\n", $logEntries));
            $this->logMessage("Created marker file: {$markerFilePath}");
        }
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
