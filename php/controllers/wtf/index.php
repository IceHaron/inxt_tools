<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class wtf_index {
	
	public static function init() {
		return new self();
	}
	
	private function wtf_index() {
		
		root::$_ALL['maintitle'] = 'Тестовая страница';
		root::$_ALL['maincaption'] = 'Заголовок страницы';
		root::$_ALL['mainsupport'] = 'Содержимое вспомогательного блока';
		root::$_ALL['maincontent'] = 'Содержимое центрального блока';
		root::$_ALL['backtrace'][] = 'initialized wtf/index';
	}

	public static function version() {

	}

}