<?

/**
*	
*	Тестовый класс, воротим здесь что захотим и всякие штуки тестируем именно тут.
*	Заодно это шаблон для создания других классов.
*	
**/

class map_index {
	
	public static function init() {
		return new self();
	}
	
	private function map_index() {
		global $GAMINAS;
		
		$GAMINAS['maincaption'] = 'EVE Universe Map';
		$GAMINAS['mainsupport'] = 'Содержание вспомогательного блока';
		$GAMINAS['maincontent'] = 'Содержание центрального блока';
		$GAMINAS['backtrace'][] = 'initialized map/index';
	}

}