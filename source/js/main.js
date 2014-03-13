
/**
*	
*	Ну конечно же, всякие разные штуки при завершении загрузки страницы
*	
**/
$(document).ready(function() {

/**
*	
*	Клик на заголовке скрывает/раскрывает фильтр и что-то еще делает, пока не придумал
*	
**/
$('#namesearch').keyup(function(key) {
	if (key.keyCode != '16' && $(this).val().length > 0) {												// Не буду отслеживать Шифт
		what = $(this).val().toLowerCase();
		filtered = 0;
		agreed = 0;
		$('.gamename').each(function() {
			where = $(this).text().toLowerCase();
			if (where.search(what) == -1) { $(this).parent().hide(); filtered++; }		// Все, что отфильтровывается, скрываем
			else { $(this).parent().show();	agreed++; }																// А все, что удовлетворяет, показываем.
		});
		$('#filtercomment').text('Показано ' + agreed + ', отфильтровано ' + filtered);
	} else if ($(this).val().length == 0) $('#filtercomment').text('');
});

/**
*	
*	Клик на заголовке скрывает/раскрывает фильтр и что-то еще делает, пока не придумал
*	
**/
  $('#maincaption').click(function() {
    $('#mainsupport').toggle();																									// Фильтр пока существует только в библиотеке, в этот блок думаю напихать всяких разных удобных штук
  });
  
/**
*	
*	Перехватчик клика на логофф, кнопка должна что-то делать хитрое,
*	пока просто перенаправляет на страничку логоффа
*	
**/
  $('#logoff').click(function() {
    window.location = '/auth/logoff';
  });
	
/**
*	
*	Работа с табличкой TODO
*	
**/

	$('.DONE').parent().hide();																										// Скрываем то, что я уже сделал, чтоб не мешались
	
	$('#todo tbody tr').hover(function() {
		$(this).children('td').animate({'opacity': '1'}, 100);
	},
	function() {
		if ($(this).children('td').eq(2).attr('class') == 'UNDONE-right')	$(this).children('td').animate({'opacity': '0.7'}, 100);
		else $(this).children('td').animate({'opacity': '0.3'}, 100);
	});
	
	
	/* Фильтрация для графика: при клике на "все" ставить/снимать галки у всех детей */
	$('input.all').on('click', function() {
		chk = this.checked;
		$(this).parent().next().children().children('input').each(function() {
			this.checked = chk;
			if ($(this).attr('name') == 'region') toggleStars($(this).attr('data-id'), chk);
		});
	});
	
	/* Фильтрация систем по региону */
	$('#region input[name="region"]').on('click', function() {
		var regid = $(this).attr('data-id');
		var condition = this.checked;
		toggleStars(regid, condition);
		if (!condition) {
			checkStars(regid, condition);
			$('#system [data-regid="' + regid + '"]').each(function() {this.checked = false;});
		}
/*	Составление строки из айдишников нужных регионов, осталось для фильтрации через AJAX */
	});
	
	/* Выделение всех систем региона */
	$('#system input[name="region"]').on('click', function() {
		var regid = $(this).attr('data-regid');
		var condition = this.checked;
		checkStars(regid, condition);
	});
	
	/* Прогрузка систем после загрузки страницы */
	if ($('#loading').attr('data-why') == 'sysfilters') {
		$('#shadow').show();
		$('#loading').show();
		$('#annotation').text('Загружаем системы из txt-файлов');
		$('#progressbar div').css('width', '3%');
		var regionset = '';
		var r = '';
		
		$('#system input[name="region"]').each(function() {													// Собираем список регионов в строку
			var regID = $(this).attr('data-regid');
			r += ',' + regID;
		});
		
		regionset = escape(r.substr(1));																						// Убираем ненужную запятую в начале строки
		writeSystemList(regionset);																									// Пишем в документ чекбоксы систем выбранных регионов
	}

	/* Получаем из блока JSON-строку чтобы нарисовать по ней график */
	if (document.getElementById('strForChart') !== null) {
		eval("array = " + $('#strForChart').text());
		customChart(array, 'daily');
	}
	
	/* При клике в поле "Ссылка на график" выделяем весь текст в нем */
	$('#graphLink').click(function() {
		this.select();
	});
	
/* End of READY() */
});


/**
*	
*	Функция логина от uLogin
*	
**/

function login(token){
	// Отправляем AJAX-запрос к ним
	$.getJSON("//ulogin.ru/token.php?host=" + encodeURIComponent(location.toString()) + "&token=" + token + "&callback=?",
	function(data){
		data=$.parseJSON(data.toString());
		if(!data.error){
			document.cookie = 'uid=' + data.uid;
			window.location.reload(); 																								// Костыль
		}
	});
}

/**
*	
*	Отбираем системы по регионам  записываем их в блок фильтра
*	@param regions - Строка, в которой ID нужных регионов записаны через запятую
*	@return void
*	
**/
	
function writeSystemList(regions) {
	var sysinputs = {};
	/* Первым делом получаем список систем для указанных в параметре регионов */
	$.ajax({
		type: 'GET',
		url: 'getsystems',
		data: {'regions' : regions},
		dataType: 'json',
		success: function(data) {
		
		// Склеиваем чекбоксы
			for (sysid in data) {
				var sysinfo = data[sysid];
				var ss = parseFloat(sysinfo.security.toFixed(1));												// Нам нужен СС системы чтобы раскрасить его в нужный цвет
				
				if (ss === 1.0) color = 'skyblue';
				if (ss <= 0.9 && ss > 0.6) color = 'green';
				if (ss <= 0.6 && ss > 0.4) color = 'yellow';
				if (ss <= 0.4 && ss > 0.0) color = 'orange';
				if (ss <= 0.0) color = 'red';
				
				// Моя придумка: помечаем регионы ВХ
				if (sysinfo['name'].search('/J\d{6}/') != -1) sysname = '&lt;WH&gt; ' + sysinfo['name'];
				else sysname = sysinfo['name'];
				
				// Формируем массив HTML-строк, по строке на каждый регион из входящего списка
				sysinputs[ sysinfo['regionID'] ] += '<label style="display: none;"><input type="checkbox" name="system" data-name="' + sysname + '" data-id="' + sysid + '" data-regid="' + sysinfo['regionID'] + '"><div class="ss" style="color:' + color + '">' + ss + '</div><span>' + sysname + '</span></label>';
			}
			
			// Записываем системы для каждого региона
			var width = 3;
			$('#system input[name="region"]').each(function() {
				var regID = $(this).attr('data-regid');
				var regName = $(this).parent().text();
				var inputs = sysinputs[regID].replace(/^undefined/, '');
				$(this).parent().after(inputs);
				$('#progressbar div').css('width', ++width + '%');											// Прибавляем прогрессбар
				$('#annotation').text('Загружаем системы для региона ' + regName);
			});
			
			/* Устанавливаем состояние всех чекбоксов в зависимости от GET`а */
			var get = unescape(window.location.search.substring(1)).replace('+', ' ').split('&');
			if (get[0] != '') {
				var query = {};
				
				for (i in get) {
					elem = get[i].split('=');
					query[ elem[0] ] = elem[1].replace(/\_\d+/g, '').split(',');					// Разбираем GET и превращаем его в массив
				}
				
				$('.graphfilter input').each(function() {
					this.checked = false;																									// Очищаем выделение
					
					if ($(this).attr('name') !== undefined) name = $(this).attr('name').replace('system', 'star');
					
					if (query.hasOwnProperty(name)) {																			// Отсеиваем чекбоксы, упомянутые в запросе
					
						for (i in query[ name ]) {
							value = query[ name ][i];
							
							if ($(this).attr('data-name') == value || $(this).attr('data-time') == value) {
							
								if ($(this).attr('name') == 'region') {
									regid = $(this).attr('data-id');
									// Все системы упомянутых регионов нужно показать
									$('#system input[data-regid="' + regid + '"]').parent().show();
								}
								
								this.checked = true;																						// Расставляем нужные галочки
							}
							
						}
						
					}
					
				});
				
			}
			
		},
		complete: function() {																											// После записи систем и расстановки галочек, закрываем прогрессбар
			$('#shadow').hide();
			$('#loading').hide();
		}
		});
}

/* check/uncheck систем в зависимости от региона и состояния его чекбокса */
function checkStars(regid, state) {
	$('input[name="system"][data-regid="' + regid + '"]').each(function() {
		this.checked = state;
	});
}

/* show/hide систем в зависимости от региона и состояния его чекбокса */
function toggleStars(regid, state) {
	$('input[data-regid="' + regid + '"]').each(function() {
		if (state) $(this).parent().show(); else $(this).parent().hide();
		// this.checked = state;
	});
}

/**
*	
*	Отрисовка графика по входным параметрам
* @param time - тип графика часовой/дневной/месячный
* @param mode - тип графика система/регион
* @param region - регионы
* @param star - системы
*	@return void
*	
**/

function drawGraph(time, mode, region, star) {			// На время разработки определю дефолтную отрисовку систем, регионы появятся много позже
	var time = $('input[name="time"]:checked').attr('data-time') ? $('input[name="time"]:checked').attr('data-time') : 'daily';
	var mode = 'system';
	var regions = {};
	var stars = {};
	var region = '';
	var star = '';
	var link = $('#graphLink').val().replace(/\?.+/,'');
	var checked = $('input[name="system"]:checked');
	
	// В зависимости от расставленных галочек, составляем массивы выбранных систем и регионов Ключи - видимое название, Значения - фактическое
	checked.each(function() {
		stars[ $(this).attr('data-name') + '_' + $(this).parent().children('.ss').text().replace('.', '') ] = $(this).attr('data-name');
		regions[ $('input[name="region"][data-id="' + $(this).attr('data-regid') + '"]').attr('data-name') ] = $('input[name="region"][data-id="' + $(this).attr('data-regid') + '"]').attr('data-name');
	});
	
	// Составляем строки видимых названий регионов и систем
	for (i in stars) { star += ',' + i; }
	for (i in regions) { region += ',' + i; }
	star = star.substr(1);
	region = region.substr(1);
	
	$('#shadow').show();																													// Показываем прогресс-бар
	$('#loading').show();
	$('#annotation').text('Рисуем график активности');
	$('#progressbar div').css('width', '0');
	
	$.ajax({																																			// Получаем из пхп форматированную строку для графика
		type: 'GET',
		url: 'drawGraph',
		data: {'time': time, 'mode': mode, 'region': region, 'star': star},
		success: function(data) {
			eval("array = " + data);																									// Единственный рабочий способ полученную строку без ошибок перевести в массив
			customChart(array, time);																									// Рисуем график
			// Составляем и записываем в нужный блок ссылку на график, закрываем прогрессбар
			link += '?time=' + time + '&mode=' + mode + '&region=' + region + '&star=' + star;
			$('#graphLink').val(link);
			$('#shadow').hide();
			$('#loading').hide();
		}
	});

}

/**
*	
*	Отрисовка графика по входным параметрам
* @param array - массив данных, по которым график рисуется
* @param time - тип графика часовой/дневной/месячный, указывает формат даты и заголовок графика
*	@return void
*	
**/

function customChart(array, time) {
	var tickset = new Array();
	var i = 0;
	var data = new google.visualization.DataTable();															// Инициализируем график
	data.addColumn('datetime', 'Date');																						// Добавляем заголовок для оси Ох и тип данных
	
	for (col in array.head) data.addColumn('number', array.head[col]);						// Добавляем видимые названия систем и типы данных для них
	
	for (row in array.content) {																									// Составляем массив вертикальных "рисочек"
		if (i % 2 == 0) {																														// Для данного кода вертикальные линии отображаются для каждого второго часа
			var date = array.content[row][0];
			var tickName = myDate.morph(date, time);
			var tick = {v: date, f: tickName};
			tickset = tickset.concat(tick);
		}
		i++;
	}
	
	data.addRows(array.content);																									// Заполняем массив данными
	
	// Выставляем опции для графика в соответствии с гугловой таблицей опций: https://google-developers.appspot.com/chart/interactive/docs/gallery/areachart#Configuration_Options
	var options = {
		title: time + ' Jumps',
		height: 500,
		chartArea: {left:80,top:50,width:"75%",height:"65%"},
		hAxis: {title: 'Date',  titleTextStyle: {color: '#333'}, ticks: tickset},
		vAxis: {title: 'Jumps', minValue: 0, gridlines: {color: '#ccc', count: 10}, minorGridlines: {color: '#eee', count: 4}}
	};

	// Создаем объект
	var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
	// Рисуем график
	chart.draw(data, options);
}

/**
*	
*	Объект для работы с датой
*	
**/

var myDate = {

/**
*	
*	Переформатирование даты в строку
* @param date - дата в своем формате
* @param mode - мод часовой/дневной/месячный, указывает формат даты
*	@return res - дата в формате строки
*	
**/

	morph: function(date, mode) {
		var day = this.zerofill(date.getDate().toString(), 2);
		var mon = this.zerofill(parseInt(date.getMonth().toString()) + 1, 2);
		var year = date.getFullYear().toString();
		var hour = this.zerofill(date.getHours().toString(), 2);
		var min = this.zerofill(date.getMinutes().toString(), 2);
		var res = '';
		if (mode == 'daily') res = day + '-' + mon + ' ' + hour + ':' + min;
		if (mode == 'monthly') res = day + '-' + mon + '-' + year;
		if (hour == '13') res += ' [DT]';
		return res;
	},
	
/**
*	
*	Дополнение числа нулями с левой стороны
* @param num - число
* @param len - нужная длина строки
*	@return outStr - число в строчном формате, дополненное слева нулями до нужной длины строки
*	
**/

	zerofill: function(num, len) {
		var numLen = (num+'').length;
		var outStr = '';
		for (i = 0; i < len-numLen; i++) {
			outStr += '0';
		}
		outStr += num;
		return outStr;
	}
	
};
