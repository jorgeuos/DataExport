<?php
namespace Piwik\Plugins\DataExport\Helpers;

class UserHelper {
    public static function getUserPreference($key, $default = false) {
        $userId = \Piwik\Piwik::getCurrentUserLogin();
        return \Piwik\Option::get($userId . '_' . $key) ?: $default;
    }

    public static function setUserPreference($key, $value) {
        $userId = \Piwik\Piwik::getCurrentUserLogin();
        \Piwik\Option::set($userId . '_' . $key, $value);
    }
}