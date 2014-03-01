<?php
//récupérer les informations de bases pour la générations d'affichage automatique
$dependencies_media = '<GET refresh="monthly"><DEPENDENCYENTITY from="media" /></GET>';
$categories_media = '<GET refresh="monthly"><CATEGORIES path="/media/livre"/><RETURN depth="all"/></GET>';
//
//
//informations à retourner pour les livres
$return_book = '
<RETURN depth="2">
	<INFO><MEDIATYPE/></INFO>
	<DESCRIPTIONS/>
	<DEPENDENCIES>
		<INFO/>
	</DEPENDENCIES>
	<CATEGORIES/>
</RETURN>';
//
//
//affichage du contenu pour un livre dans différents mode
$display_book = '
	<DISPLAY static="true" module="MEDIA" mediatype="Book" mode="website_preview" tag="ul" childtag="li">
		<DATA service="DESCRIPTION" path="TITLE"/>
		<GROUP class="PFBproperties" tag="ul" childtag="li">
			<DATA service="DESCRIPTION" path="YEAR"/>
			<DATA service="CATEGORY" path="concours"/>
		</GROUP>
		<DATA service="DEPENDENCY" type="BookToEditor"/>
		<DATA service="DEPENDENCY" type="BookToGraphist"/>
		<DATA service="DEPENDENCY" type="BookToPrinter"/>
		<DATA service="CATEGORY" path="book_theme"/>
		<LINK type="arrow"/>
	</DISPLAY>';

$display_book_technique_file = '
	<DISPLAY static="true" module="media" mediatype="Book" name="display_book_technique_file" tag="ul" childtag="li">
		<!--
		<DATA service="DESCRIPTION" label="book_NUMBER" path="NUMBER"/>
		<DATA service="DESCRIPTION" label="book_TITLE" path="TITLE"/>
		<DATA service="DESCRIPTION" label="book_HEADER" path="HEADER"/>
		<DATA service="DEPENDENCY" label="book_BookToGraphist" type="BookToGraphist"/>
		<DATA service="DEPENDENCY" label="book_BookToEditor" type="BookToEditor"/>
		<DATA service="DEPENDENCY" label="book_BookToPrinter" type="BookToPrinter"/>
		-->
		<DATA service="DEPENDENCY" label="book_BookToBinder" type="BookToBinder"/>
		<DATA service="DEPENDENCY" label="book_BookToAuthor" type="BookToAuthor"/>
		<DATA service="DEPENDENCY" label="book_BookToTranslator" type="BookToTranslator"/>
		<DATA service="DESCRIPTION" label="book_BOOK_TRANSLATOR" path="BOOK_TRANSLATOR"/>
		<DATA service="DEPENDENCY" label="book_BookToPhotograph" type="BookToPhotograph"/>
		<DATA service="DEPENDENCY" label="book_BookToIllustrator" type="BookToIllustrator"/>
		<DATA service="DESCRIPTION" label="book_BOOK_ISBN" path="BOOK_ISBN"/>
		<DATA service="DESCRIPTION" label="book_BOOK_LEGAL_DEPOSIT" path="BOOK_LEGAL_DEPOSIT"/>
		<DATA service="DESCRIPTION" label="book_BOOK_COPIES" path="BOOK_COPIES"/>
		<DATA service="DESCRIPTION" label="book_BOOK_WEIGHT" path="BOOK_WEIGHT"/>
		<DATA service="LABEL" label="book_INSIDE" tag="h4"/>
		<GROUP tag="ul" childtag="li">
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
		<DATA service="LABEL" label="book_COVER" tag="h4"/>
		<GROUP tag="ul" childtag="li">
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
		<!--
		<DATA service="DESCRIPTION" label="book_QUESTION_CONCEPT" path="QUESTION_CONCEPT"/>
		<DATA service="DESCRIPTION" label="book_QUESTION_APPRECIATE" path="QUESTION_APPRECIATE"/>
		<DATA service="DESCRIPTION" label="book_QUESTION_CONSTRAINT" path="QUESTION_CONSTRAINT"/>
		-->
	</DISPLAY>
';

?>