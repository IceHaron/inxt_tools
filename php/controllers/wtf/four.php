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
		
		foreach (json_decode(file_get_contents($GAMINAS['rootfolder'] . '/source/txt/systems.txt'), TRUE) as $sysid => $sysinfo) {
			echo "$sysid => <br/>";
		}
	}

}