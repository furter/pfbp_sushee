function XMLLoader()
{
	this.xmlhttp = this.createXMLHTTP();
	this.setMethod('GET');
	if (!this.xmlhttp) return null;	
}

XMLLoader.prototype.url;
XMLLoader.prototype.params;
XMLLoader.prototype.callBack;
XMLLoader.prototype.handlerObject;
XMLLoader.prototype.handlerFunction;
XMLLoader.prototype.handlerParams;
XMLLoader.prototype.xmlhttp;
XMLLoader.prototype.method;
XMLLoader.prototype.isComplete;

XMLLoader.prototype.createXMLHTTP = function()/*void*/
{
	this.clearParams();
	
	var xmlhttp;
	try 
	{ 
		xmlhttp = new ActiveXObject("Msxml2.XMLHTTP"); 
	}
	catch (e) 
	{ 
		try 
		{ 
			xmlhttp = new ActiveXObject("Microsoft.XMLHTTP"); 
		}
		catch (e) 
		{ 
			try 
			{ 
				xmlhttp = new XMLHttpRequest(); 
			}
			catch (e) 
			{ 
				xmlhttp = false; 
			}
		}
	}
	return xmlhttp;
}

XMLLoader.prototype.clearParams = function()/*void*/
{
	this.params = '';
}

XMLLoader.prototype.addParam = function(key/*string*/,value/*string || number */)/*void*/
{
	if(typeof(value) == "string")
		value = value.replace(/\+/g,"%2B");
	(this.params == '') ? (this.params += key+'='+value) : (this.params += '&'+key+'='+value); 
}

XMLLoader.prototype.setURL = function(url/*string*/)/*void*/
{
	this.url = url;
}

XMLLoader.prototype.addCallBack = function(o/*object*/,m/*string*/,p/*object*/)/*void*/
{
	if(o)
	{
		this.handlerObject = o;
	}
	
	if(m)
	{
		this.handlerFunction = m;
	}
	
	if(p)
	{
		this.handlerParams = p;
	}	
}

XMLLoader.prototype.setMethod = function(m/*string GET || POST */)/*void*/
{
	m = m.toUpperCase();
	if(m != 'GET' && m != 'POST' )
	{
		alert('XMLLoader method must be "GET" or "POST" !')
	}
	else
	{
		this.method = m;
	}
}

XMLLoader.prototype.cancel = function()/*void*/
{
	if (this.xmlhttp != false)
	{
		this.xmlhttp.abort();
		this.xmlhttp = this.createXMLHTTP(); 
	}	
}

XMLLoader.prototype.load = function()/*void*/
{
	if (!this.xmlhttp) return false;
	
	this.isComplete = false;
	
	try 
	{
		if (this.method == "GET")
		{
			this.xmlhttp.open(this.method, this.url+"?"+this.params, true);
			this.params = "";
		}
		else
		{
			this.xmlhttp.open(this.method, this.url, true);
			this.xmlhttp.setRequestHeader("Method", "POST " + this.url + " HTTP/1.1");
			this.xmlhttp.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
		}
		
		var xmlLoader = this; 
		this.xmlhttp.onreadystatechange = function()
		{
			if (xmlLoader.xmlhttp.readyState == 4 && !xmlLoader.isComplete)
			{
				xmlLoader.isComplete = true;
				if(xmlLoader.handlerObject)
					xmlLoader.handlerObject[xmlLoader.handlerFunction](xmlLoader.xmlhttp,xmlLoader.handlerParams);
				else if(xmlLoader.handlerFunction)
					xmlLoader.handlerFunction(xmlLoader.xmlhttp,xmlLoader.handlerParams);
			}
		};
		
		this.xmlhttp.send(this.params);
	}	
	catch(z)
	{
		return false;
	}
	
	return true;
}
