<?php 
  include_once($_SERVER['DOCUMENT_ROOT']."/includes/db.php");
  include_once($_SERVER['DOCUMENT_ROOT']."/functions/times-processing.php");
  $queryraces = $db->query("SELECT race_id, race_type, race_live, race_gun_m FROM races WHERE race_gun_m!='-' AND race_type='iturelay'");
  $races = $queryraces->fetchAll();
  foreach ($races as $race) {
    processTriathlonTimesMxRelay($race['race_id'], $race['race_type'], $race['race_live'], $race['race_gun_m'], $db);
  }
?>