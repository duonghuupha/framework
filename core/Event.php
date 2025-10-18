<?php
class Event {
    protected static $listeners = [];

    public static function listen($event, $callback) {
        self::$listeners[$event][] = $callback;
    }

    public static function trigger($event, $data = null) {
        if (!empty(self::$listeners[$event])) {
            foreach (self::$listeners[$event] as $callback) {
                call_user_func($callback, $data);
            }
        }
    }
}
