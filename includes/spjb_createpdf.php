<?php
/* 
 * This file utilizes TCPDF (tcpdf.org)
 * and FPDI by Setasign (setasign.com)
 */

require_once('tcpdf/tcpdf.php');
require_once('fpdi/fpdi.php');

// Adds a parent node to the DOM in order to allow correct positioning via style
function addDOMParentStyle($dom, $classToSearch, $newDivStyle){
	$finder = new DomXPath($dom);
	// Find all wordpress-generated items with class aligncenter
	$nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classToSearch ')]");
	
	foreach($nodes as $node){
		$newdiv = $dom->createElement('div');
		$newdiv->setAttribute('style', $newDivStyle);
		$newnode = $node->cloneNode();
		$newdiv->appendChild($newnode);
		$node->parentNode->replaceChild($newdiv, $node);
	}
	
	return $dom;
}

function createpdf($id, $type, $title, $content, $pdflink){
	
	class PDF extends FPDI{
		var $_tplindex;
	
		function Header(){
			
			if(is_null($this->_tplindex)){
				$path = __DIR__;
				$this->setSourceFile($path."/template.pdf");
				$this->_tplindex = $this->importPage(1);
			}
			$this->useTemplate($this->_tplindex, 0, 0, 0);
		}
	}
	
	$content = str_replace("&nbsp;", "<br /><br />", stripslashes($content));
	
	$dom = new DOMDocument();
	$dom->loadHTML($content);
	
	// Edit DOM to correclty align wordpress-classes alignleft, center, right 
	$dom = addDOMParentStyle($dom, "alignleft", "text-align: left;");
	$dom = addDOMParentStyle($dom, "aligncenter", "text-align: center;");
	$dom = addDOMParentStyle($dom, "alignright", "text-align: right;");

	
	$content = $dom->saveHTML();
	
	
	$pdf = new PDF();
	$pdf->SetTitle($title);
	
	// CHANGE FROM HERE TO ADAPT TO YOUR PDF TEMPLATE
	
	$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	$pdf->SetFont('helvetica', '', 10.5, '', true);
	$pdf->AddPage();
	$pdf->setAbsY(10);
	
	// END OF ADAPTION AREA
	
	$pdf->writeHTML($content, true, false, false, false, false, '');
	$pdf->Output($pdflink, 'F');
}
?>