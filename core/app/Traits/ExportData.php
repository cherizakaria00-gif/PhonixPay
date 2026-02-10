<?php

namespace App\Traits;

use App\Constants\ExportField;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;

trait ExportData{

    public function scopeExport($query){ 

        $request = request();
        
        $exportType = $request->export_type;
        $supportedExportType = ['excel', 'csv', 'pdf'];

        if(!$exportType){
            return $query;
        }

        if(!in_array($exportType, $supportedExportType)){
            throw ValidationException::withMessages(['error' => 'Unsupported export type']);
        }

        $tableName = $this->getTable(); 
        $fileName = 'export_'.$tableName; 

        $export = new ExportField();
        $fields = $export->$tableName;

        if(!$request->is('admin*')){ 
            unset($fields['user']);
        }
     
        $query = $query->get();
        $html = $this->makeTable($query, $fields);

        if($exportType == 'csv'){
            $this->$exportType($html, $fileName); exit;
        }

        return $this->$exportType($html, $fileName);
    }

    private function makeTable($data, $table){
      
        $general = gs();
        $output = null;

        // Start table tag
        $output .="<table border='1' class='pdf-table'><thead><tr>";
        $output .= "<th>SL</th>";

        // Start table heading 
        foreach($table as $index => $thead){  

            if(gettype(@$thead) == 'array'){
                $th = @$thead['heading'];
            }else{
                if(gettype($thead) == 'array'){
                    $th = $index;
                }else{
                    $th = $thead;
                }
            }

            $output .= "
                <th>$th</th>
            ";
        }

        // End table heading 
        $output .="</tr></thead><tbody>"; 

        // Start table data
        foreach($data as $sl => $value){  

            $output .= "<tr>";
            $output .= "<td>" .  ++$sl . "</td>";

            foreach($table as $index => $tbody){

                // For relational data
                if(gettype(@$tbody) == 'array' && @$tbody['relation']){ 
                    $th = @$tbody['relation']['relation_name'];

                    $relation = $tbody['relation']['relation_name'];
                    $column = $tbody['relation']['column'];

                    $td = @$value->$relation->$column;
                }else{ 
                    // Without relational data
                    if(gettype($tbody) == 'array'){ 
                        $td = $value->$index;
                    }else{
                        $td = $value->$tbody;
                    }
                }

                // For additional config 
                if(gettype(@$tbody) == 'array'){
                    if(@$tbody['showAmount']){
                        $td = showAmount($td);
                    }
                    if(@$tbody['showDateTime']){ 
                        $td = showDateTime($td, 'd/m/Y');
                    } 
                }

                $output .= "<td>" . $td . "</td>";
            }

            $output .= "</tr>";
        }

        if(!count($data)){
            $output .= "<tr>
                            <td colspan='100%'>".trans('Data not found')."</td>
                        </tr>";
        }

        return $output .="</tbody></table>";
    }

    public function excel($html, $fileName){ 
        
        header("Content-Type: application/xls");    
        header("Content-Disposition: attachment; filename=$fileName.xls");  
        header("Pragma: no-cache"); 
        header("Expires: 0");

        return $html;
    }

    public function csv($html, $fileName){

        $data = array();
        $pattern = '/<tr>(.*?)<\/tr>/s';
        preg_match_all($pattern, $html, $matches);
        $headerRow = $matches[0][0];

        foreach ($matches[0] as $i => $row) {
            if ($i == 0) continue;

            $rowData = array();
            $pattern = '/<td>(.*?)<\/td>/s';
            preg_match_all($pattern, $row, $matches2);

            foreach ($matches2[1] as $col) {
                $rowData[] = trim(strip_tags($col));
            }
            $data[] = $rowData;
        }
        
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=$fileName.csv");

        $fp = fopen('php://output', 'w');
        $pattern = '/<th>(.*?)<\/th>/s';
        preg_match_all($pattern, $headerRow, $matches);

        $headerData = array_map(function($col) {
            return trim(strip_tags($col));
        }, $matches[1]);

        fputcsv($fp, $headerData);

        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

    public function pdf($html, $fileName){ 
        $pageTitle = "$fileName.pdf";
        $pdfContent = @getContent('pdf_content.content', true)->data_values;

        $pdf = Pdf::loadView('pdf', compact('html', 'pageTitle', 'pdfContent'));
        $pdf->setOptions([
            'enable_remote' => true,
        ]);

        $pdf->setPaper('a3', 'portrait');
        return $pdf->download($pageTitle);
    }

}


