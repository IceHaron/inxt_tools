<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class wtf_four {	//<< Переименовать класс в <папка>_<контроллер>
	
	public static function init() {
		return new self();
	}
	
	private function wtf_four() {	// << переименовать конструктор под стать классу
		global $GAMINAS;
		
		$GAMINAS['maincaption'] = 'Заголовок страницы';
		$GAMINAS['mainsupport'] = 'Содержание вспомогательного блока';
		$GAMINAS['maincontent'] = 'Содержание центрального блока';
		$GAMINAS['backtrace'][] = 'initialized wtf/four';
	}
	
	
///////////////////// Шаблон закончился, осталось только закрыть фигурную скобку
	
	
	public static function level($par) {
		global $GAMINAS;
		
		// Тырим XML и превращаем его в JSON
		$xml = new SimpleXMLElement('https://api.eveonline.com/map/Jumps.xml.aspx',0,TRUE);
		$arr = json_decode(json_encode($xml), TRUE);
		var_dump($arr);

		// $str = '';
		
		// foreach (json_decode(file_get_contents($GAMINAS['rootfolder'] . '/source/txt/systems.txt'), TRUE) as $sysid => $sysinfo) {
			// $ss = str_replace(',', '.', $sysinfo['security']);
			// $str .= "($sysid, '{$sysinfo['name']}', '{$ss}', '{$sysinfo['regionID']}'),";
			// echo('<pre>');var_dump($sysinfo);echo('</pre>');
		// }
		// $str = substr($str, 0, -1);
		// var_dump("INSERT INTO `systems` (`id`, `name`, `security`, `regionID`) VALUES $str");
		// $back = db::query("INSERT INTO `systems` (`id`, `name`, `security`, `regionID`) VALUES $str");
		// var_dump($back);
	}

}