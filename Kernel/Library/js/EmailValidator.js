function EmailValidator(msgError)
{
	this.msgError = msgError;
}

EmailValidator.prototype.name = 'Email Validator';
EmailValidator.prototype.msgError;

EmailValidator.prototype.isValid = function(string,msgError)
{
	if(new EmptyValidator('').isValid(string))
	{
		// TO DO
		var splittedAT = string.split('@');
		if(splittedAT.length == 2)
		{
			var splittedPoint = splittedAT[1].split('.');
			var l = splittedPoint.length;
			
			if(l > 1)
			{
				for (var i = 0; i < l-1 ; i++)
				{
					if(splittedPoint[i].length != 0)
					{
						continue;
					}
					else
					{
						return false;
					}
				}
				if(splittedPoint[l-1].length > 1)
				{
					return true;
				}	
			}
		}
	}
	
	return false;
}

EmailValidator.prototype.getMsgError = function()
{
	return this.msgError;
}

EmailValidator.prototype.getName = function()
{
	return this.name;
}

EmailValidator.prototype.toString = function()
{
	return this.getName();
}
