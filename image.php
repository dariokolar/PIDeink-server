<?php

// our preselected stops from PID api: https://data.pid.cz/stops/json/stops.json
$st[0] = array("U899Z4P","U899Z3P");
$stops = urlencode(json_encode($st));

$url = "https://api.golemio.cz/v2/public/departureboards?stopIds={$stops}&limit=16&minutesAfter=60";

// curl to pid api
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$headers = [
    'accept: application/json',
    'X-Access-Token: {get your token here: https://api.golemio.cz/api-keys/auth/sign-in}'
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo 'Chyba: ' . curl_error($ch);
} else {


$response = json_decode($response, true);
$title = "Avia Letňany";

$rows = array();
foreach ($response[0] as $dep){

    if($dep["trip"]["headsign"] == "Sídliště Letňany"){
        continue;
        // we can filter some responses here, like buses we dont want by number or destination
    }
    $i++;
    if($i > 7){
        break;
    }

    if($dep["departure"]["minutes"] == 0){
        $dep["departure"]["minutes"] = "Teď";
    }else{
        $dep["departure"]["minutes"] .= " min";
    }

    $delay = round($dep["departure"]["delay_seconds"]/60);
    if($delay == 0){
        $depararture = date("H:i", strtotime($dep["departure"]["timestamp_scheduled"]));
    }else{
        $depararture = date("H:i", strtotime($dep["departure"]["timestamp_predicted"]));
        $originaldeparture = date("H:i", strtotime($dep["departure"]["timestamp_scheduled"]));
    }

    $metro = false;
    if($dep["trip"]["headsign"] == "Černý Most"){ $metro = "B"; }
    if($dep["trip"]["headsign"] == "Letňany"){ $metro = "C"; }
    if($dep["trip"]["headsign"] == "Kobylisy"){ $metro = "C"; }
    if($dep["trip"]["headsign"] == "Nádraží Holešovice"){ $metro = "C"; }
    if($dep["trip"]["headsign"] == "Skalka"){ $metro = "A"; }

    $rows[] = array(
            'number' => $dep["route"]["short_name"],
            'destination' => $dep["trip"]["headsign"],
            'metrodestination' => $metro,
            'departure' => $depararture,
            'delay' => $delay,
            'original_departure' => $originaldeparture, // Optional, if delay
            'countdown' => $dep["departure"]["minutes"],
            'air_conditioned' => $dep["vehicle"]["is_air_conditioned"],
            'step_free_access' => $dep["vehicle"]["is_wheelchair_accessible"],
            'charger_on_board' => $dep["vehicle"]["has_charger"]
    );
}

}

// we have prepared our data and can create image now:



// Configuration
$imageWidth = 800;   // Width of new image (adjust to your needs)
$imageHeight = 480;  // Height of new image (adjust to your needs)
$backgroundColor = 'white'; // Background color
$fontPath = dirname(__FILE__) . "/webfonts/prague.ttf"; // Path to a .ttf font file
$fontOsMedium = dirname(__FILE__) . "/webfonts/OpenSans-Medium.ttf"; // Path to a .ttf font file
$fontOSBold = dirname(__FILE__) . "/webfonts/OpenSans-Bold.ttf"; // Path to a .ttf font file

$iconAC = new Imagick(dirname(__FILE__) ."/img/snowflake-regular.png"); // Adjust path
$iconStepFree = new Imagick(dirname(__FILE__) ."/img/wheelchair-regular.png"); // Adjust path
$iconCharger = new Imagick(dirname(__FILE__) ."/img/battery-bolt-regular.png"); // Adjust path

$iconMetroC = new Imagick(dirname(__FILE__) ."/img/cecko.png"); // Adjust path
$iconMetroB = new Imagick(dirname(__FILE__) ."/img/becko.png"); // Adjust path
$iconMetroA = new Imagick(dirname(__FILE__) ."/img/acko.png"); // Adjust path


$iconPID = new Imagick(dirname(__FILE__) ."/img/pid.png"); // Adjust path
$iconPID->resizeImage(42, 30, Imagick::FILTER_LANCZOS, 1);

// Resize icons to smaller (optional, if needed)
$iconAC->resizeImage(14, 14, Imagick::FILTER_LANCZOS, 1);
$iconStepFree->resizeImage(14, 14, Imagick::FILTER_LANCZOS, 1);
$iconCharger->resizeImage(14, 14, Imagick::FILTER_LANCZOS, 1);


$iconMetroC->resizeImage(44, 22, Imagick::FILTER_LANCZOS, 1);
$iconMetroB->resizeImage(44, 22, Imagick::FILTER_LANCZOS, 1);
$iconMetroA->resizeImage(44, 22, Imagick::FILTER_LANCZOS, 1);

// Create a new blank image
$image = new Imagick();
$image->newImage($imageWidth, $imageHeight, new ImagickPixel($backgroundColor));
$image->setImageColorspace(Imagick::COLORSPACE_GRAY);
$image->setImageFormat('png'); // Output as BMP

// Create draw object
$draw = new ImagickDraw();
$draw->setFont($fontPath);
$draw->setFontSize(36); // Font size
$draw->setFillColor('black'); // Text color (white on black)

// Coordinates
$titleX = 10;   // X position for title
$titleY = 40;   // Y position for title

$rowStartX = 0;    // X position for rows
$rowStartY = 85;   // Y start for first row
$rowSpacing = 60;   // Vertical space between rows

// Draw title
$image->annotateImage($draw, $titleX, $titleY, 0, $title);

$image->compositeImage($iconPID, Imagick::COMPOSITE_OVER, $titleX+734, $titleY-26);

$colNumberX = 10;
$colDestinationX = 90;
$colOriginalDepartureX = 460;
$colDelayX = 535;
$colDepartureX = 630;
$colCountdownX = 720;

// Draw rows
foreach ($rows as $index => $row) {
    $currentY = $rowStartY + ($index * $rowSpacing);


    $lineDraw = new ImagickDraw();
    $lineDraw->setStrokeColor('black');
    $lineDraw->setStrokeWidth(1);
    $yLine = $currentY - 30; // Line a bit below text
    $lineDraw->line(10, $yLine, $imageWidth - 10, $yLine);
    $image->drawImage($lineDraw);


    $draw->setFont($fontPath);
    $draw->setFontSize(36); // Font size
    $draw->setFillColor('black'); // Text color (white on black)

    // Draw number
    $image->annotateImage($draw, $colNumberX, $currentY+14, 0, $row['number']);

    $draw->setFont($fontOsMedium);
    $draw->setFontSize(18); // Font size
    $draw->setFillColor('black'); // Text color (white on black)

    // Draw destination
    $tempColDestination = $colDestinationX;


    if($row["metrodestination"]){
        $tempColDestination += 50;
        if($row["metrodestination"] == "C"){
            $image->compositeImage($iconMetroC, Imagick::COMPOSITE_OVER, $colDestinationX, $currentY-17);
        }
        if($row["metrodestination"] == "A"){
            $image->compositeImage($iconMetroA, Imagick::COMPOSITE_OVER, $colDestinationX, $currentY-17);
        }
        if($row["metrodestination"] == "B"){
            $image->compositeImage($iconMetroB, Imagick::COMPOSITE_OVER, $colDestinationX, $currentY-17);
        }
    }

    $image->annotateImage($draw, $tempColDestination, $currentY, 0, $row['destination']);

    // Icon starting position (below destination)
    $iconBaseX = $colDestinationX;
    $iconBaseY = $currentY + 9; // 10px below destination text

    $iconSpacing = 22; // Space between icons

// Draw A/C icon if needed
    if (!empty($row['air_conditioned'])) {
        $image->compositeImage($iconAC, Imagick::COMPOSITE_OVER, $iconBaseX, $iconBaseY);
        $iconBaseX += $iconSpacing; // Move X for next icon
    }

// Draw Step-Free Access icon if needed
    if (!empty($row['step_free_access'])) {
        $image->compositeImage($iconStepFree, Imagick::COMPOSITE_OVER, $iconBaseX, $iconBaseY);
        $iconBaseX += $iconSpacing; // Move X for next icon
    }
// Draw Step-Free Access icon if needed
    if (!empty($row['charger_on_board'])) {
        $image->compositeImage($iconCharger, Imagick::COMPOSITE_OVER, $iconBaseX, $iconBaseY);
        $iconBaseX += $iconSpacing; // Move X for next icon
    }

    $draw->setFontSize(20); // Font size

    $currentY += 7;

    // Draw departure time
    if ($row['delay'] > 0) {

        $lineDraw = new ImagickDraw();
        $lineDraw->setStrokeColor('#424242');
        $lineDraw->setStrokeWidth(2);
        $lineDraw->line($colOriginalDepartureX -7, $currentY +3, $colOriginalDepartureX+53, $currentY -15);
        $image->drawImage($lineDraw);


        // Draw original time faded
        $drawFaded = clone $draw;
        $drawFaded->setFillColor('#424242');
        $image->annotateImage($drawFaded, $colOriginalDepartureX, $currentY, 0, $row['original_departure']);





        // Draw orange background for new departure
        $drawBlock = new ImagickDraw();
        //$drawBlock->setFillColor('#f36f21');
        $drawBlock->setFillColor('black');
        $blockWidth = 70;
        $blockHeight = 30;
        $blockX = $colDelayX;
        $blockY = $currentY - 23;
        $drawBlock->rectangle($blockX, $blockY, $blockX + $blockWidth, $blockY + $blockHeight);
        $image->drawImage($drawBlock);

        $draw->setFontSize(16); // Font size
        // Draw new departure time over orange block
        $drawNewTime = clone $draw;
        $drawNewTime->setFillColor('white'); // black text on orange


        $colDelayXtemp = $colDelayX+8;
        if($row['delay'] > 9){
            $colDelayXtemp -= 5;
        }
        $draw->setFont($fontOSBold);
        $image->annotateImage($drawNewTime, $colDelayXtemp, $currentY-1, 0, "+ ".$row['delay']." min");


    }

    $draw->setFontSize(20); // Font size

    $draw->setFont($fontOSBold);
    $image->annotateImage($draw, $colDepartureX, $currentY, 0, $row['departure']);

    // Draw countdown
    $draw->setFont($fontOsMedium);
    $image->annotateImage($draw, $colCountdownX, $currentY, 0, $row['countdown']);


}


$draw->setFont($fontOsMedium);
$draw->setFontSize(13); // Font size
$draw->setFillColor('#424242'); // Text color (white on black)
$image->annotateImage($draw, 677, 475, 0, date("j. n. Y H:i:s"));

// 🔥 Compress to 8-bit palette
#$image->quantizeImage(256, Imagick::COLORSPACE_RGB, 0, false, false);

//$image->quantizeImage(4, Imagick::COLORSPACE_GRAY, 0, false, false);

// 🔥 Save as BMP v3
//$image->setImageFormat('bmp3');

// 🔥 (Optional) Enable compression
//$image->setImageCompression(Imagick::COMPRESSION_RLE);
//$image->setImageCompressionQuality(0); // not really used, but safe


// Output the image
header('Content-Type: image/png');
echo $image;

// Optionally save to file
// $image->writeImage('/path/to/output.bmp');

// Clean up
$image->clear();
$image->destroy();
?>
