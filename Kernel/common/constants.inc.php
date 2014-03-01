<?php
/*
Officity - Web application platform - Version 6.0b - 2012-09-25

François Dispaux, Boris Verdeyen, Marc Mignonsin,
Jonathan Sanchez, Julien Gonzalez, Jérémie Roy, Thomas Hermant,
Grégory Meurice, Pierre Fouchez, Thomas Brunel.

Officity, Sushee, and Kaiten are © Copyright 2012 Nectil SA.

`/sushee/common/constants.inc.php` is part of Officity.

Officity, Sushee, and Kaiten are proprietary software under development.
This copy is part of our beta test program and can only be used for this purpose.
You CANNOT redistribute it and/or modify it in any way.

Officity, Sushee, and Kaiten are distributed WITHOUT ANY WARRANTY without even 
the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

define('SUSHEE_XML_HEADER','<?xml version="1.0" encoding="utf-8"?>');

// *** MSGType ***
// 0 = no error
// 1 = operation failed
// 2 = operation couldn't be achieved because of security restriction
define('SUSHEE_MSGTYPE_SUCCESS',0);
define('SUSHEE_MSGTYPE_ERROR',1);
define('SUSHEE_MSGTYPE_SECURITYERROR',2);

// *** Error codes ***
// 1 = Element was modified since you loaded it. Reload it.
// 2 = Email ... already used.
// 3 = ClientCode ... already used (obsolete)
// 4 = update/delete on an empty element set (UPDATE WHERE)
// 5 = remove failed, because element does not exist
// 6 = processor exception
// 7 = element already exists
define('SUSHEE_ERROR_ELTMODIFIED',1);
define('SUSHEE_ERROR_EMAILDUPLICATE',2);
define('SUSHEE_ERROR_CLIENTCODEDUPLICATE',3);
define('SUSHEE_ERROR_EMPTYELEMENTSET',4);
define('SUSHEE_ERROR_ELTNOTFOUND',5);
define('SUSHEE_ERROR_PROCESSOREXCEPTION',6);
define('SUSHEE_ERROR_ELTEXISTS',7);


// *** Activity ***
// 1 = active
// 0 = deleted
// 2 = waiting for approval
// 3 = system
define('SUSHEE_ACTIVITY_ACTIVE',1);
define('SUSHEE_ACTIVITY_DELETED',0);
define('SUSHEE_ACTIVITY_WAITING',2);
define('SUSHEE_ACTIVITY_SYSTEM',3);


?>