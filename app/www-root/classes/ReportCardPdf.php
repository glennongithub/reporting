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
     */
    public function addReportRow($yearAndMonth, $reportRow) {
        // {table_nr}-Place_{row_nr}  row nr is 1 = september since that is first of the service year
        // So I need some logical way to manage that.. 
        // Maybe actually just use year and moth instead of tableNr and month .. 
        // Then make sure that reportCard have a property that say what service year the card is starting at
        if (!$this->firstServiceYearStartYear instanceof \DateTime) {
            throw new Exception('Cannot add report-row when first serviceYear startYear is not configured');
        }

        // and figure out if the given month is inside that .. or not and place it properly
        // If it is not withing what a 2 sided report card can handle .. we throw error

        // If have not died we have a start year and know min and max month this card can handle
        // Min month is september of firstStartYear
        $minMonth = new \DateTime($this->firstServiceYearStartYear->format('Y') . '-09-01' );

        // Maxmonth is +23 months.
        $maxMonth = clone $minMonth;
        $maxMonth->modify('+23 month');

        echo($minMonth->format('Y-m') . '/' . $maxMonth->format('Y-m'));





        $this->publisherFormData["${tableNr}-Place_1"] = '2';
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

        // Try to handle stupid vatying format of dates
        //$birthDate = new \DateTime(trim($publisherArray[BIRTHDATE]));
        //$formData['Date of birth'] = $birthDate->format('Y-m-d');

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