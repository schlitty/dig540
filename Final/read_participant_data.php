<?php
    error_reporting(E_ALL); 
    ini_set("display_errors", 1); 
    include_once("./includes/db_config.php");
    include_once("./includes/participant.php");

    //Open the file
    $file_handle = fopen('./participants.csv', 'r');

    //Read the first line (which is the row of headers)
    $first_line = fgetcsv($file_handle);

    //Print out the headers
    for($i=0; $i<count($first_line); $i++){
        print_r('Column header found: '.$first_line[$i].'<br>');
    }

    //Create an empty array that will be filled with participants
    $participants = array();
    
    //This loop reads through the data file and instantiates participant objects for each row
    //It stores these objects in the $participants array
    while($data_row = fgetcsv($file_handle)){
        $participant = new participant();
        $participant->setData($data_row);
        $participant->save();
        array_push($participants, $participant);
    }

    //Close the file
    fclose($file_handle);
?>