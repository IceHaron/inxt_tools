/**
*	
*	Ну конечно же, всякие разные штуки при завершении загрузки страницы
*	
**/
$(document).ready(function() {

/* Получаем из блока JSON-строку чтобы нарисовать по ней график */
	if (document.getElementById('strForChart') !== null) {
		eval("array = " + $('#strForChart').text());
		customChart(array, 'hourly');
	}
	
	/* При клике в поле "Ссылка на график" выделяем весь текст в нем */
	$('#graphLink').click(function() {
		this.select();
	});
	
/* При клике на регион выводим модальное окно с его системами */
	$('.regButton').click(function() {
		var regID = $(this).attr('data-id');
		$('#shadow').show();
		$('#loading').show();
		getSystems(regID);
	});
	
/* При выборе системы в модальном окне заносим ее в блок выбранных систем, при снятии галки убираем систему из списка выбранных */
	$(document).on('change', '.systemHolder input', function() {
		var name = $(this).attr('data-name');
		var id = $(this).attr('data-id');
		var regid = $(this).attr('data-regid');
		var regname = $(this).parent().parent().parent().children().eq(0).text();
		var ss =  $(this).next()[0].outerHTML;
		if (this.checked) $('#selectedSystems').append('<div class="selectedSystem" data-regid="' + regid + '" data-id="' + id + '" data-name="' + name + '">' + ss + name + '<img class="deselectSystem" src="/source/img/delete.png"><div class="sysRegHolder"><div class="sysRegion">' + regname + '</div></div></div>');
		else $('#selectedSystems .selectedSystem[data-name="' + name + '"]').remove();
		somethingChanged = true;
	});

/* Когда меняем временную характеристику, нужно тоже перерисовывать график */
	$(document).on('change', 'input[name="time"]', function() {
		somethingChanged = true;
	});
	
/* Убираем систему из списка при клике на крестик около нее */
	$(document).on('click', '.deselectSystem', function() {
		$(this).parent().remove();
		if ($('.selectedSystem').length != 0) somethingChanged = true;
	});
	
/* Скрытие облака регионов и выбранных систем при клике на соответствующую кнопку */
	$('.hideRegs').click(function() {
		$('#regionCloud').toggle();
		$('#selectedSystems').toggle();
	});
	
	/* Формирование опций для селектора пресетов */
	var presetNumber = 1;
	var presets = '';
	while (localStorage.getItem('preset_' + presetNumber) !== null) {
		var preset = JSON.parse(localStorage.getItem('preset_' + presetNumber));
		var active = preset.link == window.location ? 'selected' : '';
		presets += '<option value="' + presetNumber + '" ' + active + '>' + preset.name + '</option>';
		presetNumber++;
	}
	$('#selectPreset').append(presets);
		
/* Блокирование/разблокирование кнопки "Сохранить пресет" */
	$('#presetName').keyup(function() {
		if ($(this).val() != '') $('#savePreset').attr('disabled', false);
		else $('#savePreset').attr('disabled', true);
	});
	
/* Сохранение пресета в localStorage */
	$('#savePreset').click(function() {
		var presetName = $('#presetName').val();
		var presetNumber = 1;
		var graphLink = $('#graphLink').val();
		var string = JSON.stringify({name: presetName, link: graphLink});
		while (localStorage.getItem('preset_' + presetNumber) != null) presetNumber++;
		localStorage.setItem('preset_' + presetNumber, string);
		$('#selectPreset').append('<option value="' + presetNumber + '">' + presetName + '</option>');
	});
	
/* Загрузка пресета */
	$('#loadPreset').click(function() {
		var presetNumber = $('#selectPreset').val();
		if (presetNumber != '0') {
			window.location = JSON.parse(localStorage.getItem('preset_' + presetNumber)).link;
		} else {
			window.location = '/systemstats/show';
		}
	});
	
/* Удаление пресета */
	$('#deletePreset').click(function() {
		var presetNumber = $('#selectPreset').val();
		localStorage.removeItem('preset_' + presetNumber, null);
		$('option[value="' + presetNumber + '"]').remove();
	});
	
/* Раз в пять секунд проверяем изменения сета систем и рисуем график */
	setInterval(function() {
		if (somethingChanged) drawGraph();
		somethingChanged = false;
	}, 5000);

/* END OF READY */
});

/**
*	
*	Отбираем системы по регионам  записываем их в блок фильтра
*	@param regions - Строка, в которой ID нужных регионов записаны через запятую
*	@return void
*	
**/
function getSystems(regions) {
	var sysinputs = {};
	/* Первым делом получаем список систем для указанных в параметре регионов */
	$.ajax({
		type: 'GET',
		url: 'getsystems',
		data: {'regions' : regions},
		dataType: 'json',
		success: function(data) {
			$('#systemSetHolder').empty();
		// Делаем модальное окно
			for (order in data) {
				var sysid = data[order]['id'];
				var sysinfo = data[order];
				var ss = SecurityStanding.format(sysinfo.security);												// Нам нужен СС системы чтобы раскрасить его в нужный цвет
				if (!sysinputs.hasOwnProperty(sysinfo['regname'])) sysinputs[ sysinfo['regname'] ] = '';
				var color = SecurityStanding.paint(ss);
				
				// Моя придумка: помечаем регионы ВХ
				if (sysinfo['name'].search('/J\d{6}/') != -1) sysname = '&lt;WH&gt; ' + sysinfo['name'];
				else sysname = sysinfo['name'];
				
				// Формируем массив HTML-строк, по строке на каждый регион из входящего списка
				sysinputs[ sysinfo['regname'] ] += '<div class="systemHolder"><label><input type="checkbox" name="system" data-name="' + sysname + '" data-id="' + sysid + '" data-regid="' + sysinfo['regionID'] + '"><div class="ss" style="color:' + color + '">' + ss + '</div><span>' + sysname + '</span></label></div>';
			}
			var fullModalContent = '';
			for (i in sysinputs) {
				var content = sysinputs[i];
				var regionHolder = '<div class="regionHolder"><div><span class="caption">' + i + '</span></div>' + content + '</div>';
				fullModalContent += regionHolder;
			}
			$('#loading').hide();
			$('#systemSetHolder').append(fullModalContent).show();
			
			
			/* Устанавливаем состояние всех чекбоксов в зависимости от выбранных систем */
			var get = $('.selectedSystem')
			if (get[0] != '') {
				var query = {};
				
				$('.regionHolder input').each(function() {
					this.checked = false;																									// Очищаем выделение
				});
				
				get.each(function() {
					elem = $(this).attr('data-id');
					$('.regionHolder input[data-id="' + elem + '"]').each(function() {
						this.checked = true;
					});
				});

			}
			
		},
		complete: function() {																											// После записи систем и расстановки галочек, закрываем прогрессбар
			$('#loading').hide();
		}
		});
}

/* check/uncheck систем в зависимости от региона и состояния его чекбокса */
function checkSystems(regid, state) {
	$('input[name="system"][data-regid="' + regid + '"]').each(function() {
		this.checked = state;
	});
}

/* show/hide систем в зависимости от региона и состояния его чекбокса */
function toggleSystems(regid, state) {
	$('input[data-regid="' + regid + '"]').each(function() {
		if (state) $(this).parent().show(); else $(this).parent().hide();
		// this.checked = state;
	});
}

/**
*	
*	Отрисовка графика
*	@return void
*	
**/
function drawGraph() {
	var info = getInfo();
	makeChart(info.time, info.mode, info.subject);
}

/**
*	
*	Сбор информации для отрисовки графика
*	@return object - объект с нужной для графика информацией
*	
**/
function getInfo() {
	var time = $('input[name="time"]:checked').attr('data-time') ? $('input[name="time"]:checked').attr('data-time') : 'hourly';
	var mode = 'system';
	var subject = '';
	$('.selectedSystem').each(function() {
		subject += ',' + $(this).attr('data-name') + '_' + $(this).children('.ss').text().replace('.', '');
	});
	subject = subject.substr(1);
	
	return {'time' : time, 'mode' : mode, 'subject' : subject}
}

/**
*	
*	Отрисовка графика по входным параметрам
* @param time - тип графика часовой/дневной/месячный
* @param mode - тип графика система/регион
* @param subject - системы/регионы
*	@return void
*	
**/
function makeChart(time, mode, subject) {			// На время разработки определю дефолтную отрисовку систем, регионы появятся много позже
	var link = $('#graphLink').val().replace(/\?.+/,'');
	
	// $('#shadow').show();																													// Показываем прогресс-бар
	// $('#loading').show();
	$('#drawing').show();
	$('#annotation').text('Рисуем график активности');
	$('#progressbar div').css('width', '0');
	
	$.ajax({																																			// Получаем из пхп форматированную строку для графика
		type: 'GET',
		url: 'drawGraph',
		data: {'time': time, 'mode': mode, 'subject': subject},
		success: function(data) {
			eval("array = " + data);																									// Единственный рабочий способ полученную строку без ошибок перевести в массив
			customChart(array, time);																									// Рисуем график
			// Составляем и записываем в нужный блок ссылку на график, закрываем прогрессбар
			link += '?time=' + time + '&mode=' + mode + '&subject=' + escape(subject);
			$('#graphLink').val(link);
			// $('#shadow').hide();
			// $('#loading').hide();
			$('#drawing').hide();
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