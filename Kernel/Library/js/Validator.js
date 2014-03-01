function Validator(input,strategy)
{
	var value = input.value;
	var id = input.id;
	var classes = input.className;
	
	this.strategy = strategy;
	
	if(! this.strategy.isValid(value))
	{
		input.className = classes + ' error';
		input.title = 'false';
		
		if(this.strategy.getMsgError() != '')
			alert(this.strategy.getMsgError());
	}
	else
	{
		input.className = getWithout(input.className,['error']);
		input.title = 'true';
	}
}

Validator.prototype.strategy;

Validator.prototype.init = function(strategy)
{
	this.setStrategy(strategy);	
}

Validator.prototype.isValid = function(string)
{
	return this.strategy.isValid(string);
}

Validator.prototype.getStrategy = function()
{
	return this.strategy;
}

Validator.prototype.setStrategy = function(strategy)
{
	this.strategy = strategy;
}

Validator.prototype.getName = function()
{
	return this.strategy.getName();
}
