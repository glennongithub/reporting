<?php 
/*
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
     exit;
}
*/

define('ACCESSCHECK', TRUE);

require_once 'vendor/autoload.php';

use Classes\GeneratePDF;

/*
$data = [

      'name_field' => $_POST['fname'] .' ' . $_POST['lname'],
      'email_field' => $_POST['email'],
      'phone_field' => $_POST['phone'],
      'enquiry_field' => $_POST['enquiry']
];
*/

$data = [
      'Check Box2' => 'Yes',
      'Service Year' => '123456',
      'RemarksJanuary' => 'Some test'
];

$pdf = new GeneratePdf;
$response = $pdf->generate($data);


header('Location: thanks.php?fname=' . $_POST['fname'] . '&link=' . $response);