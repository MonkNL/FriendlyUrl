<?php
class Singleton
{
    protected static self|null $instance = null;

    final private function __construct(){}
    final protected function __clone(){}
    final protected function __wakeup(){}

    public static function getInstance(): static
    {
        if (static::$instance === null) {
            static::$instance = new static;
        }

        return static::$instance;
    }
}

?>