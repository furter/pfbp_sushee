function EmptyValidator(msgError)
{
	this.msgError = msgError;
}

EmptyValidator.prototype.msgError;
EmptyValidator.prototype.name = 'EmptyValidator Validator';

EmptyValidator.prototype.isValid = function(string)
{
	return (string != '');
}

EmptyValidator.prototype.getName = function()
{
	return this.name;
}

EmptyValidator.prototype.getMsgError = function()
{
	return this.msgError;
}
