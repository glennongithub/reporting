<?php 
/*
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
     exit;
}
*/

define('ACCESSCHECK', TRUE);

require_once 'vendor/autoload.php';

use Classes\GeneratePDF;

const LASTNAME = 1;
const FIRSTNAME = 2;
const IS_BAPTISED = 3;
const IS_UNBAPTISED_PUBLISHER = 4;
const IS_INACTIVE = 5;
const IS_PIONEER = 6;
const IS_AUX_PIONEER = 7;
const CURRENT_GO = 8;
const IS_ELDER = 9;
const IS_SERVANT = 10;
const SEX = 11;
const BIRTHDATE = 12;
const BAPTIMSDATE = 13;
const EMAIL = 14;
const MOBILE = 15;
const HOMEPHONE = 16;
const STREET = 17;
const CITY = 18;
const POSTCODE = 19;
const DIV = 20;
const MONTHLY_REPORTS = 'monthy_reports';

/*
$data = [

      'name_field' => $_POST['fname'] .' ' . $_POST['lname'],
      'email_field' => $_POST['email'],
      'phone_field' => $_POST['phone'],
      'enquiry_field' => $_POST['enquiry']
];
*/




/**
 * Takes a array in the format we have for a publisher in kontaktlistan and 
 * returns the dataArray nneded to fill reportCard with coresponding information 
 * 
 */
function generateNewCardData($publisherArray) {
      // We need and should always have first last name
      // Maybe even some more
      $data = [
            
            'Service Year' => '123456',
            'RemarksJanuary' => 'Some test'
      ];
      
      foreach() {
            'Check Box1' => $publisherArray[SEX] == 'M' ? 'Yes' : 'No',
      }
      
      
}

$pdf = new GeneratePdf;
$response = $pdf->generate($data);


header('Location: thanks.php?fname=' . $_POST['fname'] . '&link=' . $response);