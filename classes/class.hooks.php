<?php
class Hooks {
    private static $instance = null;

    private function __construct() {
    }


    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Self();
        }
        return self::$instance;
    }
}

?>
