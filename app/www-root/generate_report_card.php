<?php 
// This file is not importatn .. and basically removable .now .. we used it for testing initially
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
      // Some initial data is always expected
      $formData = [
            'Name' => $publisherArray[LASTNAME]. ', '. $publisherArray[FIRSTNAME],
            'Service Year' => '2021/22',
            // Male or femail
            'Check Box1' => trim($publisherArray[SEX]) == 'M' ? 'Yes' : 'No',
            'Check Box2' => trim($publisherArray[SEX]) == 'K' ? 'Yes' : 'No',
            // Anointed or other cheep
            'Check Box3' =>'Yes',
            // 'Check Box4' => 'No' // We have no filed for this right now
            'Check Box5' => trim($publisherArray[IS_ELDER]) == '1' ? 'Yes' : 'No',
            'Check Box6' => trim($publisherArray[IS_SERVANT]) == '1' ? 'Yes' : 'No',
            'Check Box7' => trim($publisherArray[IS_PIONEER]) == '1' ? 'Yes' : 'No',
            'Date of birth' => trim($publisherArray[BIRTHDATE]),
      ];

      // Try to handle stupid vatying format of dates
      $birthDate = new \DateTime(trim($publisherArray[BIRTHDATE]));

      // Batism date we do not touch if we do not have one
      if (trim($publisherArray[BAPTIMSDATE])) {
        $formData['Date immersed'] = trim($publisherArray[BAPTIMSDATE])
      } 
}

$pdf = new GeneratePdf;
$response = $pdf->generate($data);


header('Location: thanks.php?fname=' . $_POST['fname'] . '&link=' . $response);