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

// Column-nr for each datafiled needed in a monthly report-row
const PLACE_COL_NR = 9;
const VIDEO_COL_NR = 10;
const HOURS_COL_NR = 11;
const RV_COL_NR = 12;
const STUDIES_COL_NR = 13;
const AUX_PIONEER_THIS_MONTH_COL_NR = 15;
const REMARKS_COL_NR = 16;

// ServiceYear start in Sept so that is why this looks strange
const MONTH_NAMES_FOR_REMARKS = [
    1 => 'September',
    2 => 'Oktober',
    3 => 'November',
    4 => 'December',
    5 => 'January',
    6 => 'February',
    7 => 'March',
    8 => 'April',
    9 => 'May',
    10 => 'June',
    11 => 'July',
    12 => 'August',
];


const SHEET_NAME_TO_MONTH = [
    'September-21' => '2021-09',
    'Oktober-21' => '2021-10',
    'November-21' => '2021-11',
    'December-21' => '2021-12',
    'Januari-22' => '2022-01',
    'Februari-22' => '2022-02',
    'Mars-22' => '2022-03',
    'April-22' => '2022-04',
    'Maj-22' => '2022-05',
    'Juni-22' => '2022-06',
    'Juli-22' => '2022-07',
    'Augusti-22' => '2022-08',
    'September-22' => '2022-09',
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
    'Bertil - Pettersson',
    'Anna-Greta - Pettersson',
    'Bengt - Löthman',
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

// Keep track of publisher we find in reportSheets but we do not have in Kontaktlistam
$missing = [];

// Now load data from all shhets with monthly report-data
$reportSheets = [];
foreach ($spreadsheet->getSheetNames() as $sheetName) {
    if (!in_array($sheetName, ['Kontaktlistan', 'Forkunnare'])) {
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
                    // echo($reportsIndex . " " .$reportRow['month'].  "<br>");
                    $fullReportArray[$index][MONTHLY_REPORTS][] = $reportRow;
                    // When we find a match we can stop searching
                    $publisherFound = true;
                    break;
                }
            }

            // If we have a publisher in contactList that we cannot find in a monthlyReportSheet
            // and it is not set to ignore. 
            if (!$publisherFound && !in_array($row[FIRSTNAME]." - ". $row[LASTNAME], $namesToIgnore)) {
                $missing[] = "Coould not find " .$index . " : " .$row[FIRSTNAME]." - ". $row[LASTNAME] . " in shhet ". $reportsIndex;

            }
        }


    }
    
	$i++;
}

foreach($publisherNames as $key => $name) {
    $getCardLink =  '';
    $link = '<a href="scan_xls.php?printCardFor='.$name.'">Get Card</a>';

    // If we clicked to get card on this one .. generate it
    if ($_GET['printCardFor'] == $name || $_GET['printCardFor'] == 'all') {
        $reportCard = new ReportCardPdf;
        $reportCard->generateNewCardData($fullReportArray[$key]);
        $reportCard->setFirstServiceYear('2021/22');

        foreach ($fullReportArray[$key][MONTHLY_REPORTS] as $reportRow) {
            $reportCard->addReportRow($reportRow);   
        }

        $reportCard->generateTotalsAndAverage($fullReportArray[$key][MONTHLY_REPORTS] );

        // FIXME .. we could potentially use $fullReportArray[$key][CURRENT_GO] as part of file-name to easily separate groups.
        // Use publishername as filename
        $filename = $reportCard->generatePdfFile($name)->getFilename();
    
        if ($_GET['printCardFor'] != 'all')
        {
            // for now .. just dont print it .. 
            // FIXME make this a download for all files as a zip-file
            $getCardLink = '<a href="./output/' . $filename . '" download>Download it here</a>';
        }
        
        echo("<pre>");
        // print_r($fullReportArray[$key]);
        echo("</pre>");
        
    }

    echo $key+1 . ": \t" . $name. ' '. $link . ' '. $getCardLink. '</br>';
}




echo("<pre>");
//print_r($fullReportArray);
print_r($missing);
echo("</pre>");


?>

