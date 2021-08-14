<?php 
namespace Illuminate\Contracts\Cookie;
use Illuminate\Contracts\Cookie\QueueingFactories;
class CookieFactory 
{
	public function __construct()
    {

    }	
	public static function boot($param=null)
	{
		
		QueueingFactories::runCookie();
		
	}
}


 ?>