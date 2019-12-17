<?php

/*
Plugin / Script for Nagios / Icinga2, checking Schneider APC USV.

V4n1X (C)2019

Version: master
*/
$host = $argv[1];
$community = "public";

$critical = false;
$warning = false;

$output = "";

$upsBasicOutputStatus = @snmpget($host, $community, ".1.3.6.1.4.1.318.1.1.1.4.1.1.0");
$upsBasicOutputStatus = str_replace("INTEGER: ", "", $upsBasicOutputStatus);

if(!$upsBasicOutputStatus) {
    fwrite(STDOUT, "Verbindung konnte nicht hergestellt werden.");
  	exit(2);
}

if($upsBasicOutputStatus != 2) {
  $error = getSystemStatus($upsBasicOutputStatus);
  $critical = true;
  $output .= "Status: " . $error . " - ";
}

$upsBasicBatteryStatus = snmpget($host, $community, ".1.3.6.1.4.1.318.1.1.1.2.1.1.0");
$upsBasicBatteryStatus = str_replace("INTEGER: ", "", $upsBasicBatteryStatus);

if($upsBasicBatteryStatus != 2) {
  $error = getBatteryStatus($upsBasicBatteryStatus);
  $critical = true;
  $output .= "Batterie: " . $error . " - ";
}

$upsAdvBatteryTemperature = snmpget($host, $community, ".1.3.6.1.4.1.318.1.1.1.2.2.2.0");
$upsAdvBatteryTemperature = str_replace("Gauge32: ", "", $upsAdvBatteryTemperature);

if($upsAdvBatteryTemperature > 45) {
  $critical = true;
  $output .= "Hohe Temperatur (" . $upsAdvBatteryTemperature . "°C)" . " - ";
}

if($upsAdvBatteryTemperature < 45 && $upsAdvBatteryTemperature > 40) {
  $warning = true;
  $output .= "Erhöhte Temperatur (" . $upsAdvBatteryTemperature . "°C)" . " - ";
}


$upsAdvBatteryReplaceIndicator = snmpget($host, $community, ".1.3.6.1.4.1.318.1.1.1.2.2.4.0");
$upsAdvBatteryReplaceIndicator = str_replace("INTEGER: ", "", $upsAdvBatteryReplaceIndicator);

if($upsAdvBatteryReplaceIndicator == 2) {
  $critical = true;
  $output .= "Batteriewechsel erforderlich" . " - ";
}

$upsAdvBatteryRunTimeRemaining = snmpget($host, $community, ".1.3.6.1.4.1.318.1.1.1.2.2.3.0");
$upsAdvBatteryRunTimeRemaining = str_replace("Timeticks: ", "", $upsAdvBatteryRunTimeRemaining);

$upsBasicIdentModel = snmpget($host, $community, ".1.3.6.1.4.1.318.1.1.1.1.1.1.0");
$upsBasicIdentModel = str_replace("STRING: ", "", $upsBasicIdentModel);
$upsBasicIdentModel = str_replace("\"", "", $upsBasicIdentModel);

$upsBasicBatteryLastReplaceDate = snmpget($host, $community, ".1.3.6.1.4.1.318.1.1.1.2.1.3.0");
$upsBasicBatteryLastReplaceDate = str_replace("STRING: ", "", $upsBasicBatteryLastReplaceDate);
$upsBasicBatteryLastReplaceDate = str_replace("\"", "", $upsBasicBatteryLastReplaceDate);

$upsBasicBatteryLastReplaceDate = strtotime($upsBasicBatteryLastReplaceDate);
$upsBasicBatteryLastReplaceDate = date('Y-m-d', $upsBasicBatteryLastReplaceDate);

$date1 = new DateTime($upsBasicBatteryLastReplaceDate);
$date2 = new DateTime(date('Y-m-d'));
$difference = $date2->diff($date1);
$difference_output = $difference->y . " Jahre " . $difference->m . " Monate " . $difference->d . " Tage";

if($difference->y > 3 || ($difference->y == 3 && $difference->m > 1)) {
  $warning = true;
  $output .= "Batteriealter: " . $difference_output . " (Letzter Tausch: " . $date1->format('d.m.Y') . ") - Modell: " . $upsBasicIdentModel;
}

$output = rtrim($output, " - ");

if($critical) {
  fwrite(STDOUT, $output);
	exit(2);
}

if($warning) {
  fwrite(STDOUT, $output);
	exit(1);
}

fwrite(STDOUT, "Status: " . getSystemStatus($upsBasicOutputStatus) . " - Batteriestatus: " . getBatteryStatus($upsBasicBatteryStatus) . " - Temperatur: " . $upsAdvBatteryTemperature . "°C - Verbleibend: " . $upsAdvBatteryRunTimeRemaining . " Stunden - Batteriealter: " . $difference_output . " (Letzter Tausch: " . $date1->format('d.m.Y') . ") - Modell: " . $upsBasicIdentModel);
exit(0);


function getSystemStatus($code) {

  $status = "";

  switch ($code) {
    case 1:
    $status = "Unbekannt";
    break;

    case 2:
    $status = "On-Line (Normal)";
    break;

    case 3:
    $status = "Batteriebetrieb";
    break;

    case 4:
    $status = "onSmartBoost";
    break;

    case 5:
    $status = "timedSleeping";
    break;

    case 6:
    $status = "softwareBypass";
    break;

    case 7:
    $status = "OFFLINE";
    break;

    case 8:
    $status = "Neustart";
    break;

    case 9:
    $status = "switchedBypass";
    break;

    case 10:
    $status = "hardwareFailureBypass";
    break;

    case 11:
    $status = "sleepingUntilPowerReturn";
    break;

    case 12:
    $status = "onSmartTrim";
    break;

    case 13:
    $status = "ecoMode";
    break;

    case 14:
    $status = "hotStandby";
    break;

    case 15:
    $status = "onBatteryTest";
    break;

    case 16:
    $status = "emergencyStaticBypass";
    break;

    case 17:
    $status = "staticBypassStandby";
    break;

    case 18:
    $status = "powerSavingMode";
    break;

    case 19:
    $status = "spotMode";
    break;

    case 20:
    $status = "eConversion";
    break;

    case 21:
    $status = "chargerSpotmode";
    break;

    case 22:
    $status = "inverterSpotmode";
    break;

  }

  return $status;

}


function getBatteryStatus($code) {

  $status = "";

  switch ($code) {
    case 1:
    $status = "Unbekannt";
    break;

    case 2:
    $status = "Normal";
    break;

    case 3:
    $status = "Schwach";
    break;

    case 4:
    $status = "Defekt";
    break;

  }

  return $status;

}

?>
