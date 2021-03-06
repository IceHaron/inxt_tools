<?php

/**
*	
*	Класс для работы с базами данных
*	
**/

class db {

	public static $con;																																			// Ссылка на подключение

	public static function init() {
		return new self();
	}
	
/**
*	
*	Конструктор
*	
**/
	private function db() {
		
		$host = 'localhost';
		$user = 'srv44030_tools';
		$pw = '230105';
		$base = 'srv44030_tools';
		
// Подключаемся к базе, устанавливаем чарсет, ловим ошибки и формируем бэктрейс

		$link = mysqli_connect($host, $user, $pw, $base);
		
		if (!$link) {
			printf("<h2>Невозможно подключиться к базе данных.</h2> Код ошибки: %s\n", mysqli_connect_error());
			exit;
		} else self::$con = $link;
		
		if (!mysqli_set_charset($link, "utf8")) {
			root::$_ALL['backtrace'][] = "Error loading character set utf8: " . mysqli_error($link);
		} else {
			root::$_ALL['backtrace'][] = "Current character set: " . mysqli_character_set_name($link);
		}
		self::query("SET sql_big_selects = ON;");
		// root::$_ALL['checkpoints'][] = array('DB initialized', microtime(1));
	}
	
	public static function query($query) {
		$query_result = mysqli_query(self::$con, $query);
		if (gettype($query_result) !== 'boolean') {
			while ($row = mysqli_fetch_assoc($query_result)) $res[] = $row;
		}
		else $res = ($query_result === FALSE) ? mysqli_error(self::$con) : $query_result;
		// fb($res, 'RESULT');
		if (mysqli_error(self::$con) != '') return mysqli_error(self::$con);
		if (!isset($res)) $res = NULL;																							// Если выборка пустая, присваиваем NULL
		return $res;
	}
	
	public static function escape($string) {
		return mysqli_real_escape_string(self::$con, $string);
	}
	
}

?>