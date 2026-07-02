<?php

namespace App\Filesystem;

use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\UnableToListContents;

/**
 * Spatie Backup は保存前に backup 名のフォルダを list する。
 * Google Drive では未作成フォルダの list が例外になるため、空配列を返して初回保存を通す。
 */
class GoogleDriveFilesystemAdapter extends FilesystemAdapter
{
    public function files($directory = null, $recursive = false)
    {
        try {
            return parent::files($directory, $recursive);
        } catch (UnableToListContents) {
            return [];
        }
    }

    public function allFiles($directory = null)
    {
        try {
            return parent::allFiles($directory);
        } catch (UnableToListContents) {
            return [];
        }
    }
}
