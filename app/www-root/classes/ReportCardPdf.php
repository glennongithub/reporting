<?php
namespace Classes;

/*
if(!defined('ACCESSCHECK')) {
      die('Direct access not permitted');
}
*/


use mikehaertl\pdftk\Pdf;

class ReportCardPdf {

    public $publisherFormData;

    private $firstServiceYearStartYear;

    public $filename;

    public function __construct() {
        // Possibly construct a object from existing file.
        // that matches the name passed.



    }

    public function generatePdfFile($filename)
    {      
        try {

            $filename = $filename ? $filename . '.pdf' :  'pdf_' . rand(2000,1200000) . '.pdf';

            $pdf = new Pdf('./template_pdf/S-21_Z.pdf');
            $pdf->fillForm($this->publisherFormData)
            //->flatten()
            ->saveAs( './output/' . $filename);
            //->send( $filename);

            $this->filename = $filename;
            return $this;

        }
        catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    /**
     * Expects service year in format "2020/21" or "2020"
     * Setts $this->firstServiceYearStartYear so we can use it for calculations for what rownomber a report should go to
     * But also sets formData for both page 1 and 2
     * 
     */
    public function setFirstServiceYear(string $serviceYear) {
        // Make sure it is a proper year before "/"
        [$leftSide, $rightSide] = explode('/' , $serviceYear);
        $format = 'Y';
        $yearForFirstHalf = \DateTime::createFromFormat($format, $leftSide);
        // Die if no year found

        print_r($yearForFirstHalf);

        // If not died yet set it on instance for future calculations
        $this->firstServiceYearStartYear = clone $yearForFirstHalf;
        
        // Prepare data to fill form page 1
        $yearForSecondHalf = clone $yearForFirstHalf;
        $yearForSecondHalf->modify('+ 1year');
        $serviceYearString = $yearForFirstHalf->format('Y') . '/' . $yearForSecondHalf->format('y');

        // and page2
        $page2yearForSecondHalf = clone $yearForSecondHalf;
        $page2yearForSecondHalf->modify('+ 1year');
        $page2ServiceYearString = $yearForSecondHalf->format('Y') . '/' . $page2yearForSecondHalf->format('y');

        $this->publisherFormData['Service Year'] = $serviceYearString;
        $this->publisherFormData['Service Year_2'] = $page2ServiceYearString;
    }

    /**
     * Add report-row for table 1 or 2 and the name of month
     */
    public function addReportRow($reportRow) {
        // {table_nr}-Place_{row_nr}  row nr is 1 = september since that is first of the service year
        
        // Validate all used data
        if (!$this->firstServiceYearStartYear instanceof \DateTime) {
            throw new \Exception('Cannot add report-row when first serviceYear startYear is not configured');
        }

        $yearAndMonth = \DateTime::createFromFormat('Y-n-d', $reportRow['month'] . '-01');

        if (!$yearAndMonth instanceof \DateTime) {
            throw new \ Exception('Cannot add report-row. Passed yearAndMonth is not a proper DateTime');
        }

        // If have not died we have a start year and know min and max month this card can handle
        // Min month is september of firstStartYear
        $minMonth = new \DateTime($this->firstServiceYearStartYear->format('Y') . '-09-01' );

        // Maxmonth is +23 months.
        $maxMonth = clone $minMonth;
        $maxMonth->modify('+23 month')->modify('+1 day');
        // Adding one hour extra since below test will somehow see yearAndMonth as bigger thatmaxMonth if they are on same month

        // Is given month is inside that .. or not and place it properly
        // If it is not withing what a 2 sided report card can handle .. we throw error

        if ($yearAndMonth > $maxMonth || $yearAndMonth < $minMonth) {
            throw new \Exception('Cannot add report-row. yearAndMonth (' . $yearAndMonth->format('Y-m-d') . ') is not within range for this reportCard:' . $minMonth->format('Y-m-d') . '/' . $maxMonth->format('Y-m-d'));
        }

        // Get months between start of page 1 service year and incomming row
        $diff = $minMonth->diff($yearAndMonth);
        // Row-nr bellow first row (sept of firstPage)
        $monthsDiff = ($diff->format('%y') * 12) + $diff->format('%m');
        $rowNrToAdd = $monthsDiff +1; // no diff means 0 but first row has id 1 .. so +1
        // Assume tableNr = 1 (first page)
        $tableNr = 1;
        // then we have 2 pages .. (2 serviceYears) so use modulus operator to get reminder if higher than 12
        if ($rowNrToAdd > 12) {
            // We know we must be on table 2
            $tableNr = 2;
            $rowNrToAdd = $rowNrToAdd % 12;
            // If the rest is nothing .. we are on 12th row .. we handle that.
            $rowNrToAdd = ($rowNrToAdd == 0) ? 12 : $rowNrToAdd;

        }
        if ($reportRow[PLACE_COL_NR]) {
            $this->publisherFormData["${tableNr}-Place_${rowNrToAdd}"] = $reportRow[PLACE_COL_NR];
        }
        
        if ($reportRow[VIDEO_COL_NR]) {
            $this->publisherFormData["${tableNr}-Video_${rowNrToAdd}"] = $reportRow[VIDEO_COL_NR];  
        }
        
        if ($reportRow[HOURS_COL_NR]) {
            $this->publisherFormData["${tableNr}-Hours_${rowNrToAdd}"] = $reportRow[HOURS_COL_NR];
        }
        
        if ($reportRow[RV_COL_NR]) {
            $this->publisherFormData["${tableNr}-RV_${rowNrToAdd}"] = $reportRow[RV_COL_NR];
        }
        
        if ($reportRow[STUDIES_COL_NR]) {
            $this->publisherFormData["${tableNr}-Studies_${rowNrToAdd}"] = $reportRow[STUDIES_COL_NR];
        }
        

        // On page two, these formField have the extrension _2
        $remarksFormFieldName = 'Remarks' . MONTH_NAMES_FOR_REMARKS[$rowNrToAdd] . (($tableNr === 2)? '_2' : '');
        
        // ADD 'HP + other remark
        // This is not consistently used . sometimes a 1 is representing that publisher is HP . sometimes the strin HP sometimes there are ather notes in the same field
        $valueFromHPCol = $reportRow[AUX_PIONEER_THIS_MONTH_COL_NR];

        // If anything at all i written in this col we assume HP is true.
        $isHp = trim($valueFromHPCol) !== '';

        // Remove parts of the string we can think of that is only there to indicate HP is true
        $valueFromHPCol = str_ireplace(['1', 'HP', 'x'], '', $valueFromHPCol);

        // Now add any part that is left of the content from HPCol but prepend it with HP if we decided that that was the case above
        $remarkValue = ($isHp ? 'HP' : '') . $valueFromHPCol;
        
        
        // if remark is x .. we just filter it .. seam to be used to bark done or something during input of data I guess
        $remarkValue .= (strtolower(trim($reportRow[REMARKS_COL_NR])) !== 'x') ? ' '. $reportRow[REMARKS_COL_NR] : '';

        if ($remarkValue) {
            $this->publisherFormData[$remarksFormFieldName] = $remarkValue;
        }

        return $this;
    }

    public function generateTotalsAndAverage($monthlyReports) {
        
        // Using field constants as indexes to easily loop through them later

        $page1RowCount = 0;
        $page1Totals = [
            PLACE_COL_NR => 0,
            VIDEO_COL_NR => 0,
            HOURS_COL_NR => 0,
            RV_COL_NR => 0,
            STUDIES_COL_NR => 0,

        ];

        $page2RowCount = 0;
        $page2Totals = [
            PLACE_COL_NR => 0,
            VIDEO_COL_NR => 0,
            HOURS_COL_NR => 0,
            RV_COL_NR => 0,
            STUDIES_COL_NR => 0,

        ];
        
        foreach ($monthlyReports as $reportRow) {
            $yearAndMonth = \DateTime::createFromFormat('Y-n-d', $reportRow['month'] . '-01');

            // Do exactly same tests as when we add singleRow to reportCard

            // Min month is september of firstStartYear
            $minMonth = new \DateTime($this->firstServiceYearStartYear->format('Y') . '-09-01' );

            // Maxmonth is +23 months.
            $maxMonth = clone $minMonth;
            $maxMonth->modify('+23 month')->modify('+1 day');
            // Adding one hour extra since below test will somehow see yearAndMonth as bigger thatmaxMonth if they are on same month

            // Is given month is inside that .. or not and place it properly
            // If it is not withing what a 2 sided report card can handle .. we throw error

            if ($yearAndMonth > $maxMonth || $yearAndMonth < $minMonth) {
                throw new \Exception('Cannot add report-row. yearAndMonth (' . $yearAndMonth->format('Y-m-d') . ') is not within range for this reportCard:' . $minMonth->format('Y-m-d') . '/' . $maxMonth->format('Y-m-d'));
            }

            // Get months between start of page 1 service year and incomming row
            $diff = $minMonth->diff($yearAndMonth);
            // Row-nr bellow first row (sept of firstPage)
            $monthsDiff = ($diff->format('%y') * 12) + $diff->format('%m');
            $rowNrToAdd = $monthsDiff +1; // no diff means 0 but first row has id 1 .. so +1
            // Assume tableNr = 1 (first page)
            $tableNr = 1;
            // then we have 2 pages .. (2 serviceYears) so use modulus operator to get reminder if higher than 12
            if ($rowNrToAdd > 12) {
                // We know we must be on table 2
                $tableNr = 2;
                $rowNrToAdd = $rowNrToAdd % 12;
                // If the rest is nothing .. we are on 12th row .. we handle that.
                $rowNrToAdd = ($rowNrToAdd == 0) ? 12 : $rowNrToAdd;
            }

            // We do not really need the rowNrToAdd but lets keep it for now untill we see if we might need it for some other purpouse

            // Now just sum up the totals and how many rows we worked through so we also can get average.

            if ($tableNr == 1) {
                // inc rouw count
                $page1RowCount += 1;

                // sum to each field
                foreach ($page1Totals as $fieldIndex => $sum) {
                    $page1Totals[$fieldIndex] += $reportRow[$fieldIndex];
                }

            }

            if ($tableNr == 2) {
                // inc rouw count
                $page2RowCount += 1;

                // sum to each field
                foreach ($page2Totals as $fieldIndex => $sum) {
                    $page2Totals[$fieldIndex] += $reportRow[$fieldIndex];
                }

            }
        }

        // All rows looped so we have total and nr of rows on each page
        // Calculate the average and write values to corresponding fields in pdf

        if (count($page1RowCount) > 0) {
          
            $this->publisherFormData["1-Place_Total"] = $page1Totals[PLACE_COL_NR];
            $this->publisherFormData["1-Place_Average"] = number_format($page1Totals[PLACE_COL_NR]/$page1RowCount, 2, '.', '');
            
            $this->publisherFormData["1-Video_Total"] = $page1Totals[VIDEO_COL_NR];  
            $this->publisherFormData["1-Video_Average"] = number_format($page1Totals[VIDEO_COL_NR]/$page1RowCount, 2, '.', '');
            
            $this->publisherFormData["1-Hours_Total"] = $page1Totals[HOURS_COL_NR];
            $this->publisherFormData["1-Hours_Average"] = number_format($page1Totals[HOURS_COL_NR]/$page1RowCount, 2, '.', '');
            
            $this->publisherFormData["1-RV_Total"] = $page1Totals[RV_COL_NR];
            $this->publisherFormData["1-RV_Average"] = number_format($page1Totals[RV_COL_NR]/$page1RowCount, 2, '.', '');
        
            $this->publisherFormData["1-Studies_Total"] = $page1Totals[STUDIES_COL_NR];
            $this->publisherFormData["1-Studies_Average"] = number_format($page1Totals[STUDIES_COL_NR]/$page1RowCount, 2, '.', '');
        }

        if (count($page2RowCount) > 0) {
          
            $this->publisherFormData["2-Place_Total"] = $page2Totals[PLACE_COL_NR];
            $this->publisherFormData["2-Place_Average"] = number_format($page2Totals[PLACE_COL_NR]/$page2RowCount, 2, '.', '');
            
            $this->publisherFormData["2-Video_Total"] = $page2Totals[VIDEO_COL_NR];  
            $this->publisherFormData["2-Video_Average"] = number_format($page2Totals[VIDEO_COL_NR]/$page2RowCount, 2, '.', '');
            
            $this->publisherFormData["2-Hours_Total"] = $page2Totals[HOURS_COL_NR];
            $this->publisherFormData["2-Hours_Average"] = number_format($page2Totals[HOURS_COL_NR]/$page2RowCount, 2, '.', '');
            
            $this->publisherFormData["2-RV_Total"] = $page2Totals[RV_COL_NR];
            $this->publisherFormData["2-RV_Average"] = number_format($page2Totals[RV_COL_NR]/$page2RowCount, 2, '.', '');
        
            $this->publisherFormData["2-Studies_Total"] = $page2Totals[STUDIES_COL_NR];
            $this->publisherFormData["2-Studies_Average"] = number_format($page2Totals[STUDIES_COL_NR]/$page2RowCount, 2, '.', '');
        }

    }

    /**
     * Takes a array in the format we have for a publisher in kontaktlistan and 
     * returns the dataArray nneded to fill reportCard with coresponding information 
     * 
     */
    public function generateNewCardData($publisherArray) {
        // Some initial data is always expected
        $this->publisherFormData['Name'] = $publisherArray[LASTNAME]. ', '. $publisherArray[FIRSTNAME];
        // Male or femail
        $this->publisherFormData['Check Box1'] = trim($publisherArray[SEX]) == 'M' ? 'Yes' : 'No';
        $this->publisherFormData['Check Box2'] = trim($publisherArray[SEX]) == 'K' ? 'Yes' : 'No';
        // Anointed or other cheep
        $this->publisherFormData['Check Box3'] ='Yes';
        // 'Check Box4' => 'No' // We have no filed in excel for anointed right now
        $this->publisherFormData['Check Box5'] = trim($publisherArray[IS_ELDER]) == '1' ? 'Yes' : 'No';
        $this->publisherFormData['Check Box6'] = trim($publisherArray[IS_SERVANT]) == '1' ? 'Yes' : 'No';
        $this->publisherFormData['Check Box7'] = trim($publisherArray[IS_PIONEER]) == '1' ? 'Yes' : 'No';
        $this->publisherFormData['Date of birth'] = trim($publisherArray[BIRTHDATE]);

        // Birthdate should not be missing but .. still it might
        if (trim($publisherArray[BIRTHDATE])) {
            //$birthDate = new \DateTime(trim($publisherArray[BIRTHDATE]));
            //$formData['Date of birth'] = $birthDate->format('Y-m-d');
            // Wa want to do something like above .. but we need to fix contac-list first
            // Untill then .. just set string as is
            $this->publisherFormData['Date of birth'] = trim($publisherArray[BIRTHDATE]);
        }
        
        // Batism date we do not touch if we do not have one
        if (trim($publisherArray[BAPTIMSDATE])) {
            // Wait untill we have all dates in same format untill we try to perform this stunt
            //$imersedDate = new \DateTime(trim($publisherArray[BAPTIMSDATE]));
            //$formData['Date immersed'] = $imersedDate->format('Y-m-d');

            // Until fixed we just input it as is
            $this->publisherFormData['Date immersed'] = trim($publisherArray[BAPTIMSDATE]);
        }
    
        return $this;
    }

    public function getFilename() {
        return $this->filename;
    }
}