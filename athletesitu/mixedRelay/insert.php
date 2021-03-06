<?php
	include ($_SERVER['DOCUMENT_ROOT']."/includes/db.php");
  include ($_SERVER['DOCUMENT_ROOT']."/functions/isTime.php");
	// INICIALIZAR VARIÁVEIS
  $time = $_POST['time'] ?? '';
  $pos = $_POST["pos"] ?? '';
  $run = $_POST["run"] ?? '';
	$started = 0;
	$has_times = 0;
	$stmt_race = $db->prepare("SELECT race_type, race_gun_m, race_gun_f, race_relay, race_live, race_id FROM races WHERE race_id = ? LIMIT 1");
	$stmt_race->execute([$_POST["race"]]);
	$race = $stmt_race->fetch();
  if(isset($_POST["operation"])) {
		if($_POST["operation"] === "Add") {
			$stmt = $db->prepare("INSERT INTO athletes (athlete_chip, athlete_bib, athlete_name, athlete_sex, athlete_team_id, athlete_arrive_order, athlete_race_id) VALUES (:chip, :bib, :name, :sex, :team, :arriveOrder, :race)
			");
			$result = $stmt->execute(array(
				':chip'	=>	$_POST["chip"],
				':bib'	=>	$_POST["dorsal"],
				':name'		=>	$_POST["name"],
				':license'		=>	$_POST["licenca"],
				':sex'		=>	$_POST["sexo"],
				':category'		=>	$_POST["escalao"],
				':team'		=>	$_POST["clube"],
				':arriveOrder' => $_POST['ordem'],
				':race'		=>	$_POST["race"]
			));
		}
		if($_POST["operation"] === "Edit") {
			$curAthleteOrder = $_POST['ordem'];
      $nextAthleteOrder = $curAthleteOrder + 1;
      $previousAthleteOrder = $curAthleteOrder - 1;
			$bib = $_POST['dorsal'];
  	  // IGUAL TRIATLO
  		// LE O GUN DO ATLETA = T0
      if ($race['race_type'] === 'iturelay') {
    		$stmt = $db->prepare('SELECT athlete_t0 FROM athletes WHERE athlete_bib=? AND athlete_arrive_order=? AND athlete_race_id=? LIMIT 1 ');
    		$stmt->execute([$bib, $curAthleteOrder, $_POST["race"]]);
    		$stmtResult = $stmt->fetch();
    		$gun = $stmtResult['athlete_t0'];
      } elseif ($_POST['sexo'] === 'F') 
        $gun = $race['race_gun_f'];
      elseif ($_POST['sexo'] === 'M') 
        $gun = $race['race_gun_m'];
    	$live_swim = 'time';
      $live_t1 = 'time';
      $live_bike = 'time';
      $live_t2 = 'time';
      $live_run = 'time';
      $finishtime = 'time';
      $t0 = $gun;
      $swim = isTime($_POST['swim']);
      if ($swim !== '-') {
        // SO CALCULA LIVE SE HOUVER T0, NO CASO DO RELAY
        if ($gun === '-') {
          $live_swim = 'time';
          $finishtime = 'time';
        } else {
        	$live_swim = gmdate('H:i:s', strtotime($_POST['swim']) - strtotime($gun));
          $finishtime = $live_swim;
        }
        $started = 1; $has_times = 1;
      }
      $t1 = isTime($_POST['t1']);
      if($t1 !== '-') {
        $started=2; $has_times = 1;
        $finishtime = gmdate('H:i:s', strtotime($_POST['t1']) - strtotime($gun));
        if ($swim !=='-') $live_t1 = gmdate('H:i:s', strtotime($_POST['t1']) - strtotime($_POST['swim']));
      }
      $bike = isTime($_POST['bike']);
      if($bike !== '-') {
          $started=3; $has_times = 1;
          $finishtime = gmdate('H:i:s', strtotime($_POST['bike']) - strtotime($gun));
          if ($t1 !== '-') $live_bike = gmdate('H:i:s', strtotime($_POST['bike']) - strtotime($_POST['t1']));
      }
      $t2 = isTime($_POST['t2']);
      if($t2 !== '-') {
        $started=4; $has_times = 1;
        $finishtime = gmdate('H:i:s', strtotime($_POST['t2']) - strtotime($gun));
        if ($bike !== '-') $live_t2 = gmdate('H:i:s', strtotime($_POST['t2']) - strtotime($_POST['bike']));
      }
    	// ESTAFETAS TEM APENAS O TEMPO T5
    	$run = isTime($_POST['run']);
    	if (($time==="DNF") || ($time==="DNS") || ($time==="chkin") || ($time==="DSQ") || ($time==="LAP")) {
      	$run = '-'; 
        $total = '-';
      } else {
      	$time = $run;
      }
      if ($run !== '-') {
				$started=5; $has_times = 0; $time = $run;
        if($gun === '-') {
          $finishtime = 'time';
        } else {
				  $finishtime = gmdate('H:i:s', strtotime($run) - strtotime($gun));    
        }
        if ($t2 !== '-') $live_run = gmdate('H:i:s', strtotime($_POST['run']) - strtotime($_POST['t2']));
        if ($race['race_type'] === 'iturelay') {
          $stmt = $db->prepare('SELECT athlete_t1, athlete_t5 FROM athletes WHERE athlete_bib=? AND athlete_arrive_order=? AND athlete_race_id=? LIMIT 1');
          $stmt->execute([$bib, $nextAthleteOrder, $_POST["race"]]);
          $nextAthleteT1 = $stmt->fetch();
          // se tiver t1 do atlete seguinte da equipa, calcula live_t1 e atualiza tabela
          if ($nextAthleteT1['athlete_t1'] === '-') {
            $nextLiveT1 = 'time';
          } else {
            $nextLiveT1 = gmdate('H:i:s', strtotime($nextAthleteT1['athlete_t1']) - strtotime($run));
          }
          if ($nextAthleteT1['athlete_t5'] === '-') $nextTotal = 'time';
          else $nextTotal = gmdate('H:i:s', strtotime($nextAthleteT1['athlete_t5']) - strtotime($run));
          $stmt = $db->prepare('UPDATE live SET live_t1=?, live_finishtime=? WHERE live_bib=? AND live_license=? AND live_race=?');
          $stmt->execute([$nextLiveT1, $nextTotal, $bib, $nextAthleteOrder, $_POST["race"]]);
          $stmtNext = $db->prepare('UPDATE athletes SET athlete_t0=? WHERE athlete_bib=? AND athlete_arrive_order=? AND athlete_race_id=?');
          $stmtNext->execute([$run, $bib, $nextAthleteOrder, $_POST["race"]]);
        }
			}
      if (($time==="DNF") || ($time==="DSQ") || ($time==="LAP") || ($time==="DNS")) {
    		$has_times = 0;
    		$finishtime = $time;
        if ($time === 'DNS') {
          $started=0;
          $stmtLive = $db->prepare('UPDATE live SET live_t0=?, live_pos=9999, live_started=?, live_t1="time", live_t2="time", live_t3="time", live_t4="time", live_t5="time", live_finishtime="time" WHERE live_bib=? AND live_race=?');
          $stmtLive->execute([$time, $started, $bib, $_POST["race"]]);
          $stmt = $db->prepare('UPDATE athletes SET athlete_finishtime=?, athlete_pos=9999, athlete_t1="-", athlete_t2="-", athlete_t3="-", athlete_t4="-", athlete_t5="-" WHERE athlete_bib=? AND athlete_race_id=?');
          $stmt->execute([$time, $bib, $_POST["race"]]);      
        } else {
          $stmtLive = $db->prepare('UPDATE live SET live_t0=?, live_pos=9999, live_started=? WHERE live_bib=? AND live_race=?');
          $stmtLive->execute([$time, $started, $bib, $_POST["race"]]);
          $stmtLive1 = $db->prepare('UPDATE live SET live_finishtime="time" WHERE live_bib=? AND live_race=? AND live_license=?');
          $stmtLive1->execute([$bib, $_POST["race"], $curAthleteOrder]);
          $stmt = $db->prepare('UPDATE athletes SET athlete_finishtime=?, athlete_pos=9999 WHERE athlete_bib=? AND athlete_race_id=?');
          $stmt->execute([$time, $bib, $_POST["race"]]); 
        }
    	} 
      if ($started !== 5) $pos = 9999;
      if ($has_times == 1) $time = '-';      
      $stmt = $db->prepare("UPDATE athletes SET athlete_chip=:chip, athlete_bib=:dorsal, athlete_name=:name, athlete_sex=:sexo, athlete_team_id=:clube, athlete_t0=:t0, athlete_t1=:swim, athlete_t2=:t1, athlete_t3=:bike, athlete_t4=:t2, athlete_t5=:run, athlete_race_id=:race, athlete_finishtime=:time, athlete_started=:started, athlete_totaltime='-', athlete_arrive_order=:order WHERE athlete_id=:id AND athlete_race_id=:raceId"
      );
      $result = $stmt->execute(array(
        ':chip' => $_POST["chip"],
        ':dorsal' =>  $_POST["dorsal"],
        ':name'   =>  $_POST["name"],
        ':sexo'   =>  $_POST["sexo"],
        ':clube'    =>  $_POST["clube"],
        ':t0'   =>  $t0,
        ':swim'   =>  $swim,
        ':t1'   =>  $t1,
        ':bike'   =>  $bike,
        ':t2'   =>  $t2,
        ':run'    =>  $run,
        ':race'   =>  $_POST["race"],
        ':time'   =>  $time,
        ':started'    =>  $started,
        ':id' =>  $_POST["user_id"],
        ':order' => $curAthleteOrder,
        ':raceId' => $_POST["race"]
      ));
      if ($race['race_live'] == 1) {
        if (($time==="DNF") || ($time==="DSQ") || ($time==="LAP") || ($time==="DNS")) $finishtime = 'time';
        $stmtLive = $db->prepare("UPDATE live SET live_chip=:chip, live_bib=:dorsal, live_team_id=:clube, live_t1=:swim, live_t2=:t1, live_t3=:bike, live_t4=:t2, live_t5=:run, live_finishtime=:finishtime, live_started=:started, live_sex=:sex, live_license=:order WHERE live_id=:id AND live_race=:raceId"
        );
        $stmtLive->execute(array(
					':id'	=>	$_POST["user_id"],
          ':chip' => $_POST["chip"],
					':dorsal'	=>	$_POST["dorsal"],
					':clube'		=>	$_POST["clube"],
          ':swim'		=>	$live_swim,
					':t1'		=>	$live_t1,
					':bike'		=>	$live_bike,
					':t2'		=>	$live_t2,
					':run'		=>	$live_run,
					':finishtime'		=>	$finishtime,
					':started' => $started,
          ':sex' => $_POST["sexo"],
          ':order' => $curAthleteOrder,
          ':raceId' => $_POST["race"]
				));
	    }
			// FALTA QUANDO ESTIVER PREENCHIDO O TEMPO DE META EM VEZ DA HORA DO DIA
    }
  }
  // CODIGO REPETIDO
  $stmtFinisher = $db->prepare('SELECT live_bib, athlete_t5 FROM live JOIN athletes ON live_chip=athlete_chip WHERE live_license=4 AND live_started=5 AND live_race=?');
  $stmtFinisher->execute([$race['race_id']]);
  $finishers = $stmtFinisher->fetchAll();
  foreach ($finishers as $finisher) {
    $bib = $finisher['live_bib'];
    $teamTotalTime = gmdate('H:i:s', strtotime($finisher['athlete_t5']) - strtotime($race['race_gun_m']));   
    $stmtUpdate = $db->prepare('UPDATE live SET live_t0=? WHERE live_bib=? AND live_race=?');
    $stmtUpdate->execute([$teamTotalTime, $bib, $race['race_id']]);
  }
  // FIM CODIGO REPETIDO
  if ($run !== '-') {
		//**** atualizar coluna 'pos' conforme tempo total, para validar com registo de meta
	  $pos = 1;
    $queryathletes = $db->query("SELECT athlete_chip FROM athletes WHERE athlete_started = 5 ORDER BY athlete_t5");
    $athletes = $queryathletes->fetchAll(); 
    foreach ($athletes as $athlete) {
      $updateathletes = $db->prepare("UPDATE athletes SET athlete_pos=? WHERE athlete_chip=?");
      $updateathletes->execute([$pos, $athlete['athlete_chip']]);
      $pos++;
    }
    include_once($_SERVER['DOCUMENT_ROOT']."/functions/times-processing.php");
    if($race['race_type'] === 'iturelay') {
      processLivePositionsMxRelay($race['race_id'], $db);
    } else {
      processLivePositions($_POST["sexo"], $race['race_id'], $db);
    }
	}
?>