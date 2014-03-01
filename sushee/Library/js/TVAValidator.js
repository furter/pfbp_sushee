function getISO2(countryID)
{
	if(countryID == '')
		return '';
		
	var prefixes = 
	{
		aut:'ATU',
		ger:'DE',
		deu:'DE',
		bel:'BE',
		bgr:'BG',
		cyp:'CY',
		dnk:'DK',
		spa:'ES',
		esp:'ES',
		fin:'FI',
		est:'EE',
		fra:'FR',
		grc:'EL',
		gre:'EL',
		hun:'HU',				
		irl:'IE',
		ita:'IT',
		lva:'LV',
		ltu:'LT',
		lux:'LU',
		mlt:'MT',
		nld:'NL',
		pol:'PL',
		prt:'PT',
		gbr:'GB',
		rou:'RO',
		svk:'SK',
		svn:'SI',
		swe:'SE',
		cze:'CZ'
	}
	
	if(!isset(prefixes[countryID]))
		return '';
	else
		return prefixes[countryID];
}

/*					
function clearString(string,array)
{
	for (var i = 0; i < array.length ; i++)
	{
		var splitted = string.split(array[i]);	
		string = splitted.join('');	
	}
	return string;
}

function isNum(c)
{
	return (! isNaN(parseFloat(c)));
}

function isAlphaNum(c)
{
	var valid = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	
	if (valid.indexOf(c) == -1 || !isNum(c)) return false;
	else return true;
}

function isAlpha(c)
{
	var valid = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	if (valid.indexOf(c) == -1) return false;
	else return true;
}

function allNum(string)
{
	for(var i = 0 ; i < string.length ; i++)
	{
		if(!isNum(string.charAt(i)))
		{
			return false;
		}
	}
	return true;
}

function allAlpha(string)
{
	for(var i = 0 ; i < string.length ; i++)
	{
		if(!isAlpha(string.charAt(i)))
		{
			return false;
		}
	}
	return true;
}

function allExcept(string,validationFunction,exceptions)
{
	for(var i = 0 ; i < string.length ; i++)
	{
		if(typeof(exceptions[i]) != "function")
		{
			if(! validationFunction(string.charAt(i)))
			{
				return false;
			}
		}
		else
		{
			if(!exceptions[i](string.charAt(i)))
			{
				return false;
			}
		}
	}
	return true;
}
*/

function TVAValidator(countryID,msgError)
{
	logs("TVAValidator");
	logs("countryID : " + countryID);
	logs("msgError : " + msgError);	
	this.countryID = countryID;
	this.msgError = msgError;
}

TVAValidator.prototype.countryID;
TVAValidator.prototype.msgError;
TVAValidator.prototype.name = 'TVA Validator';
TVAValidator.prototype.web = "http://fiscus.fgov.be/INTERFAOIFFR/tva_intrac/tva_eur_fr.htm";

TVAValidator.prototype.isTVAValid = function(string,iso,le,validity)
{
	var test = true;
	
	if(string.length != le || string.substr(0,iso.length).toUpperCase() != iso)
	{
		test = false;
	}	
	else 
	{
		string = string.substr(iso.length);
		if(!allExcept(string,validity.f,(validity.e != undefined) ? validity.e : {} ))
		{
			test = false;
		}
	}
	
	return test;
}

TVAValidator.prototype.isValid = function(string)
{
	if(string != '' && this.countryID != '')
	{
		var test = true;
		
		string = clearString(string,[' ',',','-','*','_','/','\\']);
		
		switch(this.countryID)
		{
			case 'ger' : // ok
			case 'deu' : // ok
				// Allemagne :  DE + 9 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 NUMERIC
				test = this.isTVAValid(string,'DE',11,{f:isNum});
			break;
			
			case 'aut' : // ok
				// Autriche :   AT + U + 8 caract�res
				// C1 ALPHABETIC, C2-C9 NUMERIC
				test = this.isTVAValid(string,'ATU',11,{f:isNum});
			break;
			
			case 'bel' : // ok
				//Belgique : BE + 10 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 NUMERIC
				test = this.isTVAValid(string,'BE',12,{f:isNum});
			break;
			
			case 'bgr' : // ok
				// Bulgarie : BG + 9 ou 10 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 NUMERIC
				test = this.isTVAValid(string,'BG',11,{f:isNum});
				
				if(!test)
					test = this.isTVAValid(string,'BG',12,{f:isNum});
			break;
			
			case 'cyp' : // ok
				// Chypre  : CY + 8 caract�res num�riques + 1 caract�re alphab�tique
				// C1-C8 NUMERIC, C9 ALPHABETIC
				test = this.isTVAValid(string,'CY',11,{f:isNum,e:{8:isAlpha}});
			break;
			
			case 'dnk' : // ok
				// Danemark   :  DK + 8 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 NUMERIC
				test = this.isTVAValid(string,'DK',10,{f:isNum});
			break;
			
			case 'spa' : // ok
			case 'esp' : // ok
				// Espagne  :  ES + 9 caract�res
				// C1 AND C9 ALPHABETIC, C2-C8 NUMERIC
				test = this.isTVAValid(string,'ES',11,{f:isNum,e:{0:isAlpha,8:isAlpha}});
			break;
			
			case 'est' : // ok
				// Estonie :  EE + 9 caract�res num�riques
				//  C1 C2 C3 C4 C5 C6 C7 C8 C9 NUMERIC
				test = this.isTVAValid(string,'EE',11,{f:isNum});
			break;
			
			case 'fin' : // ok
				// Finlande : FI + 8 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 NUMERIC
				test = this.isTVAValid(string,'FI',10,{f:isNum});
			break;
			
			case 'fra' : // ok
				// France  : FR + 1 bloc de 2 caract�res + 1 bloc de 9 chiffres
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 C11 NUMERIC OR
				// C1 AND C2 ALPHABETIC, C3-C11 NUMERIC
				
				test = this.isTVAValid(string,'FR',13,{f:isNum});
				
				if(!test)
					test = this.isTVAValid(string,'FR',13,{f:isNum,e:{0:isAlpha,1:isAlpha}});
			break;
			
			case 'gre' :
			case 'grc' :
				// Gr�ce  : EL + 9 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 NUMERIC
				test = this.isTVAValid(string,'EL',11,{f:isNum});
			break;
			
			case 'hun' : 
				// Hongrie :  HU + 8 caract�res num�riques
				//  C1 C2 C3 C4 C5 C6 C7 C8 NUMERIC
				test = this.isTVAValid(string,'HU',10,{f:isNum});
			break;
			
			case 'irl' : 
				// Irlande :  IE + 8 caract�res num�riques et alphab�tiques
				// C1 C2 C3 C4 C5 C6 C7 C8
				// C1 AND C3-C7 NUMERIC C2 AND C8 ALPHABETIC OR
				// C1 C2 C3 C4 C5 C6 C7 C8
				// C1-C7 NUMERIC C8 ALPHABETIC
				
				test = this.isTVAValid(string,'IE',10,{f:isNum,e:{1:isAlpha,7:isAlpha}});
				
				if(!test)
					test = this.isTVAValid(string,'IE',10,{f:isNum,e:{7:isAlpha}});
					
			break;
			
			case 'ita' :
				// Italie :  IT + 11 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 C11 NUMERIC
				test = this.isTVAValid(string,'IT',13,{f:isNum});
			break;
			
			case 'lva' :
				// Lettonie :  LV + 11 caract�res num�riques
				// CASE 1 - LEGAL PERSONS: 11 DIGITS
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 C11 NUMERIC
				// CASE 2 - NATURAL PERSONS: 11 DIGITS
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 C11 NUMERIC 
				
				test = this.isTVAValid(string,'LV',13,{f:isNum});
			break;
			
			case 'ltu' :
				// Lituanie  :  LT + 9 ou 12 caract�res num�riques
				// CASE 1 - LEGAL PERSONS: 9 DIGITS
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 NUMERIC
				
				// CASE 2 - TEMPORARILY REGISTERED TAXPAYERS: 12 DIGITS
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 C11 C12 NUMERIC
				
				// CASE 3 - NATURAL PERSONS: NOW 12 DIGITS
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 C11 C12 NUMERIC
				
				// NOTE: 13 DIGITS VAT NOS ARE NOW NOT VALID 
				
				test = this.isTVAValid(string,'LT',14,{f:isNum});
				
				if(!test)
					test = this.isTVAValid(string,'LT',11,{f:isNum});
			break;
			
			case 'lux' : 
				// Luxembourg : LU + 8 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 NUMERIC
				
				test = this.isTVAValid(string,'LU',10,{f:isNum});
			break;
			
			case 'mlt' : 
				// Malte  :  MT + 8 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 NUMERIC
				
				test = this.isTVAValid(string,'MT',10,{f:isNum});
			break;
			
			case 'nld' : 
				// Pays-Bas  :  NL + 12 caract�res alphanum�riques dont une lettre
				// C1-C9 AND C11 C12 NUMERIC C10 ALPHABETIC
				
				test = this.isTVAValid(string,'NL',14,{f:isNum,e:{9:isAlpha}});
			break;
			
			case 'pol' : 
				// Pologne  :  PL + 10 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 NUMERIC
				
				test = this.isTVAValid(string,'NL',12,{f:isNum});
			break;
			case 'prt' : 
				// Portugal : PT + 9 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 NUMERIC
				
				test = this.isTVAValid(string,'NL',11,{f:isNum});
			 break;
			 
			 // ------------------------------
			 case 'gbr' : 
				// Royaume-Uni :  GB + 9 caract�res num�riques
				//C1 C2 C3 C4 C5 C6 C7 C8 C9 NUMERIC
				// GB + 5 caract�res num�riques et alphab�tiques
				// C1 C2 C3 C4 C5. C1 AND C2 ALPHABETIC, C3-C5 NUMERIC 
				
				test = this.isTVAValid(string,'GB',11,{f:isNum});

				if(!test)
					test = this.isTVAValid(string,'GB',7,{f:isNum,e:{0:isAlpha,1:isAlpha}});
			 break;
			 
			 case 'rou' : 
				//Roumanie :  RO + 9 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 NUMERIC
				
				test = this.isTVAValid(string,'RO',11,{f:isNum});
			 break;
			 
			 case 'svk' : 
				//Slovaquie :  SK + 10 caract�res num�riques
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 NUMERIC
				
				test = this.isTVAValid(string,'SK',11,{f:isNum});
			 break;
			 
			 case 'svn' : 
				//Slov�nie : SI + 8 caract�res num�riques
				//  C1 C2 C3 C4 C5 C6 C7 C8 NUMERIC
				
				test = this.isTVAValid(string,'SI',10,{f:isNum});
			 break;
			 
			 case 'swe' : 
				//Su�de  :  SE + 12 caract�res num�riques
				//  C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 C11 C12 NUMERIC
				
				test = this.isTVAValid(string,'SE',14,{f:isNum});
			 break;
			 
			 case 'cze' : 
				//Tch�quie  :  CZ + 8 ou 9 ou 10 caract�res num�riques
				// CASE 1 - LEGAL ENTITY: 8 DIGITS
				// C1 C2 C3 C4 C5 C6 C7 C8 NUMERIC
				// CASE 2 - INDIVIDUALS: 9 DIGITS
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 NUMERIC
				// CASE 3 - SPECIAL CASES: 9 DIGITS
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 NUMERIC
				// CASE 4 - INDIVIDUALS: 10 DIGITS
				// C1 C2 C3 C4 C5 C6 C7 C8 C9 C10 NUMERIC
				
				test = this.isTVAValid(string,'CZ',12,{f:isNum});
				if(! test)
					test = this.isTVAValid(string,'CZ',11,{f:isNum});
				if(! test)
					test = this.isTVAValid(string,'CZ',10,{f:isNum});					
			 break;
		}
		
		return test;
	}
	else
	{
		return false;
	}	
}

TVAValidator.prototype.getName = function()
{
	return this.name;
}

TVAValidator.prototype.toString = function()
{
	return this.getName();
}

TVAValidator.prototype.getMsgError = function()
{
	return this.msgError;
}
