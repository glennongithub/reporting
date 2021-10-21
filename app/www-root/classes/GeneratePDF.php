<?php
namespace Classes;

/*
if(!defined('ACCESSCHECK')) {
      die('Direct access not permitted');
}
*/


use mikehaertl\pdftk\Pdf;

class GeneratePDF {
    public function generate($data, $filename)
    {      
        echo("test");
        try {

            $filename = $filename ? $filename :  'pdf_' . rand(2000,1200000) . '.pdf';

            $pdf = new Pdf('./template_pdf/S-21_Z.pdf');
            $pdf->fillForm($data)
            //->flatten()
            ->saveAs( './output/' . $filename);
            //->send( $filename . '.pdf');

            return $filename;

        }
        catch(Exception $e)
        {
            return $e->getMessage();
        }


    }
}