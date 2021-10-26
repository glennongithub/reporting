<?php
define('ACCESSCHECK', TRUE);

require 'vendor/autoload.php';


use Classes\ReportCardPdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

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

const SHEET_NAME_TO_MONTH = [
    'SEPTEMBER 20' => '2020-09',
    'OKTOBER 20' => '2020-10',
    'NOVEMBER 20' => '2020-11',
    'December -20' => '2020-12',
    'Januari -21' => '2021-01',
    'Februari -21' => '2021-02',
    'Mars -21' => '2021-03',
    'April -21' => '2021-04',
    'Maj -21' => '2021-05',
    'Juni -21' => '2021-06',
    'Juli -21' => '2021-07',
    'Augusti -21' => '2021-08',
    'September-21' => '2021-09',
];

// Manually creating a array of publishers that exists in the contact list .. but not on all reportsSheets
// due to no longer in our cong. (No need to keep registercards)
$namesToIgnore = [
    'Jeanette - Turesson',
    'Stefan - Littauer',
    'Brian - Sprogö',
    'Josefina - Sprogö',
    'Melanie - Sprogö',
    'Shari - Haim',
    'Bengt - Nilsson',
    'Maja-Leena - Nilsson',
    'Ulf - Nilsson',
    'Bertil - Pettersson'
];

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load("xls/Manadsrapporter-2020-2022.xlsx");
$contactListSheetData = $spreadsheet->getSheetByName('Kontaktlistan')->toArray();
// $contactListSheetData[2] is the column header so lets save it as a new var with clear purpouse.
$headerIndexMap = $contactListSheetData[0];

// Remove bottom rows that does not contin any intesresting data
array_splice($contactListSheetData, 105);

// Remove header row
unset($contactListSheetData[0]);
/*
echo("<pre>");
print_r($contactListSheetData);
echo("</pre>");
*/

// print_r($spreadsheet->getSheetNames());
unset($sheetNames[0]); // Konaktlistan
// Add a extra key where we can tack on publisher we find in reportSheets but we do not have in Kontaktlistam
$contactListSheetData['missing'] = [];

// Now load data from all shhets with monthly report-data
$reportSheets = [];
foreach ($spreadsheet->getSheetNames() as $sheetName) {
    if (!in_array($sheetName, ['Kontaktlistan', 'Forkunnare', 'Oktober-21'])) {
        $reportSheets[$sheetName] = $spreadsheet->getSheetByName($sheetName)->toArray();
    }
     
}

// Create a new array with only the data we are interested in
$fullReportArray = [];
$publisherNames = [];
// Now try to attach each monthly report to the correct publisher
$i=1;
//
// For every publisher in contactList
//
foreach ($contactListSheetData as $index => $row) {
    // if we have both first and last name we also try to find it in next sheet.
    //echo ($index . " : " .$row[FIRSTNAME]." - ". $row[LASTNAME]. "- </br>");

    // We need to skipp past some rows that are just mid-sheet header info
    $notLastNames = ['Efternamn', 'FÖRKUNNARE  TL', 'HJÄPPIONJÄRAR', 'TL', 'PIONJÄRAR'];
    if ($row[FIRSTNAME] && $row[LASTNAME] && !(in_array(trim($row[LASTNAME]), $notLastNames))) {
        $publisherName = $row[LASTNAME].", ". $row[FIRSTNAME];
        $publisherNames[$index] = $publisherName;
        // only keep interesting columns
        array_splice($row, 21);
        // Save the publisher in fullReport
        $fullReportArray[$index] = $row;
        // Make sure we set $contactListSheetData[$index][MONTHLY_REPORTS] to an array (overriting prev value)
        $fullReportArray[$index][MONTHLY_REPORTS] = [];

        // Now push each monthts sheet data onto it
        foreach ($reportSheets as $reportsIndex => $reportSheet) {
            // Before removing headers from data save it in other var
            $reportHeaders = $reportSheet[0];
            unset($reportSheet[0]);
            // Now scan for matching name row
            // keep track of if we found a match
            $publisherFound = false;
            foreach($reportSheet as $reportRow) {
                if (trim($reportRow[FIRSTNAME]) === trim($row[FIRSTNAME]) && 
                    trim($reportRow[LASTNAME]) === trim($row[LASTNAME])) {
                    // Splice up to key 17
                    array_splice($reportRow, 18);
                    $reportRow['month'] = SHEET_NAME_TO_MONTH[$reportsIndex];
                    echo($reportsIndex . " " .$reportRow['month'].  "<br>");
                    $fullReportArray[$index][MONTHLY_REPORTS][] = $reportRow;
                    // When we find a match we can stop searching
                    $publisherFound = true;
                    break;
                }
            }

            // If we have a publisher in contactList that we cannot find in a monthlyReportSheet
            // and it is not set to ignore. 
            if (!$publisherFound && !in_array($row[FIRSTNAME]." - ". $row[LASTNAME], $namesToIgnore)) {
                $fullReportArray['missing'][$row[FIRSTNAME]." - ". $row[LASTNAME]][] = "Coould not find " .$index . " : " .$row[FIRSTNAME]." - ". $row[LASTNAME] . " in shhet ". $reportsIndex;

            }
        }


    }
    
	$i++;
}

foreach($publisherNames as $key => $name) {
    $getCardLink =  '';
    $link = '<a href="scan_xls.php?printCardFor='.$name.'">Get Card</a>';

    // If we clicked to get card on this one .. generate it
    if ($_GET['printCardFor'] == $name) {
        $reportCard = new ReportCardPdf;
        $reportCard->generateNewCardData($fullReportArray[$key]);
        $reportCard->setFirstServiceYear('2020/21');

        foreach ($fullReportArray[$key][MONTHLY_REPORTS] as $reportRow) {
            echo("test- " .$reportRow['month'] . '-01' . "-test");
            $reportCard->addReportRow(\DateTime::createFromFormat('Y-n-d', $reportRow['month'] . '-01'), null);   
        }

        $reportCard->addReportRow(\DateTime::createFromFormat('Y-n-d', '2022-08-01'), null);

        // Use publishername as filename
        $filename = $reportCard->generatePdfFile($name)->getFilename();
    
        $getCardLink = '<a href="./output/' . $filename . '" download>Download it here</a>';
        
    }

    echo $key+1 . ": \t" . $name. ' '. $link . ' '. $getCardLink. '</br>';

}




echo("<pre>");
print_r($fullReportArray);
echo("</pre>");


?>

