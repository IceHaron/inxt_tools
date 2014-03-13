<?


/**
*	
*	Класс, связанный с аутентификацией пользователей,
*	все действия, как-то касающиеся авторизации, логина, регистрации и прочей чепухи нужно кидать сюда.
*	
*/
class auth {

	public static function init() {
		return new self();
	}

/**
*	
*	Конструктор
*	
*/
	private function auth() {
		global $GAMINAS;
		
// Логинизация от uLogin, оставлю пока здесь, мало ли пригодится
		
		if (isset($_POST['token'])) {
		$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] . '&host=' . $_SERVER['HTTP_HOST']);
		$user = json_decode($s, true);
		//$user['network'] - соц. сеть, через которую авторизовался пользователь
		//$user['identity'] - уникальная строка определяющая конкретного пользователя соц. сети
		//$user['first_name'] - имя пользователя
		//$user['last_name'] - фамилия пользователя
		// var_dump($user);
		}
		
// Конец логинизации через uLogin, теперь все по хардкору
		if (isset($_SESSION['uid']) && $_SESSION['uid'] !== '' && $_SESSION['uid'] !== NULL) {
			// Проверяем, может, в сессии лежит айдишник? это значит, что мы уже авторизованы
			$GAMINAS['uid'] = $_SESSION['uid'];
			$GAMINAS['backtrace'][] = 'Logged in through session';
		} else if (isset($_COOKIE['uid']) && $_COOKIE['uid'] !== '' && $_COOKIE['uid'] !== NULL) {
			// Не в сессии, так в печеньках
			$GAMINAS['uid'] = $_COOKIE['uid'];
			$_SESSION['uid'] = $GAMINAS['uid'];
			$GAMINAS['backtrace'][] = 'Logged in through cookie';
		} else $GAMINAS['uid'] = 0;																									// Ну если даже в печеньках нет нашего юида, то все-таки мы не логинились
		
		$uid = $GAMINAS['uid'] ? $GAMINAS['uid'] : 0;																// Делалось для сокращения кода, после модификаций можно будет это убрать и лишний раз не переобъявлять переменную
		
		if ($uid !== 0) {																														// Если мы авторизованы, надо подгрузить из стима наши данные
			$str = '';

			// Profile
			$profile_str = file_get_contents('http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=0BE85074D210A01F70B48205C44D1D56&steamids=' . $uid);
// Вот эта строка - и есть заглушка на случай если стим недоступен
			// $profile_str = '{"response":{"players":[{"personaname":"Dummy","profileurl":"gaminas.ice","avatar":"http://placehold.it/32x32"}]}}';
			$str .= '{"profile":' . $profile_str . '';

			// Inventory
			$inv_str = file_get_contents('http://steamcommunity.com/profiles/' . $uid . '/inventory/json/753/1');
// И это - тоже заглушка
			// $inv_str = '{}';
			$str .= '}';

			// echo '<pre>';
			// echo $str;
			// echo '</pre>';
			$json = json_decode($str);																								// Собираем из строки массив...
			// echo '<pre>';
			// var_dump($json);
			// echo '</pre>';
			$GAMINAS['username'] = $json->profile->response->players[0]->personaname;	// ...и парсим...
			$GAMINAS['profurl'] = $json->profile->response->players[0]->profileurl;		// ...парсим...
			$GAMINAS['avatar'] = $json->profile->response->players[0]->avatar;				// ...и еще раз парсим...
		} else {
			// Ну и если все-таки мы не авторизованы, выводим сообщение, не знаю пока, зачем.
			$GAMINAS['nologin'] = 'You are not logged in!';
		}
		// echo '<pre>';
		// var_dump('GLOBAL', $GAMINAS);
		// echo '</pre>';
	}
	

/**
*	
*	Метод логоффа
* Все просто: подчищаем сессию, убиваем куку, редиректим пока что на главную, надо будет сделать редирект обратно
*	
*/
	public static function logoff() {
		unset ($_SESSION['uid']);
		setcookie('uid', '', time(), '/');																			// Пример удаления печенки: имя, пустое значение, нынешний таймстамп и обязательно совпадающий с существующей кукой путь
		unset ($_COOKIE['uid']);
		echo '<meta http-equiv="refresh" content="0;URL=http://gaminas.ice" />';
	}
}

?>
