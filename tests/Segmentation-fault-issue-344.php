<?php
  
include('vendor/autoload.php');

$mails = array("issue-344.eml", "issue-344.eml");

foreach($mails as $email){

    echo "New email $email ----------------\n";

    # Create Email decode object
    $parser = new PhpMimeMailParser\Parser();
    echo "- PhpMimeMailParser object created.\n";

    # Add email in to email object
    $parser->setText(file_get_contents($email));
    echo "- Set email body to object.\n";

    unset($parser);
}

?>
