<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class ssf_index {
	
	public static function init() {
		return new self();
	}
	
	private function ssf_index() {
		
		root::$_ALL['maincaption'] = 'EVE Smart Star Filters';
		root::$_ALL['mainsupport'] = 'Содержание вспомогательного блока';
		root::$_ALL['maincontent'] = 'Содержание центрального блока';
		root::$_ALL['backtrace'][] = 'initialized ssf/index';
	}

}