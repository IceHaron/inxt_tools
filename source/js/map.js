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
	var visibleDots = {};
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
			var newtox = coords[jump.toName]["x"]*Math.cos(azimut) - coords[jump.toName]["y"]*Math.sin(azimut);
			var newtoy = coords[jump.toName]["x"]*Math.cos(zenit)*Math.sin(azimut) + coords[jump.toName]["y"]*Math.cos(zenit)*Math.cos(azimut) - coords[jump.toName]["z"]*Math.sin(zenit);
			cxt.beginPath();
			cxt.moveTo(newfromx+width/2+2, -newfromy+height/2+2);
			cxt.lineTo(newtox+width/2+2, -newtoy+height/2+2);
			cxt.closePath();
			cxt.stroke();
		}
		for (i in coords) {
			var dot = coords[i];
			var multiplier = dot["z"] > 0 ? 1.1 : 0.9;
			var newx = dot["x"]*Math.cos(azimut) - dot["y"]*Math.sin(azimut);
			var newy = dot["x"]*Math.cos(zenit)*Math.sin(azimut) + dot["y"]*Math.cos(zenit)*Math.cos(azimut) - dot["z"]*Math.sin(zenit);
			// var newz = dot["x"]*Math.sin(zenit)*Math.sin(azimut) + dot["y"]*Math.sin(zenit)*Math.cos(azimut) + dot["z"]*Math.cos(zenit);
			// console.log(i,dot,newx,newy);
			cxt.fillRect(newx+width/2, -newy+height/2, 4, 4);
			cxt.fillText(i,newx+width/2, -newy+height/2-1);
			visibleDots[i]["x"] = newx;
			visibleDots[i]["y"] = newy;
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

});
