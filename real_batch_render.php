<?php
/*** 
 * real_batch_render.php
 * by Wolf I. Butler
 * v. 1.2, Last Updated: 12/08/2023
 * 
 * Script using the xLights API to render all sequences
 * in the current Show Directory. 
 * 
 * Tools -> Batch Render only saves .fseq files, and not the .xsq files.
 * This saves BOTH.
 * 
 * If you monitor xLights while it is running, you can also fix any model
 * mapping issues "on-the-fly" and they will actually be saved.
 *  
 * This was designed to work on Mac or Linux with PHP installed.
 * For Windows- PHP CLI will need to be installed, and you will need to use
 * the Windows format for the Show Folder path.
 *
 * This is open source, do what you want with it. As-is, no-warranty.
 * 
*/

//Be sure the API server is enabled in xLights. 
//As of this writing, it is set in Preferences -> Output. Set xFade/xSchedule to "Port A".

//Just set this...
define ( 'SHOWDIR', '/Users/wolf/xLights Sync/2023/Christmas' );   //This needs to match your xLights Show Directory

//You shouldn't need to chnage this unless xLights devs change the port number (49913)
define ( 'BASEURL', 'http://127.0.0.1:49913' ); //This is the default for the xLights API.

//Skip to this sequence. Used if xLights crashes while running this script. Set to FALSE if no need.
$skipTo = FALSE;
//$skipTo='Text Me Merry Christmas (feat. Kristen Bell).xsq';

//Leave the rest alone...

//Send GET request to the API
function do_get ( $request ) {
    //Initiate cURL.
    $ch = curl_init(BASEURL . '/' . $request);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
//    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    
    //Set high timouts as some sequences take a long time to render...
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600); //Yes- 10 minutes, for long renders!

    if ( $curlResult = curl_exec($ch) ) {
        if ( $json = json_decode ( $curlResult, TRUE ) ) return $json;
        else return $curlResult;
    }
    return FALSE;
}

function disp_result ( $input ) {
    //Since the still-in-development API doesn't always return an array, process returned text accordingly.
    if ( is_array ( $input ) ) {
        foreach ( $input as $index => $value ) echo "\n\t$index: $value";
    }
    else echo "\n\t$input";
}

//Make sure xLights is running...
if ( ! do_get ( 'getVersion' ) ) {
    echo "\nYou must have xLights open to run this script!\n\n";
    exit;
};

//Get file list from current directory and process all .xsq files:
$arrDir = scandir ( SHOWDIR );
do_get ( "closeSequence");  //If xLights already has a sequence open, close it.
foreach ( $arrDir as $file ) {
    if ( substr ( $file, -4 ) == '.xsq' ) {
        if($skipTo) {
            if ($file != $skipTo) {
                 echo "\nSkipping $file...";
                 continue;
            }
            $skipTo = FALSE;
        }
        $encFile = str_replace ( ' ', '%20', $file );
        echo "\nProcessing: " . $file;
        disp_result ( do_get ( "openSequence/$encFile" ) );
        echo "\n\tRendering...";
        disp_result ( do_get ( "renderAll") );
        disp_result ( do_get ( "saveSequence") );
        disp_result ( do_get ( "closeSequence") );
        echo "\n\tDone.\n";
    }
} 

?>