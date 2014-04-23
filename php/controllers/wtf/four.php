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
		
		root::$_ALL['maintitle'] = 'Тестовая страница';
		root::$_ALL['maincaption'] = 'Заголовок страницы';
		root::$_ALL['mainsupport'] = 'Содержание вспомогательного блока';
		root::$_ALL['maincontent'] = 'Содержание центрального блока';
		root::$_ALL['backtrace'][] = 'initialized wtf/four';
	}
	
	
///////////////////// Шаблон закончился, осталось только закрыть фигурную скобку
	
	
	public static function level($par) {
		
		root::$_ALL['maintitle'] = 'Тестовая страница';
		root::$_ALL['maincaption'] = 'Заголовок страницы';
		root::$_ALL['mainsupport'] = 'Содержимое вспомогательного блока';
		root::$_ALL['maincontent'] = 'Содержимое центрального блока';
		root::$_ALL['backtrace'][] = 'initialized wtf/four';
	}

}