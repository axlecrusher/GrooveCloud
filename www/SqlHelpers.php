<?php
	namespace SqlHelpers;

	function BindParam($stmt,$values)
	{
		static $typeMap = array('integer'=>'i','double'=>'d','string'=>'s');
		$types = '';
		$args = array();
		foreach ($values as &$v)
		{
			$type = gettype($v);
			$types .= isset($typeMap[$type])?$typeMap[$type]:'s';
			$args[] = &$v;
		}
		array_unshift($args,$types);
		return call_user_func_array(array($stmt,'bind_param'), $args);
	}

?>
