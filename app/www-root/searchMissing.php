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
const MONTHLY_REPORTS = 'monthy_reports';

// Manually creating a array of publishers that exists in the contact list .. but not on all reportsSheets
// due to no longer in our cong. (No need to keep registercards)
$namesToIgnore = [
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
// $contactListSheetData[0] is the column header so lets save it as a new var with clear purpouse.
// $headerIndexMap = $contactListSheetData[0];

// Remove bottom rows that does not contin any intesresting data
array_splice($contactListSheetData, 150);

// Remove header row
unset($contactListSheetData[0]);
/*
echo("<pre>");
print_r($contactListSheetData);
echo("</pre>");
*/

// Prep missing arrays
$missingInContactList = [];
$missingForkunnare = [];

$forkunnareSheetData = $spreadsheet->getSheetByName('Forkunnare')->toArray();
// Remove bottom rows that does not contin any intesresting data
array_splice($forkunnareSheetData, 150);

// Remove header row
unset($forkunnareSheetData[0]);

$publisherNames = [];
// Now try to see if any publisher is missing.
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
        $publisherNames[] = $row[LASTNAME]." \t ". $row[FIRSTNAME];
        // Assume we will not find it
        $publisherFound = false;
        // only keep interesting columns
        array_splice($row, 21);

       
        foreach($forkunnareSheetData as $forkunnareRow) {
            // For some stupid reason reportSheet is pasted on cell offset to the right .. so accout for that
            if (trim($forkunnareRow[FIRSTNAME]) === trim($row[FIRSTNAME]) && 
                trim($forkunnareRow[LASTNAME]) === trim($row[LASTNAME])) {
                // Splice up to key 17
                array_splice($forkunnareRow, 18);
                // When we find a match we can stop searching
                $publisherFound = true;
                break;
            }
        }

        // If we did not find the publisher we have in contactSheet when we loop forkunnareSheet
        // and it is not set to ignore. 
        if (!$publisherFound && !in_array($row[FIRSTNAME]." - ". $row[LASTNAME], $namesToIgnore)) {
            $missingForkunnare[] = "Coould not find " .$index . " : " .$row[FIRSTNAME]." - ". $row[LASTNAME] . " in forkunnare shhet ";
        }
      
    }
    
	$i++;
}

sort($publisherNames);

foreach($publisherNames as $key => $name) {
    echo $key+1 . ": \t" . $name. '</br>';
}


echo("<pre>");
print_r($missingForkunnare);
echo("</pre>");

?>