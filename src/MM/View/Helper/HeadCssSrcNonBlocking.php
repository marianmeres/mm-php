<?php

namespace MM\View\Helper;

class HeadCssSrcNonBlocking extends HeadCssSrc {
	public function toString(): string {
		$out = '';
		foreach ($this->_container as $src) {
			$out .= "<link href='$src' rel='stylesheet' media='foo' onload=\"if (media!='all') media='all'\">\n";
		}
		return $out;
	}
}
