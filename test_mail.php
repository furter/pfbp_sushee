<?php
$to = 'loraine.furter@gmail.com';
$subject = 'Testing php’s mail function';
$message = 'Hello, with this mail I’m trying out PHP’s mail function. The script is in the fernand folder, as test_mail.php , and you can run it again by visiting http://www.prixfernandbaudinprijs.be/test_mail.php For more info: http://docs.webfaction.com/software/php.html#sending-mail-from-a-php-script';
$headers = "From: eric@ericschrijver.nl\r\n"; // Or sendmail_username@hostname by default
mail($to, $subject, $message, $headers);
?>

<p>I have tried to send a mail</p>
