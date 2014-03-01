<?php
$countries = '<GET refresh="monthly"><COUNTRIES/></GET>';	
$labels = '<GET refresh="monthly"><LABELS/></GET>';

/*****/
/* CONTACTS */
/*****/

$display_contact = '
	<DISPLAY static="true" module="CONTACT">
		<UPDATE />
		<DATA service="INFO" path="LASTNAME"/>
		<DATA service="INFO" path="FIRSTNAME"/>
		<DATA service="INFO" path="DENOMINATION"/>
		<DATA service="CATEGORY" path="professionel"/>
		<GROUP class="contact">
			<DATA service="INFO" path="EMAIL1"/>			
			<DATA service="INFO" path="LANGUAGEID"/>			
			<DATA service="INFO" path="MOBILEPHONE"/>
			<DATA service="INFO" path="PHONE1"/>
			<DATA service="INFO" path="ADDRESS"/>
			<DATA service="INFO" path="POSTALCODE"/>
			<DATA service="INFO" path="CITY"/>
			<DATA service="INFO" path="COUNTRYID"/>
		</GROUP>
		<GROUP class="books">
			<DATA service="DEPENDENCY" type="EditorToBook"/>
			<DATA service="DEPENDENCY" type="GraphistToBook"/>
			<DATA service="DEPENDENCY" type="PhotographToBook"/>
			<DATA service="DEPENDENCY" type="IllustratorToBook"/>
			<DATA service="DEPENDENCY" type="AuthorToBook"/>
			<DATA service="DEPENDENCY" type="BinderToBook"/>
			<DATA service="DEPENDENCY" type="PrinterToBook"/>
		</GROUP>
	</DISPLAY>';
	
$return_contact = '
	<RETURN>
		<INFO/>
		<DEPENDENCIES>
			<DEPENDENCY type="EditorToBook">
				<INFO><MEDIATYPE/></INFO>
				<DESCRIPTION><TITLE/></DESCRIPTION>
			</DEPENDENCY>
			<DEPENDENCY type="GraphistToBook">
				<INFO><MEDIATYPE/></INFO>
				<DESCRIPTION><TITLE/></DESCRIPTION>
			</DEPENDENCY>
			<DEPENDENCY type="PhotographToBook">
				<INFO><MEDIATYPE/></INFO>
				<DESCRIPTION><TITLE/></DESCRIPTION>
			</DEPENDENCY>
			<DEPENDENCY type="IllustratorToBook">
				<INFO><MEDIATYPE/></INFO>
				<DESCRIPTION><TITLE/></DESCRIPTION>
			</DEPENDENCY>
			<DEPENDENCY type="AuthorToBook">
				<INFO><MEDIATYPE/></INFO>
				<DESCRIPTION><TITLE/></DESCRIPTION>
			</DEPENDENCY>
			<DEPENDENCY type="BinderToBook">
				<INFO><MEDIATYPE/></INFO>
				<DESCRIPTION><TITLE/></DESCRIPTION>
			</DEPENDENCY>
			<DEPENDENCY type="PrinterToBook">
				<INFO><MEDIATYPE/></INFO>
				<DESCRIPTION><TITLE/></DESCRIPTION>
			</DEPENDENCY>
			<DEPENDENCY type="PeopleContactToBook">
				<INFO><MEDIATYPE/></INFO>
				<DESCRIPTION><TITLE/></DESCRIPTION>
			</DEPENDENCY>
		</DEPENDENCIES>
		<CATEGORIES/>
	</RETURN>';

$dependencies_contact = '<GET refresh="monthly"><DEPENDENCYENTITY from="contact" /></GET>';
$categories_contact = '<GET refresh="monthly"><CATEGORIES path="/contact/contact_details"/><RETURN depth="all"/></GET>';



$dependencies_media = '<GET refresh="monthly"><DEPENDENCYENTITY from="media" /></GET>';
$categories_media = '<GET refresh="monthly"><CATEGORIES name="prize_years"/><RETURN depth="all"/></GET><GET refresh="monthly"><CATEGORIES path="/media/livre"/><RETURN depth="all"/></GET>';

$display_book = '
	<DISPLAY static="true" module="MEDIA" mediatype="Book">
		<UPDATE />
		<DATA service="DESCRIPTION" path="TITLE"/>
		<!--DATA service="DESCRIPTION" path="TITLE" function="length" /-->
		<GROUP class="PFBproperties">
			<DATA service="INFO" path="ID"/>
			<DATA service="INFO" path="CREATIONDATE"/>
			<DATA service="DESCRIPTION" path="YEAR"/>
			<DATA service="CATEGORY" path="prize_years"/>
			<DATA service="DESCRIPTION" path="NUMBER" data-type="number"/>
			<DATA service="DESCRIPTION" path="NUMBER2" data-type="number"/>
			<DATA service="CATEGORY" path="book_theme"/>
			<DATA service="CATEGORY" path="concours"/>
			<DATA service="DESCRIPTION" path="REFUS"/>
			<DATA service="DESCRIPTION" path="BIBLIO"/>
		</GROUP>
		<!--GROUP class="BOOKproperties"/>
		<GROUPE class="inside"/>
		<GROUPE class="cover"/>
		<GROUPE class="questions"/-->
		<GROUP class="people">
			<DATA service="DEPENDENCY" type="BookToEditor"/>
			<DATA service="DEPENDENCY" type="BookToGraphist"/>
			<DATA service="DEPENDENCY" type="BookToPhotograph"/>
			<DATA service="DEPENDENCY" type="BookToIllustrator"/>
			<DATA service="DEPENDENCY" type="BookToAuthor"/>
			<DATA service="DEPENDENCY" type="BookToBinder"/>
			<DATA service="DEPENDENCY" type="BookToPrinter"/>
			<DATA service="DEPENDENCY" type="BookToPeopleContact"/>
			<DATA service="DEPENDENCY" function="PeopleContactType"/>
		</GROUP>
	</DISPLAY>';

$return_book = '
<RETURN>
	<INFO><MEDIATYPE/><CREATIONDATE/><ID/></INFO>
	<DESCRIPTIONS/>
	<DEPENDENCIES>
		<DEPENDENCY type="BookToEditor">
			<INFO><CONTACTTYPE/><EMAIL1/><FIRSTNAME/><LASTNAME/><DENOMINATION/><PHONE1/><MOBILEPHONE/><ADDRESS/><COUNTRYID/><POSTALCODE/><CITY/></INFO>
		</DEPENDENCY>
		<DEPENDENCY type="BookToGraphist">
			<INFO><CONTACTTYPE/><EMAIL1/><FIRSTNAME/><LASTNAME/><DENOMINATION/><PHONE1/><MOBILEPHONE/><ADDRESS/><COUNTRYID/><POSTALCODE/><CITY/></INFO>
		</DEPENDENCY>
		<DEPENDENCY type="BookToPrinter">
			<INFO><CONTACTTYPE/><EMAIL1/><FIRSTNAME/><LASTNAME/><DENOMINATION/><PHONE1/><MOBILEPHONE/><ADDRESS/><COUNTRYID/><POSTALCODE/><CITY/></INFO>
		</DEPENDENCY>
		<DEPENDENCY type="BookToPeopleContact">
			<INFO><CONTACTTYPE/><EMAIL1/><FIRSTNAME/><LASTNAME/><DENOMINATION/><PHONE1/><MOBILEPHONE/><ADDRESS/><COUNTRYID/><POSTALCODE/><CITY/></INFO>
		</DEPENDENCY>
	</DEPENDENCIES>
	<CATEGORIES/>
</RETURN>';

$display_book_export = '
	<DISPLAY static="true" module="media" mediatype="Book" name="display_book_export">
		<DATA service="DESCRIPTION" label="book_NUMBER" path="NUMBER"/>
		<DATA service="DESCRIPTION" label="book_TITLE" path="TITLE"/>
		<DATA service="DESCRIPTION" label="book_HEADER" path="HEADER"/>
		<DATA service="DEPENDENCY" label="book_BookToGraphist" type="BookToGraphist"/>
		<DATA service="DEPENDENCY" label="book_BookToEditor" type="BookToEditor"/>
		<DATA service="DEPENDENCY" label="book_BookToPrinter" type="BookToPrinter"/>
		<DATA service="DEPENDENCY" label="book_BookToBinder" type="BookToBinder"/>
		<DATA service="DEPENDENCY" label="book_BookToAuthor" type="BookToAuthor"/>
		<DATA service="DESCRIPTION" label="book_BOOK_TRANSLATOR" path="BOOK_TRANSLATOR"/>
		<DATA service="DEPENDENCY" label="book_BookToPhotograph" type="BookToPhotograph"/>
		<DATA service="DEPENDENCY" label="book_BookToIllustrator" type="BookToIllustrator"/>
		<DATA service="DESCRIPTION" label="book_BOOK_ISBN" path="BOOK_ISBN"/>
		<DATA service="DESCRIPTION" label="book_BOOK_LEGAL_DEPOSIT" path="BOOK_LEGAL_DEPOSIT"/>
		<DATA service="DESCRIPTION" label="book_BOOK_COPIES" path="BOOK_COPIES"/>
		<DATA service="DESCRIPTION" label="book_BOOK_WEIGHT" path="BOOK_WEIGHT"/>
		<DATA service="LABEL" label="book_INSIDE"/>
		<GROUP>
			<DATA service="DESCRIPTION" label="book_INSIDE_WIDTH" path="INSIDE_WIDTH"/>
			<DATA service="LABEL" label="dimensions_by"/>
			<DATA service="DESCRIPTION" label="book_INSIDE_HEIGHT" path="INSIDE_HEIGHT"/>
			<DATA service="LABEL" label="millimetres"/>
		</GROUP>
		<DATA service="DESCRIPTION" label="book_INSIDE_PAGES" path="INSIDE_PAGES"/>
		<DATA service="DESCRIPTION" label="book_INSIDE_BOOKLETS" path="INSIDE_BOOKLETS"/>
		<DATA service="DESCRIPTION" label="book_INSIDE_PAPER" path="INSIDE_PAPER"/>
		<DATA service="DESCRIPTION" label="book_INSIDE_PRINT" path="INSIDE_PRINT"/>
		<DATA service="DESCRIPTION" label="book_INSIDE_BINDING" path="INSIDE_BINDING"/>
		<DATA service="DESCRIPTION" label="book_INSIDE_FONTS" path="INSIDE_FONTS"/>
		<DATA service="DESCRIPTION" label="book_INSIDE_REMARK" path="INSIDE_REMARK"/>
		<DATA service="LABEL" label="book_COVER"/>
		<GROUP>
			<DATA service="DESCRIPTION" label="book_COVER_WIDTH" path="COVER_WIDTH"/>
			<DATA service="LABEL" label="dimensions_by"/>
			<DATA service="DESCRIPTION" label="book_COVER_HEIGHT" path="COVER_HEIGHT"/>
			<DATA service="LABEL" label="millimetres"/>
		</GROUP>
		<DATA service="DESCRIPTION" label="book_COVER_PAGES" path="COVER_PAGES"/>
		<DATA service="DESCRIPTION" label="book_COVER_PAPER" path="COVER_PAPER"/>
		<DATA service="DESCRIPTION" label="book_COVER_PRINT" path="COVER_PRINT"/>
		<DATA service="DESCRIPTION" label="book_COVER_FINISHING" path="COVER_FINISHING"/>
		<DATA service="DESCRIPTION" label="book_COVER_REMARK" path="COVER_REMARK"/>
		<DATA service="DESCRIPTION" label="book_QUESTION_CONCEPT" path="QUESTION_CONCEPT"/>
		<DATA service="DESCRIPTION" label="book_QUESTION_APPRECIATE" path="QUESTION_APPRECIATE"/>
		<DATA service="DESCRIPTION" label="book_QUESTION_CONSTRAINT" path="QUESTION_CONSTRAINT"/>
	</DISPLAY>
';


?>