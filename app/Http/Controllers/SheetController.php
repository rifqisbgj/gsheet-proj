<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SheetController extends Controller
{
    
    private $client;

    private $googleSheetService;

    public function __construct() {
        $this->client = new \Google_Client();
        $this->client->setApplicationName(env('APP_TITLE'));
        $this->client->setDeveloperKey(env('GOOGLE_SERVER_KEY'));

    }

    public function index()
    {
        return view('home');
    }

    public function getData(Request $req)
    {
        $path = parse_url($req->link);
        preg_match("/d\/(.*?)\/edit/", $path['path'], $matches);
        
        if(count($matches) != 2) {
            return [
                'error' => true,
                'message' => 'Invalid Url.'
            ];
        }
        
        $spid = $matches[1];
        $this->client->setAccessToken(Auth::user()->token);
        $this->googleSheetService = new \Google_Service_Sheets($this->client);
        $dim = $this->getDimensions($spid);
        $colRange = 'Sheet1!1:1';
        $range = 'Sheet1!A1:'.$dim['colCount'];

        $data = $this->googleSheetService
                    ->spreadsheets_values
                    ->batchGet($spid,['ranges'=>$range]);
        
        $att = $data->getValueRanges()[0]->values;
        $row = [];

        for ($i=1; $i < count($att); $i++) { 
            $rowObject = [];
            for ($j=0; $j < count($att[$i]); $j++) { 
                $rowObject[$att[0][$j]] = $att[$i][$j];
            }
            array_push($row,$rowObject);
        }
        $filename = "data.json";
        $handle = fopen($filename, 'w+');
        fputs($handle, json_encode($row));
        fclose($handle);
        $headers = array('Content-type'=> 'application/json');
        return response()->download($filename,'data.json',$headers);
        dd(json_encode($row));
        // return view('detail');
    }

    private function getDimensions($spreadSheetId)
    {
        $rowDimensions = $this->googleSheetService->spreadsheets_values->batchGet(
            $spreadSheetId,
            ['ranges' => 'Sheet1!A:A','majorDimension'=>'COLUMNS']
        );

        //if data is present at nth row, it will return array till nth row
        //if all column values are empty, it returns null
        $rowMeta = $rowDimensions->getValueRanges()[0]->values;
        if (! $rowMeta) {
            return [
                'error' => true,
                'message' => 'missing row data'
            ];
        }

        $colDimensions = $this->googleSheetService->spreadsheets_values->batchGet(
            $spreadSheetId,
            ['ranges' => 'Sheet1!1:1','majorDimension'=>'ROWS']
        );
        
        //if data is present at nth col, it will return array till nth col
        //if all column values are empty, it returns null
        $colMeta = $colDimensions->getValueRanges()[0]->values;
        if (! $colMeta) {
            return [
                'error' => true,
                'message' => 'missing row data'
            ];
        }

        return [
            'error' => false,
            'rowCount' => count($rowMeta[0]),
            'colCount' => $this->colLengthToColumnAddress(count($colMeta[0]))
        ];
    }

    public  function colLengthToColumnAddress($number)
    {
        if ($number <= 0) return null;

        $temp; $letter = '';
        while ($number > 0) {
            $temp = ($number - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $number = ($number - $temp - 1) / 26;
        }
        return $letter;
    }
}
