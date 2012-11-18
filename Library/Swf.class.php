<?php
class Swf 
{
	public function output($text)
	{
		$m = new SWFMovie();
		$m->setRate(24.0);
		$m->setDimension(520, 320);
		$m->setBackground(255, 74, 119);
	
		// This functions was based on the example from
		// http://ming.sourceforge.net/examples/animation.html
		/*
		$colorr[1] = 255 * 0.85;
		$colorg[1] = 255 * 0.85;
		$colorb[1] = 255 * 0.85;
	
		$colorr[2] = 255 * 0.9;
		$colorg[2] = 255 * 0.9;
		$colorb[2] = 255 * 0.9;
	
		$colorr[3] = 255 * 0.95;
		$colorg[3] = 255 * 0.95;
		$colorb[3] = 255 * 0.95;
	
		$colorr[4] = 255;
		$colorg[4] = 255;
		$colorb[4] = 255;
	
		$c = 1;
		$anz = 4;
		$step = 4 / $anz;
		
		for ($i = 0; $i < $anz; $i += 1) {
			$x = 1040;
			$y = 50 + $i * 30;
			$size = ($i / 5 + 0.2);
			$t[$i] = $this->text($m, $colorr[$c], $colorg[$c], $colorb[$c], 0xff, 0, $x, $y, $size, $text);
			$c += $step;
		}
		*/
		$colorr = 255;
		$colorg = 255;
		$colorb = 255;
		
		$t = $this->text($m, $colorr, $colorg, $colorb, 0xff, 0, 1040, 170, 1, $text);
		
		$frames = 300;
		for ($j = 0; $j < $frames; $j++) {
			//for ($i = 0; $i < $anz; $i++) {
				//$t[$i]->moveTo(260 + round(sin($j / $frames * 2 * pi() + $i) * (50 + 50 * ($i + 1))), 160 + round(sin($j / $frames * 4 * pi() + $i) * (20 + 20 * ($i + 1))));
				//$t[$i]->rotateTo(round(sin($j / $frames * 2 * pi() + $i / 10) * 360));
				$t->moveTo(260 + round(sin($j / $frames * 2 * pi()) * (50 + 50)), 240 + round(sin($j / $frames * 4 * pi()) * (20 + 20)));
			//}
			$m->nextFrame();
		}
	
		header('Content-Type: application/x-shockwave-flash');
		$m->output(0);
		exit;
	}
	
	public function text($m, $r, $g, $b, $a, $rot, $x, $y, $scale, $string)
	{
		$f = new SWFFont(WEBROOT_DIR .DS. 'fonts' .DS. "CandyRandy.fdb");
		$t = new SWFText();
		$t->setFont($f);
		$t->setColor($r, $g, $b, $a);
		$t->setHeight(250);
		$t->moveTo(- ($t->getWidth($string)) / 2, 8);
		$t->addString($string);
		
		$i = $m->add($t);
		$i->rotateTo($rot);
		$i->moveTo($x, $y);
		$i->scale($scale, $scale);
		
		return $i;
	}
}
?>