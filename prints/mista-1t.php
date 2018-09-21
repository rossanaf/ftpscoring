<?php

//load the database configuration file
include($_SERVER['DOCUMENT_ROOT']."/includes/db.php");
require('fpdf.php');

class PDF extends FPDF
{
// Page header
function Header()
{
    //load the database configuration file
    include($_SERVER['DOCUMENT_ROOT']."/includes/db.php");

    $race_id = $_GET['race_id'];
    $queryrace = $db->prepare("SELECT * FROM races WHERE race_id = ? LIMIT 1");
    $queryrace->execute([$race_id]);
    $rowrace = $queryrace->fetch();

    // Logo
    $this->Image('../images/ftp_logo.png',10,5,54);
    // Times bold 15
    $this->SetFont('Times','B',14);
    // Move to the right
    $this->SetX(100);
    // Title
    $this->Cell(80,8,utf8_decode(strtoupper($rowrace['race_namepdf'])),0,1,'C');
    // Line break
    $this->SetX(100);
    $this->Cell(80,8,utf8_decode(ucwords($rowrace['race_ranking'])),0,0,'C');
	$this->Ln(24);
    
    $this->SetDrawColor(255,214,0);
    $this->Line(0,34,80,34);
    $this->SetDrawColor(0,110,38);
    $this->Line(80,34,150,34);
    $this->SetDrawColor(166,16,8);
    $this->Line(150,34,210,34);
    
    $this->SetLineWidth(.4);
    $this->SetFont('Times','',10);
    $this->SetFillColor(255);
    $this->Cell(24,5,utf8_decode("Local da Prova: "),0,0,'L',true);
    $this->SetX(100);
    $this->Cell(16,5,utf8_decode("Data: "),0,0,'L',true);
    $this->SetX(176);
    $this->Cell(8,5,utf8_decode("Hora da Partida: "),0,0,'R',true);
    
    $this->SetFont('Times','B',10);
    $this->SetX(34);
    $this->Cell(52,5,utf8_decode(ucwords($rowrace['race_location'])),0,0,'L',true);
    $this->SetX(110);
    $this->Cell(12,5,utf8_decode($rowrace['race_date']),0,0,'L',true);
    $this->SetX(190);
    $this->Cell(10,5,gmdate('H:i:s', strtotime($rowrace['race_gun'])-strtotime('02:00:00')),0,0,'R',true);
    $segment1 = ucwords($rowrace['race_segment1'])." - ".$rowrace['race_distsegment1'];
    if ($rowrace['race_segment2'] !== 'n.a.')
    {
        $segment2 = ucwords($rowrace['race_segment2'])." - ".$rowrace['race_distsegment2'];
    }
    $segment3 = ucwords($rowrace['race_segment3'])." - ".$rowrace['race_distsegment3'];

    $this->SetFont('Times','',10);
    $this->Ln(10);
    $this->Cell(20,5,utf8_decode("Distancias: "),0,0,'L',true);
    $this->SetDrawColor(255,214,0);
    $this->Cell(50,5,utf8_decode($segment1),1,0,'C',true);
    if ($rowrace['race_segment2'] !== 'n.a.')
    {
        $this->SetDrawColor(166,16,8);
        $this->SetX(90);
        $this->Cell(50,5,utf8_decode($segment2),1,0,'C',true);
    }
    $this->SetX(150);
    $this->SetDrawColor(0,110,38);
    $this->Cell(50,5,utf8_decode($segment3),1,0,'C',true);
    $this->Ln(10);
    $this->SetFont('Times','',14);
    if (stripos($rowrace['race_name'],'estafeta') === false)
    {
        $this->Cell(190,8,utf8_decode("Classificações Absolutos Femininos"),0,0,'C');
    } else {
        $this->Cell(190,8,utf8_decode("Classificações Estafetas Mistas"),0,0,'C');
    }
    $this->Ln(10);
    $this->SetDrawColor(0);
    $this->SetFillColor(87,87,85);
	$this->SetTextColor(255);
	$this->SetLineWidth(.1);
    $this->SetFont('Times','',9);
	
	// Header
    $this->SetX(10);
	$w = array(8, 14, 10, 48, 10, 60, 20, 20); // menos 2+2+4+10+2 = 20
	$header = array('#','Lic.','Dors.','Nome','Esc.','Equipa','T.Total', 'Diff.');
	for($i=0;$i<count($header);$i++)
		$this->Cell($w[$i],5,utf8_decode($header[$i]),1,0,'C',true);
	$this->Ln();
}

// Page footer
function Footer()
{
    $this->SetDrawColor(255,214,0);
    $this->Line(0,285,80,285);
    $this->SetDrawColor(0,110,38);
    $this->Line(80,285,150,285);
    $this->SetDrawColor(166,16,8);
    $this->Line(150,285,210,285);
    // Position at 1.0 cm from bottom
    $this->SetXY(10,-15);
    // Arial italic 8
    $this->SetFont('Times','',7);
    // Page number
    $this->Cell(0,10,utf8_decode("© Federação de Triatlo de Portugal"),0,0,'L');
    $this->Cell(0,10,utf8_decode("Página ").$this->PageNo().'/{nb}',0,0,'R');
}
}

// Instanciation of inherited class
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage('P','A4');

// Campeonato Nacional Clubes Triatlo Longo - Absolutos Masculinos
$pdf->SetFont('Times','',9);
$pdf->SetTextColor(0);
$pdf->SetFillColor(244,244,244);
$pos = 1;
$fill = false;

//TEMPOS DOS GUNS
$race_id = $_GET['race_id'];
$querygun = $db->prepare("SELECT race_type, race_gun FROM races WHERE race_id = ? LIMIT 1");
$querygun->execute([$race_id]);
$rowrace = $querygun->fetch();

//**** TEMPOS DE QUEM TERMINOU ****//
$query = $db->prepare("SELECT athletes.*, teams.team_name FROM athletes INNER JOIN teams ON athletes.athlete_team_id=teams.team_id WHERE athletes.athlete_started >= '5' AND athletes.athlete_race_id = ? AND athletes.athlete_sex = 'X'");
$query->execute([$race_id]);
$rows = $query->fetchAll();
foreach ($rows as $row) 
{
    // if($row['athlete_totaltime']=="-")
    // {
        if ($rowrace['race_type'] == 'crind')
        {
            $racegun = $row['athlete_t0'];
        } else {
            $racegun = $rowrace['race_gun'];
        }
        $athlete_totaltime = gmdate('H:i:s', strtotime($row['athlete_finishtime'])-strtotime($racegun));
        $query = $db->prepare("UPDATE athletes SET athlete_totaltime = ? WHERE athlete_chip = ?");
        $query->execute([$athlete_totaltime, $row['athlete_chip']]);
    // }
}

$query = $db->prepare("SELECT athletes.*, teams.team_name FROM athletes INNER JOIN teams ON athletes.athlete_team_id=teams.team_id WHERE athletes.athlete_started >= '5' AND athletes.athlete_race_id = ? AND athletes.athlete_sex = 'X' ORDER BY athlete_totaltime ASC");
$query->execute([$race_id]);
$rows = $query->fetchAll();
foreach ($rows as $row) 
{
    $pdf->Cell(8,5,$pos,1,0,'C',$fill);
    $pdf->Cell(14,5,$row['athlete_license'],1,0,'C',$fill);
    $pdf->Cell(10,5,$row['athlete_bib'],1,0,'C',$fill);
    $pdf->Cell(48,5,utf8_decode($row['athlete_name']),1,0,'L',$fill);
    $pdf->Cell(10,5,$row['athlete_category'],1,0,'C',$fill);
    $pdf->Cell(60,5,utf8_decode($row['team_name']),1,0,'L',$fill);
    $pdf->Cell(20,5,$row['athlete_totaltime'],1,0,'C',$fill);
    if($pos == 1){
        $pdf->Cell(20,5,"-",1,1,'C',$fill);
        $time_winner = $row['athlete_totaltime'];
    }else{
        $time = strtotime($row['athlete_totaltime']) - strtotime($time_winner);
        $pdf->Cell(20,5,gmdate('H:i:s', $time),1,1,'C',$fill);
    }
    $fill=!$fill;
    $pos++;
}

// **** PENALIZAÇÕES, time = DSQ / DNF / DNS
$penalty = array("DSQ", "DNF", "DNS", "LAP");
for($i=0;$i<count($penalty);$i++)
{
    $query = $db->prepare("SELECT athletes.*, teams.team_name FROM athletes INNER JOIN teams ON athletes.athlete_team_id=teams.team_id WHERE athletes.athlete_race_id = ? AND athletes.athlete_finishtime = ? AND athletes.athlete_sex = 'X' ORDER BY athletes.athlete_started DESC");
    $query->execute([$race_id, $penalty[$i]]);
    $rows = $query->fetchAll();
    foreach ($rows as $row) 
    {
        $pdf->Cell(8,5,$row['athlete_finishtime'],1,0,'C',$fill);
        $pdf->Cell(14,5,$row['athlete_license'],1,0,'C',$fill);
        $pdf->Cell(10,5,$row['athlete_bib'],1,0,'C',$fill);
        $pdf->Cell(48,5,utf8_decode($row['athlete_name']),1,0,'L',$fill);
        $pdf->Cell(10,5,$row['athlete_category'],1,0,'C',$fill);
        $pdf->Cell(60,5,utf8_decode($row['team_name']),1,0,'L',$fill);
        $pdf->Cell(20,5,$row['athlete_finishtime'],1,1,'C',$fill);
        $fill=!$fill;
    }
}

$pdf->Output();
?>