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

            $filename = $filename ? $filename :  'pdf_' . rand(2000,1200000) . '.pdf';

            $pdf = new Pdf('./template_pdf/S-21_Z.pdf');
            $pdf->fillForm($this->publisherFormData)
            //->flatten()
            ->saveAs( './output/' . $filename);
            //->send( $filename . '.pdf');

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
     * 
     * 
     */
    public function setFirstServiceYear(string $serviceYear) {
        // Make sure it is a proper year before "/"
        [$leftSide, $rightSide] = explode('/' , $serviceYear);
        $format = 'Y';
        $yearForFirstHalf = \DateTime::createFromFormat($format, $leftSide);
        // Die if no year found

        // If not died yet set it on instance for future calculations
        $this->firstServiceYearStartYear = clone $yearForFirstHalf;
        $yearForSecondHalf = clone $yearForFirstHalf;
        $yearForSecondHalf->modify('+ 1year');
        $serviceYearString = $yearForFirstHalf->format('Y') . '/' . $yearForSecondHalf->format('y');

        $this->publisherFormData = [
            'Service Year' => $serviceYearString,
        ];

    }

    /**
     * Add report-row for table 1 or 2 and the name of month
     * $yearAndMonth passed to this function should be created by
     * \DateTime::createFromFormat('Y-m', 'XXXX-mm')
     */
    public function addReportRow(\DateTime $yearAndMonth, $reportRow) {
        // {table_nr}-Place_{row_nr}  row nr is 1 = september since that is first of the service year
        
        // Validate all used data
        if (!$this->firstServiceYearStartYear instanceof \DateTime) {
            throw new \Exception('Cannot add report-row when first serviceYear startYear is not configured');
        }
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

        echo($minMonth->format('Y-m') . '/' . $maxMonth->format('Y-m') . 'Use table: ' . $tableNr . '  row ' .$rowNrToAdd);

        $this->publisherFormData["${tableNr}-Place_${rowNrToAdd}"] = '2';

        return $this;
    }

    /**
     * Takes a array in the format we have for a publisher in kontaktlistan and 
     * returns the dataArray nneded to fill reportCard with coresponding information 
     * 
     */
    public function generateNewCardData($publisherArray) {
        // Some initial data is always expected
        $publisherFormData = [
            'Name' => $publisherArray[LASTNAME]. ', '. $publisherArray[FIRSTNAME],
            // Male or femail
            'Check Box1' => trim($publisherArray[SEX]) == 'M' ? 'Yes' : 'No',
            'Check Box2' => trim($publisherArray[SEX]) == 'K' ? 'Yes' : 'No',
            // Anointed or other cheep
            'Check Box3' =>'Yes',
            // 'Check Box4' => 'No' // We have no filed in excel for anointed right now
            'Check Box5' => trim($publisherArray[IS_ELDER]) == '1' ? 'Yes' : 'No',
            'Check Box6' => trim($publisherArray[IS_SERVANT]) == '1' ? 'Yes' : 'No',
            'Check Box7' => trim($publisherArray[IS_PIONEER]) == '1' ? 'Yes' : 'No',
            'Date of birth' => trim($publisherArray[BIRTHDATE]),
        ];

        // Birthdate should not be missing but .. still it might
        if (trim($publisherArray[BIRTHDATE])) {
            //$birthDate = new \DateTime(trim($publisherArray[BIRTHDATE]));
            //$formData['Date of birth'] = $birthDate->format('Y-m-d');
            // Wa want to do something like above .. but we need to fix contac-list first
            // Untill then .. just set string as is
            $formData['Date of birth'] = trim($publisherArray[BIRTHDATE]);
        }
        
        // Batism date we do not touch if we do not have one
        if (trim($publisherArray[BAPTIMSDATE])) {
            // Wait untill we have all dates in same format untill we try to perform this stunt
            //$imersedDate = new \DateTime(trim($publisherArray[BAPTIMSDATE]));
            //$formData['Date immersed'] = $imersedDate->format('Y-m-d');

            // Until fixed we just input it as is
            $publisherFormData['Date immersed'] = trim($publisherArray[BAPTIMSDATE]);
        }
        $this->publisherFormData = $publisherFormData;
        return $this;
    }

    public function getFilename() {
        return $this->filename;
    }
}