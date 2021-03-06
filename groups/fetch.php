<?php

	include ($_SERVER['DOCUMENT_ROOT']."/includes/db.php");
	
	function get_total_all_records(){
		include ($_SERVER['DOCUMENT_ROOT']."/includes/db.php");
		$stmt = $db->prepare("SELECT * FROM groups");
		$stmt->execute();
		$result = $stmt->fetchAll();
		return $stmt->rowCount();
	}

	$order_column = array("group_name");

	$output = array();

	$query = "SELECT * FROM groups ";

	if(isset($_POST["search"]["value"])){
		$query .= 'WHERE LOWER (group_name) LIKE "%'.$_POST["search"]["value"].'%" ';
	}

	if(isset($_POST["order"])){
	    $query .= 'ORDER BY '.$order_column[$_POST["order"]["0"]["column"]].' '.$_POST["order"]["0"]["dir"].' ';
	}
	else{
	    $query .= 'ORDER BY group_name ';
	}

	if($_POST["length"] != -1){
		$query .= 'LIMIT ' . $_POST['start'] . ', ' . $_POST['length'];
	}

	$stmt = $db->prepare($query);
	$stmt->execute();
	$result = $stmt->fetchAll();
	$data = array();
	$filtered_rows = $stmt->rowCount();

	foreach($result as $row){              
		$sub_array = array();
	    $sub_array[] = $row["group_name"];
	    $sub_array[] = $row["group_min"];
	    $sub_array[] = $row["group_max"];
	    $sub_array[] = '<button type="button" name="update" id="'.$row["group_id"].'" class="btn btn-success btn-xs update"><i class="fa fa-pencil"></button>';
		$sub_array[] = '<button type="button" name="delete" id="'.$row["group_id"].'" class="btn btn-danger btn-xs delete"><i class="fa fa-trash"></i></button>';
		$data[] = $sub_array;
	}

	$output = array(
		"draw"				=>	intval($_POST["draw"]),
		"recordsTotal"		=> 	$filtered_rows,
		"recordsFiltered"	=>	get_total_all_records(),
		"data"				=>	$data
	);

	echo json_encode($output);

?>