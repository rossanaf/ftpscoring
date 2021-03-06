<?php
	include ($_SERVER['DOCUMENT_ROOT']."/includes/db.php");
	$stmt = $db->prepare("SELECT Chip, ChipTime, Location FROM times ORDER BY Millisecs DESC LIMIT 5");
	$stmt->execute();
  $times = $stmt->fetchAll();
  $data = array();
	$filtered_rows = 0;
  foreach ($times as $time) {
  	$stmt_live = $db->prepare("SELECT live_firstname, live_bib, live_race, live_sex, team_name FROM live LEFT JOIN teams ON live.live_team_id=teams.team_id WHERE live_chip=?");
  	$stmt_live->execute([$time['Chip']]);
  	$live = $stmt_live->fetch();
    if($live['live_sex'] === 'M') {
		  $stmt_gunrace = $db->prepare("SELECT race_gun_m FROM races WHERE race_id=?");
  		$stmt_gunrace->execute([$live['live_race']]);
  		$gunrace = $stmt_gunrace->fetch();
      $gun = $gunrace['race_gun_m'];
    } elseif($live['live_sex'] === 'F') {
      $stmt_gunrace = $db->prepare("SELECT race_gun_f FROM races WHERE race_id=?");
      $stmt_gunrace->execute([$live['live_race']]);
      $gunrace = $stmt_gunrace->fetch();
      $gun = $gunrace['race_gun_f'];
    }
		list($date, $timing) = explode (" ", $time['ChipTime']);
		// $race_gun = gmdate('H:i:s', (strtotime($gunrace['race_gun']) + strtotime('01:00:00')));
    $timing = gmdate('H:i:s', (strtotime($timing) - strtotime($gun)));
    // $timing = gmdate('H:i:s', (strtotime($timing) - strtotime('01:00:00')));
    $sub_array = array();
		$sub_array[] = $live["live_bib"];
		$sub_array[] = $live["live_firstname"];
		$sub_array[] = $live["team_name"];
		if ($time["Location"] == 'TimeT1')
			$sub_array[] = 'Natação';
		elseif ($time["Location"] == 'TimeT2')
			$sub_array[] = 'Transição 1';
		elseif ($time["Location"] == 'TimeT3')
			$sub_array[] = 'Ciclismo';
		elseif ($time["Location"] == 'TimeT4')
			$sub_array[] = 'Transição 2';
		elseif ($time["Location"] == 'TimeT5')
			$sub_array[] = 'Corrida';
		$sub_array[] = $timing;
		$data[] = $sub_array;
	}
  $output = array(
		"draw"				=>	intval($_POST["draw"]),
		"data"				=>	$data
	);
	echo json_encode($output);
?>