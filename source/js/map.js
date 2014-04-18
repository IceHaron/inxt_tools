var graph = {
"nodes": [
{"name": "Center", "xpos":0,"ypos":0},
{"name": "N", "xpos":0,"ypos":-2},
{"name": "NE", "xpos":2,"ypos":-2},
{"name": "E", "xpos":2,"ypos":0},
{"name": "SE", "xpos":2,"ypos":2},
{"name": "S", "xpos":0,"ypos":2},
{"name": "SW", "xpos":-2,"ypos":2},
{"name": "W", "xpos":-2,"ypos":0},
{"name": "NW", "xpos":-2,"ypos":-2}
],
"edges": [
{"src": "Center", "dest": "N"},
{"src": "Center", "dest": "NE"},
{"src": "Center", "dest": "E"},
{"src": "Center", "dest": "SE"},
{"src": "Center", "dest": "S"},
{"src": "Center", "dest": "SW"},
{"src": "Center", "dest": "W"},
{"src": "Center", "dest": "NW"}
]
};

(function($){
 var Renderer = function(canvas)
 {
  var canvas = $(canvas).get(0);
  var ctx = canvas.getContext("2d");
  var particleSystem;

  var that = {
   init:function(system){
    //начальная инициализация
    particleSystem = system;
    particleSystem.screenSize(canvas.width, canvas.height); 
    particleSystem.screenPadding(0);
    // that.initMouseHandling();
   },
      
   redraw:function(){
    //действия при перересовке
    ctx.fillStyle = "white"; //белым цветом
    ctx.fillRect(0,0, canvas.width, canvas.height); //закрашиваем всю область
   
    particleSystem.eachEdge( //отрисуем каждую грань
     function(edge, pt1, pt2){ //будем работать с гранями и точками её начала и конца
      ctx.strokeStyle = "rgba(0,0,0, .333)"; //грани будут чёрным цветом с некой прозрачностью
      ctx.lineWidth = 1; //толщиной в один пиксель
      ctx.beginPath();  //начинаем рисовать
      ctx.moveTo(pt1.x, pt1.y); //от точки один
      ctx.lineTo(pt2.x, pt2.y); //до точки два
      ctx.stroke();
    });
 
    particleSystem.eachNode( //теперь каждую вершину
     function(node, pt){  //получаем вершину и точку где она
      var w = 10;   //ширина квадрата
      ctx.fillStyle = "orange"; //с его цветом понятно
      ctx.fillRect(pt.x-w/2, pt.y-w/2, w,w); //рисуем
      ctx.fillStyle = "black"; //цвет для шрифта
      ctx.font = 'italic 13px sans-serif'; //шрифт
      ctx.fillText (node.name, pt.x+8, pt.y+8); //пишем имя у каждой точки
    });       
   },
  
   initMouseHandling:function(){ //события с мышью
    var dragged = null;   //вершина которую перемещают
    var handler = {
     clicked:function(e){ //нажали
      var pos = $(canvas).offset(); //получаем позицию canvas
      _mouseP = arbor.Point(e.pageX-pos.left, e.pageY-pos.top); //и позицию нажатия кнопки относительно canvas
      dragged = particleSystem.nearest(_mouseP); //определяем ближайшую вершину к нажатию
      if (dragged && dragged.node !== null){
       dragged.node.fixed = true; //фиксируем её
      }
      $(canvas).bind('mousemove', handler.dragged); //слушаем события перемещения мыши
      $(window).bind('mouseup', handler.dropped);  //и отпускания кнопки
      return false;
     },
     dragged:function(e){ //перетаскиваем вершину
      var pos = $(canvas).offset();
      var s = arbor.Point(e.pageX-pos.left, e.pageY-pos.top);
 
      if (dragged && dragged.node !== null){
       var p = particleSystem.fromScreen(s);
       dragged.node.p = p; //тянем вершину за нажатой мышью
      }
 
      return false;
     },
     dropped:function(e){ //отпустили
      if (dragged===null || dragged.node===undefined) return; //если не перемещали, то уходим
      if (dragged.node !== null) dragged.node.fixed = true; //если перемещали - отпускаем
      dragged = null; //очищаем
      $(canvas).unbind('mousemove', handler.dragged); //перестаём слушать события
      $(window).unbind('mouseup', handler.dropped);
      _mouseP = null;
      return false;
     }
    }
    // слушаем события нажатия мыши
    $(canvas).mousedown(handler.clicked);
   },
      
  }
  return that;
 }    

$(document).ready(function(){
	sys = arbor.ParticleSystem({precision:0}); // создаём систему
	sys.parameters({gravity:false}); // гравитация вкл
	sys.renderer = Renderer("#viewport") //начинаем рисовать в выбраной области
	
	$.each(graph.nodes, function(i,node){
		console.log(sys.addNode(node.name, {fixed:"true", x: node.xpos, y: node.ypos})); //добавляем вершину
	});
	$.each(graph.edges, function(i,edge){
		sys.addEdge(sys.getNode(edge.src),sys.getNode(edge.dest)); //добавляем грань
	});

})

})(this.jQuery)