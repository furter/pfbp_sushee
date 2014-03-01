<?php
include_once("../Kernel/common/common_functions.inc.php");

// the form was filled, we process it
if(sizeof($_POST)>0 && $_POST["lastname"]!='' && $_POST["firstname"]!='' && $_POST["email"]!=''){
	
	// trying to create the contact of the person with the information provided by the visitor
	// we add the contact in the group with ID 132. This group will be the group used in the Mailing application
	$result = query("<QUERY>
	<CREATE>
		<CONTACT>
			<INFO>
				<FIRSTNAME>".encode_to_XML($_POST["firstname"])."</FIRSTNAME>
				<LASTNAME>".encode_to_XML($_POST["lastname"])."</LASTNAME>
				<EMAIL1>".encode_to_XML($_POST["email"])."</EMAIL1>
				<CONTACTTYPE>PP</CONTACTTYPE>
				<COUNTRYID>".$_POST["countryid"]."</COUNTRYID>
				<LANGUAGEID>".$GLOBALS['NectilLanguage']."</LANGUAGEID>
				<POSTALCODE>".encode_to_XML($_POST["postalcode"])."</POSTALCODE>
				<CITY>".encode_to_XML($_POST["city"])."</CITY>
				<ADDRESS>".encode_to_XML($_POST["address"])."</ADDRESS>
			</INFO>
			<DEPENDENCIES>
				<DEPENDENCY type='groupMember' mode='reverse'><GROUP ID='132'/></DEPENDENCY>
			</DEPENDENCIES>
		</CONTACT>
	</CREATE>
	</QUERY>");
	
	// verifying it was well created : it could have failed because a contact with the same email adress already existed
	$create_contact_xml = new XML($result);
	$res_bool = $create_contact_xml->getData("/RESPONSE/MESSAGE/@msgType");
	$contactID = $create_contact_xml->getData("/RESPONSE/MESSAGE/@elementID");
	// if an error occurred, res_bool is 1. If it's ok, it's 0.
	if($res_bool=="1"){
		// in the case of duplicate email adress, we update the contact with the new information
		$update_contact = "<QUERY>
			<UPDATE>
				<CONTACT ID='$contactID'>
					<INFO>
						<FIRSTNAME>".encode_to_XML($_POST["firstname"])."</FIRSTNAME>
						<LASTNAME>".encode_to_XML($_POST["lastname"])."</LASTNAME>
						<COUNTRYID>".$_POST["countryid"]."</COUNTRYID>
						<LANGUAGEID>".$GLOBALS['NectilLanguage']."</LANGUAGEID>
						<POSTALCODE>".encode_to_XML($_POST["postalcode"])."</POSTALCODE>
						<CITY>".encode_to_XML($_POST["city"])."</CITY>
						<ADDRESS>".encode_to_XML($_POST["address"])."</ADDRESS>
					</INFO>
					<DEPENDENCIES><DEPENDENCY type='groupMember' mode='reverse'><GROUP ID='132'/></DEPENDENCY></DEPENDENCIES>
				</CONTACT>
			</UPDATE>
			</QUERY>";
		$result2 = query($update_contact,false);
	}
	echo transform($result,"inscription_ok.xsl");
}else{
	// displaying the form
	if(sizeof($_POST)>0)
		$_GET=$_POST;
	$result = query("<QUERY><GET name='countries'><COUNTRIES profile='SmallList'/></GET></QUERY>");
	echo transform($result,"inscription1.xsl");
}
?>
