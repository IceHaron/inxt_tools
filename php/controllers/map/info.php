<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class map_info {
	
	public static function init() {
		return new self();
	}
	
	private function map_info() {
		
		root::$_ALL['maintitle'] = 'Тестовая страница';
		root::$_ALL['maincaption'] = 'Заголовок страницы';
		root::$_ALL['mainsupport'] = 'Содержание вспомогательного блока';
		root::$_ALL['maincontent'] = 'Содержание центрального блока';
		root::$_ALL['backtrace'][] = 'initialized wtf/four';
	}
	
	public static function system($params) {
		if (!$params) $params = array('Amarr');
		$system = urldecode($params[0]);

		$sysInfo = db::query("SELECT `s`.`id`, `s`.`name` AS `sysName`, `s`.`security`, `r`.`name` AS `regName`
														FROM `systems` AS `s`
														JOIN `regions` AS `r` ON (`r`.`id` = `s`.`regionID`)
														WHERE `s`.`name` = '$system';");
		root::$_ALL['sysID'] = $sysInfo[0]['id'];
		root::$_ALL['sysName'] = $sysInfo[0]['sysName'];
		root::$_ALL['sysSS'] = $sysInfo[0]['security'];
		root::$_ALL['sysRegName'] = $sysInfo[0]['regName'];

		$maincontent = '';

		root::$_ALL['maintitle'] = 'Информация о системе ' . $system;
		root::$_ALL['maincaption'] = 'Информация о системе ' . $system;
		root::$_ALL['mainsupport'] = '';
		root::$_ALL['maincontent'] = $maincontent;
		root::$_ALL['backtrace'][] = 'initialized map/info/system';
	}
	
	public static function region($params) {
		if (!$params) $params = array('Domain');
		$region = $params[0];

		$regInfo = db::query("SELECT `id`, `name` FROM `regions` WHERE `name` = '$region';");
		root::$_ALL['regID'] = $regInfo[0]['id'];
		root::$_ALL['regName'] = $regInfo[0]['name'];

		$maincontent = '';

		root::$_ALL['maintitle'] = 'Информация о регионе ' . $region;
		root::$_ALL['maincaption'] = 'Информация о регионе ' . $region;
		root::$_ALL['mainsupport'] = '';
		root::$_ALL['maincontent'] = $maincontent;
		root::$_ALL['backtrace'][] = 'initialized map/info/system';
	}
	
	
}