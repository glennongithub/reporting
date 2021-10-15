<?php

require 'vendor/autoload.php';

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
const MONTHLY_REPORTS = 21;

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
];

$reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
$spreadsheet = $reader->load("xls/Manadsrapporter-2020-2021.xlsx");
$contactListSheetData = $spreadsheet->getSheetByName('Kontaktlistan')->toArray();
// $contactListSheetData[2] is the column header so lets save it as a new var with clear purpouse.
$headerIndexMap = $contactListSheetData[2];

// Remove bottom rows that does not contin any intesresting data
array_splice($contactListSheetData, 88);

// Remove first 2 rows. Some info about sircuitOverseer
unset($contactListSheetData[0]);
unset($contactListSheetData[1]);
// Also the headr row
unset($contactListSheetData[2]);
// print_r($spreadsheet->getSheetNames());
unset($sheetNames[0]); // Konaktlistan
unset($sheetNames[13]); // Some last unused sheet
// Add a extra key where we can tack on publisher we find in reportSheets but we do not have in Kontaktlistam
$contactListSheetData['missing'] = [];

// Now load data from all shhets with monthly report-data
$reportSheets = [];
$reportSheetsNames = [];
foreach ($spreadsheet->getSheetNames() as $sheetName) {
    if (!in_array($sheetName, ['Kontaktlistan', 'Blad1'])) {
        $reportSheets[] = $spreadsheet->getSheetByName($sheetName)->toArray();
        $reportSheetsNames[] = $sheetName;
    }
     
}

// Now try to attach each monthly report to the correct publisher
$i=1;
foreach ($contactListSheetData as $index => $row) {
    // if we have both first and last name we also try to find it in next sheet.
    //echo ($index . " : " .$row[FIRSTNAME]." - ". $row[LASTNAME]. "- </br>");
    if ($row[FIRSTNAME] && $row[LASTNAME]) {
        // Make sure we set $contactListSheetData[$index][MONTHLY_REPORTS] to an array (overriting prev value)
        $contactListSheetData[$index][MONTHLY_REPORTS] = [];

        // Now push each monthts sheet data onto it
        foreach ($reportSheets as $reportsIndex => $reportSheet) {
            // Before removing headers from data save it in other var
            $reportHeaders = $reportSheet[0];
            unset($reportSheet[0]);
            // Now scan for matching name row
            // keep track of if we found a match
            $publisherFound = false;
            foreach($reportSheet as $reportRow) {
                // For some stupid reason reportSheet is pasted on cell offset to the right .. so accout for that
                $offset = 1;
                if ($reportRow[FIRSTNAME + $offset] === $row[FIRSTNAME] && 
                    $reportRow[LASTNAME + $offset] === $row[LASTNAME]) {
                    // Splice up to key 17
                    array_splice($reportRow, 18);
                    $contactListSheetData[$index][MONTHLY_REPORTS][] = $reportRow;
                    // When we find a match we can stop searching
                    $publisherFound = true;
                    break;
                }
            }

            // If we found a publisher in a monthlyReportSheet that we cannot find in the contactList
            // and it is not set to ignore. 
            if (!$publisherFound && !in_array($row[FIRSTNAME]." - ". $row[LASTNAME], $namesToIgnore)) {
                $contactListSheetData['missing'][$row[FIRSTNAME]." - ". $row[LASTNAME]][] = "Coould not find " .$index . " : " .$row[FIRSTNAME]." - ". $row[LASTNAME] . " in shhet ". $reportSheetsNames[$reportsIndex];

            }
        }
    }
    
	$i++;
}



echo("<pre>");
print_r($contactListSheetData);
echo("</pre>");

?>