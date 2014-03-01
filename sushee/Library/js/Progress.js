function Progress(contextID, barsNumber, centerObject, innerRadius, sizeBarObject, colorRGBObject) 
{
	this.context = ($id(contextID)).getContext("2d");
	this.bars = barsNumber;
	this.center = centerObject;
	this.innerRadius = innerRadius;
	this.size = sizeBarObject;
	this.color = colorRGBObject;
	
	//this.nextAnimation(this);
}

Progress.prototype.context/*canvas*/;
Progress.prototype.bars/*number*/;
Progress.prototype.center/*object {x,y}*/;
Progress.prototype.innerRadius/*number*/;
Progress.prototype.size/*object {width,height}*/;
Progress.prototype.color/*object {red,green,blue}*/;
Progress.prototype.animating/*boolean*/;
Progress.prototype.currentOffset = 0/*number*/;

Progress.prototype.makeRGBA = function() 
{
	return "rgba(" + [].slice.call(arguments, 0).join(",") + ")";
}
	
Progress.prototype.drawBlock = function(ctx/*canvas*/, barNo/*number*/) 
{
	var size = this.size;
	var bars = this.bars;
	var color = this.color;
	ctx.fillStyle = this.makeRGBA(color.red, color.green, color.blue, (bars+1-barNo)/(bars+1));
	ctx.fillRect(-size.width/2, 0, size.width, size.height);
}

Progress.prototype.calculateAngle = function(barNo/*number*/) /*number*/
{
	return 2 * barNo * Math.PI / this.bars;
}

Progress.prototype.calculatePosition = function(barNo/*number*/) /*object {x,y,angle}*/
{
	var angle = this.calculateAngle(barNo);
	var innerRadius = this.innerRadius;
	
	return {
		y: (innerRadius * Math.cos(-angle)),
		x: (innerRadius * Math.sin(-angle)),
		angle: angle
	};
}

Progress.prototype.draw = function(ctx/*canvas*/, offset/*number*/) /*void*/
{
	this.clearFrame(ctx);
	ctx.save();

	var center = this.center;
	ctx.translate(center.x, center.y);

	var bars = this.bars;
	for(var i = 0; i < bars; i++)
	{
		
		var curbar = (offset + i) % bars;
		
		pos = this.calculatePosition(curbar);
		
		ctx.save();
		
		ctx.translate(pos.x, pos.y);
		
		ctx.rotate(pos.angle);
		
		this.drawBlock(this.context, i);
		ctx.restore();
	}
	
	ctx.restore();
}

Progress.prototype.clearFrame = function(ctx) 
{
	try
	{
		ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.width);
	}
	catch(e)
	{
		console.log(ctx.canvas.width);
	}
}

Progress.prototype.nextAnimation = function(me)
{
	if (!me.animating) 
	{
		return;
	};
	
	me.currentOffset = (me.currentOffset + 1) % me.bars;
	me.draw(me.context, me.currentOffset);
	setTimeout(me.nextAnimation,50,me);
}

Progress.prototype.stop = function()
{
	if(this.animating)
	{
		this.animating = false;
		this.stop();
	}
}

Progress.prototype.start = function ()
{
	if(!this.animating)
	{
		this.animating = true;
		this.nextAnimation(this);
	}
}

Progress.prototype.toggle = function ()
{
	if(this.animating == true)
	{
		this.stop();
	}
	else 
	{
		this.start();
	}
}
