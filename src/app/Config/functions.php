<?php

if (!function_exists('is_serialized')) {
	function is_serialized($param)
	{
		$data = @unserialize($param);
		return $param === 'b:0;' || $data !== false;
	}
}