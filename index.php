<?php

/**
*	
*	Корневой контроллер, ядро всего сайта
*	@author: Пищулин Сергей
*	@version: 0.0.1
*	
*/
class root {

	public static $path; 																																		// Адрес, куда мы обращаемся, берется из $_SERVER['HTTP_HOST']
	public static $server; 																																	// Переменная $_SERVER, добавил сюда просто для тренировки.
	public static $_ALL;
	public static function init() {
		return new self();
	}
	
/**
*
*	Конструктор
*
*/
	private function root() {
		header("HTTP/1.0 200 OK");																									// Вывешивается хэдер, иначе любая страница кроме / выдает 404 в хэдере
		$address = $_SERVER['HTTP_HOST'];
		// Объявляем глобалку
		self::$_ALL = array(																												// Глобальная переменная, куда будет запихиваться весь нужный хлам
			  'maintitle' => 'Главная'																								// Заголовок страницы
			, 'maincaption' => 'Default Caption'																			// Стандартный заголовок страницы
			, 'maincontent' => 'NULL'																									// Стандартное содержимое центрального блока
			, 'mainsupport' => 'NULL'																									// Вспомогательный блок
			, 'backtrace' => array()																									// Стандартный бэктрейс
			, 'rootfolder' => isset($_SERVER['HOME']) ? $_SERVER['HOME'].'/gaminas' : $_SERVER['DOCUMENT_ROOT']
		);
		self::$path = $address;																											// Отдаем в классовое свойство адрес...
		self::$server = $_SERVER;																										// ...и переменную $_SERVER
		self::url_parse();																													// Разбираем адрес

	}

/**
*	
*	Метод подгрузки классов в зависимости от заданных фильтров
*	На вход можно задать фильтр в виде строки, массива или ничего
* @param NULL : Фильтра нет, подгружаем все классы из папки
*	@param string : Фильтр задан строкой, выбираем класс в соответствии с ним (желательно сообщать точное название файла)
*	@param array : Фильтр задан массивом, выбираем файлы в соответствии с элементами этого массива
*	
*/
	public static function include_classes($filter = '') {
		$files = array();
		
		/*// Проверка входных данных
		if (gettype($filter) == 'string' && $filter != '') {
			$ver[$filter] = preg_match('/[\W]/', $filter);														// При такой проверке допускаются буквы, цифры и нижний слэш, например, ololo_2trololo
		} else if (gettype($filter) == 'array') {
		
		} else {
		
		}
		var_dump($ver);
		die; */
		
		// Проверка пройдена, начинаем разбор
		if(gettype($filter) == 'string' && $filter != '') { 												// Если даем строкой только один нужный модуль
			root::$_ALL['backtrace'][] = 'got string filter: ' . $filter;
			$files = glob('php/classes/' . $filter . '.php');
		} else if (gettype($filter) == 'array') {																		// Если в массиве перечисляем нужные модули
			$backtrace = 'got array filter: ';

			foreach ($filter as $need) {
				$backtrace .= $need . ', ';
				$files = array_merge($files, glob('php/classes/' . $need . '.php'));
			}
			
			root::$_ALL['backtrace'][] = $backtrace;

		} else {																																		// Если вообще не даем параметров, соответственно, нужны вообще все модули, пока что нужно в качестве костыля
			root::$_ALL['backtrace'][] = 'got no filter';
			$files = glob('php/classes/*.php'); 
		}
		if ($files) {
			// Пробегаемся по составленному списку файлов и инклудим каждый.
			$backtrace = 'found files: ';
			foreach($files as $file) {
				$backtrace .= $file . ', ';
				require_once($file);
			}
		} else {
			$backtrace = 'We found no classes with that filter';
		}
		root::$_ALL['backtrace'][] = $backtrace;
	}

/**
*	
*	Метод разбора адреса
*	
*/
	private static function url_parse() {
		global $auth;																																// Класс auth полюбому уже объявлен. лишний раз его объявлять не надо, просто обращаемся к глобалке
		$path = explode('/', trim($_SERVER['REQUEST_URI'], '/'));										// Отрезаем крайние слеши у адреса и разбиваем его в массив
		$a = $path;
		
		if (strpos(array_pop($a), '.') === FALSE) {																	// Проверяем наличие точки в последнем элементе (признак адреса типа /wtf/tell.php, не нужно нам такой радости.)
		
			if ($path[0] != '') {
				$count = count($path);																									// Считаем количество уровней в адресе
				$path[ $count-1 ] = preg_replace('/\?.+/', '', $path[ $count-1 ]);			// Убираем GET, в адресе он нам не нужен, он уже в переменной
				root::$_ALL['folder'] = $path[0];																				// Первый уровень всегда определяет группу контроллеров
				
				if ($count == 1) {																											// Один уровень: <host>/library
					root::$_ALL['controller'] = 'index';
					root::$_ALL['action'] = 'init';
					root::$_ALL['params'] = array();

				} else if ($count == 2) {																								// Два уровня: <host>/auth/login
					root::$_ALL['controller'] = 'index';
					root::$_ALL['action'] = $path[1];
					root::$_ALL['params'] = array();
					
				} else if ($count == 3) {																								// Три уровня: <host>/wtf/three/level
					root::$_ALL['controller'] = $path[1];
					root::$_ALL['action'] = $path[2];
					root::$_ALL['params'] = array();
					
				} else if ($count >= 4) {																								// Четыре и более уровня: <host>/wtf/four/level/addr...
					root::$_ALL['controller'] = $path[1];
					root::$_ALL['action'] = $path[2];
					root::$_ALL['params'] = array_slice($path, 3);
				
				} else {																																// Ну и это на всякий случай
					header("HTTP/1.0 404 Not Found");
					echo file_get_contents('error/404.php');
					die;
				}
			}
				
			// fb($path, 'PATH');
			root::$_ALL['isfile'] = FALSE;																						// Ставим триггер в положение FALSE чтобы позже определить, пытаемся мы обратиться к файлу напрямую или ввели нормальный путь
			self::include_classes();																									// Подключаем обязательные классы
			db::init();	auth::init();																									// Инициализируем обязательные классы
			universe::init();																													// Создаем вселенную

		/////////////////////// Может, имеет смысл инициализировать классы сразу после подключения? Раз уж у меня лишнего пока ничего не подключается вроде
			
		} else {
			root::$_ALL['isfile'] = TRUE;																								// Если же мы пытаемся обратиться к файлу напрямую, ставим триггер в положение TRUE
		}
	}
	
}

/**
*	
*	Самое начало работы сайта
*	
*/

session_start();																																// Стартуем сессию
INCLUDE_ONCE('php/firephp/fb.php');																							// Подключаем FirePHP
ob_start();
// fb($_SERVER);
root::init();																																		// Инициализируем коренной класс
	
/////////////////////////////// Делаем блок TODO, надо бы это запихнуть в какой-нибудь отдельный файл

if (isset(root::$_ALL['username']) && root::$_ALL['username'] == 'Ice_Haron') {
	$file = fopen('source/txt/TODO.txt', 'r');																									// Разбираем TODO.txt
	$c = 0;
	while ($todostring = fgets($file)) {
		$todoarr = explode('--', $todostring);
		root::$_ALL['todo'][$c]['class'] = trim($todoarr[0]);
		root::$_ALL['todo'][$c]['text'] = trim($todoarr[1]);
		root::$_ALL['todo'][$c]['state'] = trim($todoarr[2]);
		$c++;
	}
}

if (!root::$_ALL['isfile']) {																											// Если обращаемся не непосредственно к файлу

	root::$_ALL['source'] = 'http://' . root::$path . '/source';											// Папка, откуда берется весь хлам
	require_once('php/controllers/index.php');																		// Подключаем контроллер, хорошо бы сделать подгрузку контроллера в зависимости от адреса или что-нибудь типа того

	if (isset(root::$_ALL['action']) && root::$_ALL['action'] == 'logoff') auth::logoff();
	else if (root::$_ALL['folder'] != '') {																								// Если же мы зрим не в корень, то надо подключить контроллер и вид
		$controller = root::$_ALL['folder'] . '_' . root::$_ALL['controller'];
		INCLUDE_ONCE('php/controllers/' . root::$_ALL['folder'] . '/' . root::$_ALL['controller'] . '.php');
		$controller::{root::$_ALL['action']}(root::$_ALL['params']);
		// Здесь я забираю содержимое вида и управляющие конструкции меняю на содержимое переменных из root::$_ALL - подсмотрел этот способ реализации MVC
		if (!isset(root::$_ALL['notemplate'])) $page = file_get_contents('html/views/' . root::$_ALL['folder'] . '/' . root::$_ALL['controller'] . '.html');
		else $page = '';
		preg_match_all('/\{(\w+)\}/', $page, $matches);
		foreach ($matches[1] as $word) {
			$page = str_replace('{' . $word . '}', root::$_ALL[$word], $page);						// Если здесь возникает ошибка, то значит в массиве root::$_ALL нет элемента с именем, которое использовано в каком-то макете
		}
	} else $page = root::$_ALL['maincontent'];
	
	// fb(root::$_ALL, '$_ALL');
	if (!isset(root::$_ALL['notemplate'])) INCLUDE_ONCE('html/index.html');					// Ну и подгружаем макет, конечно же
	
} else {																																				// Если же обращение идет непосредственно к файлу
	header('HTTP/1.0 404 Not Found');
	// INCLUDE(trim($_SERVER['REQUEST_URI'], '/'));
	// echo 'Nonono, David Blaine!';
}
?>