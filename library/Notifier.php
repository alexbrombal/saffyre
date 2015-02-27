<? 

class Notifier 
{
	private static $observers = array();
	
	public static function observe($event, $callback)
	{
		self::$observers[$event][] = $callback;
	}
	
	public static function notify($event, $args = null)
	{
		if(!isset(self::$observers[$event])) return;
		$args = func_get_args();
		foreach(self::$observers[$event] as $callback) call_user_func_array($callback, $args);
	}
}