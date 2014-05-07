$(document).ready(function() {
	var canvas=document.getElementById("map");
	var cxt=canvas.getContext("2d");
	var width = canvas.width;
	var height = canvas.height;
	var padding = 50;
	var depth = 500
	var map = JSON.parse($('#strForMap').text());
	var dots = map.dots;
	var jumps = map.jumps;
	// var dots = {};
	// var jumps = {};
	var pi = Math.PI;
	var zenit = 0;
	var azimut = 0;
	var maxx = minx = parseFloat(dots[0].pos_x);
	var maxy = miny = parseFloat(dots[0].pos_y);
	var maxz = minz = parseFloat(dots[0].pos_z);
	var coords = {
		//   'a':{'x':-100,'y':100,'z':100}
		// , 'b':{'x':100,'y':100,'z':100}
		// , 'c':{'x':100,'y':-100,'z':100}
		// , 'd':{'x':-100,'y':-100,'z':100}
		// , 'e':{'x':-100,'y':100,'z':-100}
		// , 'f':{'x':100,'y':100,'z':-100}
		// , 'g':{'x':100,'y':-100,'z':-100}
		// , 'h':{'x':-100,'y':-100,'z':-100}
		// , 'z':{'x':0,'y':0,'z':0}
		// , 'width':{'x':200,'y':0,'z':0}
		// , 'height':{'x':0,'y':200,'z':0}
		// , 'length':{'x':0,'y':0,'z':200}
	};
	var visibleDots = {
		//   'a':{'x':-100,'y':100,'z':100}
		// , 'b':{'x':100,'y':100,'z':100}
		// , 'c':{'x':100,'y':-100,'z':100}
		// , 'd':{'x':-100,'y':-100,'z':100}
		// , 'e':{'x':-100,'y':100,'z':-100}
		// , 'f':{'x':100,'y':100,'z':-100}
		// , 'g':{'x':100,'y':-100,'z':-100}
		// , 'h':{'x':-100,'y':-100,'z':-100}
		// , 'z':{'x':0,'y':0,'z':0}
		// , 'width':{'x':200,'y':0,'z':0}
		// , 'height':{'x':0,'y':200,'z':0}
		// , 'length':{'x':0,'y':0,'z':200}
	};
	var currentRegion = $('#mapRegion option:selected').val() != 0 ? $('#mapRegion option:selected').html() : '';
	for (i in dots) {
		var dot = dots[i];
		coords[dot.name] = {};
		visibleDots[dot.name] = {};
		if (dot.regName == currentRegion || currentRegion == '') {
			if (parseFloat(dot.pos_x) < minx) minx = parseFloat(dot.pos_x);
			if (parseFloat(dot.pos_x) > maxx) maxx = parseFloat(dot.pos_x);
			if (parseFloat(dot.pos_y) < miny) miny = parseFloat(dot.pos_y);
			if (parseFloat(dot.pos_y) > maxy) maxy = parseFloat(dot.pos_y);
			if (parseFloat(dot.pos_z) < minz) minz = parseFloat(dot.pos_z);
			if (parseFloat(dot.pos_z) > maxz) maxz = parseFloat(dot.pos_z);
		}
	}
	var divx = maxx - minx;
	var divy = maxy - miny;
	var divz = maxz - minz;
	var scaleX = (width-padding*2) / divx;
	var scaleY = (height-padding*2) / divy;
	var scaleZ = (depth-padding*2) / divz;
	calc();
	draw();
	allowSelect();
	route();

	$(canvas).mousedown(function(e) {
		$('.star').remove();
		var pos = $(this).offset();
		$('.startx').html(e.pageX-pos.left);
		$('.starty').html(e.pageY-pos.top);
		$(canvas).bind('mousemove', drag);
		$(window).bind('mouseup', function(ev) {
		});
	});

	$(canvas).mouseup(function() {
		$(canvas).unbind('mousemove');
		$(window).unbind('mouseup');
		allowSelect();
	});
	$(document).on('click', '.star', function() {
		if (window.location.search == '') {
			var newLoc = window.location.pathname + '?reg=' + escape($(this).attr('data-name'));
			window.location = newLoc;
		}
	});

	function allowSelect() {
		for (i in visibleDots) {
			var dot = visibleDots[i];
			$('.interaction').append('<div class="star" data-name="' + i + '"><img src="/source/img/starCircle.png"></div>');
			$('.star[data-name="' + i + '"]').css({'margin-left':dot["x"]+width/2-10, 'margin-top':-dot["y"]+height/2-10});
		}
	}

	function drag(ev) {
		var pos = $(this).offset();
		// $('.x').html(ev.pageX-pos.left-parseInt($('.startx').html()));
		// $('.y').html(ev.pageY-pos.top-parseInt($('.starty').html()));
		azimut += (ev.pageX-pos.left-parseInt($('.startx').html()))/500;
		zenit += (ev.pageY-pos.top-parseInt($('.starty').html()))/500;
		draw();
		$('.startx').html(ev.pageX-pos.left);
		$('.starty').html(ev.pageY-pos.top);
	}

	function calc() {
		for (i in dots) {
			var dot = dots[i];
			var x = (parseFloat(dot.pos_x) - minx - divx / 2) * scaleX;
			var y = (parseFloat(dot.pos_y) - miny - divy / 2) * scaleY;
			var z = (parseFloat(dot.pos_z) - minz - divz / 2) * scaleZ;
			coords[dot.name]["x"] = x;
			visibleDots[dot.name]["x"] = x;
			coords[dot.name]["y"] = y;
			visibleDots[dot.name]["y"] = y;
			coords[dot.name]["z"] = z;
		}
	}

	function draw() {
		canvas.width = width;
		cxt.fillStyle = "black";
		cxt.fillRect(0, 0, width, height);
		cxt.fillStyle = 'white';
		cxt.strokeStyle = 'grey';
		cxt.font = "normal 8pt Sans-Serif";
		for (i in jumps) {
			var jump = jumps[i];
			var newfromx = coords[jump.fromName]["x"]*Math.cos(azimut) - coords[jump.fromName]["y"]*Math.sin(azimut);
			var newfromy = coords[jump.fromName]["x"]*Math.cos(zenit)*Math.sin(azimut) + coords[jump.fromName]["y"]*Math.cos(zenit)*Math.cos(azimut) - coords[jump.fromName]["z"]*Math.sin(zenit);
			var newfromz = coords[jump.fromName]["x"]*Math.sin(zenit)*Math.sin(azimut) + coords[jump.fromName]["y"]*Math.sin(zenit)*Math.cos(azimut) + coords[jump.fromName]["z"]*Math.cos(zenit);
			var fromPerspective = (newfromz + divz * scaleZ) / divz / scaleZ / 10 + 0.9;
			var newtox = coords[jump.toName]["x"]*Math.cos(azimut) - coords[jump.toName]["y"]*Math.sin(azimut);
			var newtoy = coords[jump.toName]["x"]*Math.cos(zenit)*Math.sin(azimut) + coords[jump.toName]["y"]*Math.cos(zenit)*Math.cos(azimut) - coords[jump.toName]["z"]*Math.sin(zenit);
			var newtoz = coords[jump.toName]["x"]*Math.sin(zenit)*Math.sin(azimut) + coords[jump.toName]["y"]*Math.sin(zenit)*Math.cos(azimut) + coords[jump.toName]["z"]*Math.cos(zenit);
			var toPerspective = (newtoz + divz * scaleZ) / divz / scaleZ / 10 + 0.9;
			cxt.beginPath();
			cxt.moveTo(newfromx*fromPerspective+width/2+2, -newfromy*fromPerspective+height/2+2);
			cxt.lineTo(newtox*toPerspective+width/2+2, -newtoy*toPerspective+height/2+2);
			cxt.closePath();
			cxt.stroke();
		}
		for (i in coords) {
			var dot = coords[i];
			var newx = dot["x"]*Math.cos(azimut) - dot["y"]*Math.sin(azimut);
			var newy = dot["x"]*Math.cos(zenit)*Math.sin(azimut) + dot["y"]*Math.cos(zenit)*Math.cos(azimut) - dot["z"]*Math.sin(zenit);
			var newz = dot["x"]*Math.sin(zenit)*Math.sin(azimut) + dot["y"]*Math.sin(zenit)*Math.cos(azimut) + dot["z"]*Math.cos(zenit);
			var perspective = (newz + divz * scaleZ) / divz / scaleZ / 10 + 0.9;
			cxt.fillRect(newx*perspective+width/2, -newy*perspective+height/2, 3, 3);
			cxt.fillText(i,newx*perspective+width/2, -newy*perspective+height/2-1);
			visibleDots[i]["x"] = newx*perspective;
			visibleDots[i]["y"] = newy*perspective;
			// cxt.beginPath();
			// cxt.moveTo(0+width/2+2, 0+height/2+2);
			// cxt.lineTo(newx+width/2+2, -newy+height/2+2);
			// cxt.closePath();
			// cxt.stroke();
		}
	}

	$('#drawMap').click(function() {
		window.location = window.location.pathname + "?reg=" + escape($('#mapRegion option:selected').html());
	});

	$(document).on('mouseenter', '.star', function() {
		var starName = $(this).attr('data-name');
		$(this).append('<span class="starName">' + starName + '</span>');
	});
	$(document).on('mouseleave', '.star', function() {
		$(this).children('.starName').remove();
	});

	function route(from, to) {
		from = from ? from : 'Deklein';
		to = to ? to : 'Domain';
		var routeDots = map.routeDots;
		var d = {}; // Длина пути
		var p = {}; // Кратчайший путь
		var u = {}; // Посещенные вершины
		var n = {}; // Вершины для посещения
		var now = '';
		var counter = 0;
		var min = 0;
		var mindot = '';
		d[from] = 0;
		u[from] = d[from];
		now = from;
		for (i in dots) {
			if (dots[i]['name'] != from) {
				d[dots[i]['name']] = 10000;
			}
		}

		while (now != to/* && counter < 100*/) {
			var trigger = false;
			// console.log("Entering to " + now, d[now]);
			delete n[now];
			u[now] = d[now];
			for (i in routeDots[now]) {
				if (d[i] > d[now] + routeDots[now][i] && !u.hasOwnProperty(i)) {
					d[i] = d[now] + routeDots[now][i];
				}
			// console.log("Looking " + i, d[i]);
				if (!u.hasOwnProperty(i)) {
					triger = true;
					n[i] = d[i];
					min = d[i];
					mindot = i;
					// console.log("Setting to minimum: " + i, d[i]);
				}
			}
			for (i in n) {
				// console.log("Calculating minimum for " + i, d[i], d[i] <= min && !u.hasOwnProperty(i) && trigger == false);
				if (d[i] <= min && !u.hasOwnProperty(i) && trigger == false) {
					min = d[i];
					mindot = i;
				}
			}
			// console.log("Minimum: " + mindot, min);
			delete n[mindot];
			u[mindot] = min;
			now = mindot;
			counter++;
		}
		console.log(now != to, counter < 100);
		p[ d[now] ] = now;
		counter = 0;
		while (now != from/* && counter < 100*/) {
			for (i in routeDots[now]) {
				if (d[i] < d[now]) {
					p[ d[i] ] = i;
					now = i;
				}
			}
		}

		console.log(d,p,u,n);
	}

});